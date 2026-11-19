import { BrowserQRCodeReader } from '@zxing/browser';

const scanner = document.getElementById('invitation-qr-scanner');
const scanFeedbackDelay = 700;

// Le script est aussi chargé sur d’autres pages : on ne l’initialise que si le scanner existe.
if (scanner) {
    const video = document.getElementById('qr-video');
    const startButton = document.getElementById('qr-start');
    const status = document.getElementById('qr-status');
    const statusMessage = document.getElementById('qr-status-message');
    const resultContainer = document.getElementById('qr-result');
    const loadingSpinner = document.getElementById('qr-loading-spinner');
    const csrfToken = scanner.dataset.csrfToken;
    const validationUrl = scanner.dataset.validationUrl;
    // Lecteur ZXing qui utilise les API du navigateur pour décoder les QR codes depuis la vidéo.
    const reader = new BrowserQRCodeReader();
    let audioContext;
    let audioContextReady;
    let controls;
    let mediaStream;
    // Empêche une seconde validation pendant qu’une première requête est en cours.
    let validating = false;
    let lastQrText;
    let qrMissingSince;

    function prepareFeedbackAudio() {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) {
            return;
        }

        try {
            audioContext ??= new AudioContext();
            if (audioContext.state !== 'running') {
                audioContextReady = audioContext.resume().catch((error) => {
                    console.error('Impossible d’activer le son de validation.', error);
                });
            }
        } catch (error) {
            console.error('Impossible d’initialiser le son de validation.', error);
        }
    }

    async function playFeedbackBeep(startFrequency, endFrequency, duration) {
        if (!audioContext) {
            return;
        }

        try {
            if (audioContextReady) {
                await audioContextReady;
            }
            if (audioContext.state !== 'running') {
                await audioContext.resume();
            }
            if (audioContext.state !== 'running') {
                throw new Error(`Le contexte audio est ${audioContext.state}.`);
            }

            const oscillator = audioContext.createOscillator();
            const volume = audioContext.createGain();
            const now = audioContext.currentTime;
            oscillator.frequency.setValueAtTime(startFrequency, now);
            oscillator.frequency.exponentialRampToValueAtTime(endFrequency, now + duration);
            volume.gain.setValueAtTime(0.15, now);
            volume.gain.exponentialRampToValueAtTime(0.001, now + duration);
            oscillator.connect(volume);
            volume.connect(audioContext.destination);
            oscillator.start(now);
            oscillator.stop(now + duration);
        } catch (error) {
            console.error('Impossible de jouer le bip de validation.', error);
        }
    }

    function playSuccessBeep() {
        return playFeedbackBeep(880, 880, 0.2);
    }

    function playFailureBeep() {
        return playFeedbackBeep(880, 220, 0.45);
    }

    async function showValidationFailure(message) {
        await new Promise((resolve) => setTimeout(resolve, scanFeedbackDelay));
        status.className = 'alert alert-danger';
        statusMessage.textContent = message;
        await playFailureBeep();
    }

    function stopScanner() {
        // Arrête le décodeur ZXing et libère aussi la caméra du navigateur.
        controls?.stop();
        controls = undefined;
        mediaStream?.getTracks().forEach((track) => track.stop());
        mediaStream = undefined;
        video.pause();
        video.srcObject = null;
        video.hidden = true;
        video.style.display = 'none';
        loadingSpinner.classList.add('d-none');
        startButton.disabled = false;
        startButton.textContent = 'Démarrer le scan';
        startButton.classList.remove('btn-danger');
        startButton.classList.add('btn-primary');
    }

    async function validateQrCode(qrText) {
        // Le même QR peut être détecté sur plusieurs images successives de la vidéo.
        if (validating) {
            return;
        }
        validating = true;
        status.className = 'alert alert-info';
        statusMessage.textContent = 'Scan pris en compte. Vérification de l’invitation…';
        loadingSpinner.classList.remove('d-none');
        resultContainer.replaceChildren();

        try {
            let response;
            try {
                // Le QR contient les données de réservation envoyées au serveur pour validation.
                response = await fetch(validationUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: qrText,
                });
            } catch {
                await showValidationFailure('Impossible de contacter le serveur. Vérifiez la connexion puis réessayez.');
                return;
            }

            let result;
            try {
                // Le serveur renvoie le nom et indique si la présence avait déjà été validée.
                result = await response.json();
            } catch {
                await showValidationFailure('Le serveur a renvoyé une réponse invalide.');
                return;
            }

            if (!response.ok) {
                await showValidationFailure(result.message || 'Cette invitation n’a pas pu être validée.');
                return;
            }

            await new Promise((resolve) => setTimeout(resolve, scanFeedbackDelay));
            status.className = result.alreadyPresent ? 'alert alert-warning' : 'alert alert-success';
            statusMessage.textContent = '';
            // Affiche le résultat sans injecter directement de contenu HTML provenant du serveur.
            const label = document.createElement('div');
            label.className = result.alreadyPresent ? 'display-4' : 'display-1 font-weight-bold';
            label.textContent = result.alreadyPresent ? 'Déjà validée' : 'Valide';
            const name = document.createElement('div');
            name.className = 'h3 mt-2';
            name.textContent = `${result.prenom} ${result.nom}`;
            resultContainer.append(label, name);
            await playSuccessBeep();
        } finally {
            loadingSpinner.classList.add('d-none');
            validating = false;
        }
    }

    startButton.addEventListener('click', async function () {
        if (validating) {
            return;
        }
        // Si un flux existe, le bouton sert à arrêter le scan au lieu de relancer la caméra.
        if (mediaStream) {
            stopScanner();
            resultContainer.replaceChildren();
            status.className = 'alert alert-secondary';
            statusMessage.textContent = 'Scan arrêté. Appuyez sur « Démarrer le scan » pour reprendre.';
            return;
        }
        // Les navigateurs n’autorisent généralement la caméra que dans un contexte HTTPS.
        if (!window.isSecureContext) {
            status.className = 'alert alert-danger';
            statusMessage.textContent = 'La caméra nécessite une connexion HTTPS sécurisée.';
            return;
        }

        if (!navigator.mediaDevices?.getUserMedia) {
            status.className = 'alert alert-danger';
            statusMessage.textContent = 'Ce navigateur ne permet pas l’accès à la caméra. Ouvrez cette page avec un navigateur récent.';
            return;
        }

        prepareFeedbackAudio();
        status.className = 'alert alert-info';
        statusMessage.textContent = 'Demande d’accès à la caméra…';
        loadingSpinner.classList.remove('d-none');
        startButton.disabled = true;
        // Ces attributs sont nécessaires notamment pour que la vidéo reste intégrée sur iOS.
        video.setAttribute('autoplay', '');
        video.setAttribute('muted', '');
        video.setAttribute('playsinline', '');
        video.muted = true;
        video.playsInline = true;

        try {
            // Demande la caméra arrière si disponible, sinon laisse le navigateur choisir.
            mediaStream = await navigator.mediaDevices.getUserMedia({
                audio: false,
                video: { facingMode: { ideal: 'environment' } },
            });
            video.hidden = false;
            video.style.display = '';
            loadingSpinner.classList.add('d-none');
            // ZXing attache le flux vidéo, démarre la lecture et fournit les contrôles du scan.
            controls = await reader.decodeFromStream(mediaStream, video, (result, _error, scanControls) => {
                if (result) {
                    qrMissingSince = undefined;
                    const qrText = result.getText();
                    if (qrText === lastQrText || validating) {
                        return;
                    }

                    lastQrText = qrText;
                    controls = scanControls;
                    validateQrCode(qrText);
                } else if (_error?.name === 'NotFoundException') {
                    // Réautorise le même QR après qu’il a quitté le champ de la caméra.
                    qrMissingSince ??= Date.now();
                    if (Date.now() - qrMissingSince >= 1000) {
                        lastQrText = undefined;
                    }
                }
            });
            statusMessage.textContent = 'Caméra active. Présentez le QR code devant l’objectif.';
            startButton.disabled = false;
            startButton.textContent = 'Arrêter le scan';
            startButton.classList.remove('btn-primary');
            startButton.classList.add('btn-danger');
            if (window.matchMedia('(max-width: 767.98px)').matches) {
                requestAnimationFrame(() => requestAnimationFrame(() => {
                    startButton.parentElement?.scrollIntoView({ behavior: 'smooth', block: 'end' });
                }));
            }
        } catch (error) {
            // Arrête tout flux éventuellement ouvert avant d’afficher une erreur adaptée.
            stopScanner();
            status.className = 'alert alert-danger';
            if (error.name === 'NotAllowedError' || error.name === 'SecurityError') {
                statusMessage.textContent = 'L’accès à la caméra est refusé. Autorisez-le dans les réglages du navigateur puis réessayez.';
            } else if (error.name === 'NotFoundError' || error.name === 'OverconstrainedError') {
                statusMessage.textContent = 'Aucune caméra compatible n’a été trouvée sur cet appareil.';
            } else if (error.name === 'NotReadableError' || error.name === 'AbortError') {
                statusMessage.textContent = 'La caméra est utilisée par une autre application ou ne peut pas démarrer.';
            } else {
                statusMessage.textContent = `Impossible de démarrer la caméra${error.message ? ` : ${error.message}` : '.'}`;
            }
        }
    });
}
