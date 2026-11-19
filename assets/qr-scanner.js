import { BrowserQRCodeReader } from '@zxing/browser';

const scanner = document.getElementById('invitation-qr-scanner');

// Le script est aussi chargé sur d’autres pages : on ne l’initialise que si le scanner existe.
if (scanner) {
    const video = document.getElementById('qr-video');
    const startButton = document.getElementById('qr-start');
    const status = document.getElementById('qr-status');
    const statusMessage = document.getElementById('qr-status-message');
    const loadingSpinner = document.getElementById('qr-loading-spinner');
    const csrfToken = scanner.dataset.csrfToken;
    const validationUrl = scanner.dataset.validationUrl;
    // Lecteur ZXing qui utilise les API du navigateur pour décoder les QR codes depuis la vidéo.
    const reader = new BrowserQRCodeReader();
    let controls;
    let mediaStream;
    // Empêche une seconde validation pendant qu’une première requête est en cours.
    let validating = false;

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
        stopScanner();
        startButton.disabled = true;
        status.className = 'alert alert-info';
        statusMessage.textContent = 'Vérification de l’invitation…';

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
            status.className = 'alert alert-danger';
            statusMessage.textContent = 'Impossible de contacter le serveur. Vérifiez la connexion puis réessayez.';
            validating = false;
            startButton.disabled = false;
            return;
        }

        let result;
        try {
            // Le serveur renvoie le nom et indique si la présence avait déjà été validée.
            result = await response.json();
        } catch {
            status.className = 'alert alert-danger';
            statusMessage.textContent = 'Le serveur a renvoyé une réponse invalide.';
            validating = false;
            startButton.disabled = false;
            return;
        }

        if (!response.ok) {
            status.className = 'alert alert-danger';
            statusMessage.textContent = result.message || 'Cette invitation n’a pas pu être validée.';
            validating = false;
            startButton.disabled = false;
            return;
        }

        status.className = result.alreadyPresent ? 'alert alert-warning' : 'alert alert-success';
        statusMessage.textContent = '';
        // Affiche le résultat sans injecter directement de contenu HTML provenant du serveur.
        const label = document.createElement('div');
        label.className = result.alreadyPresent ? 'display-4' : 'display-1 font-weight-bold';
        label.textContent = result.alreadyPresent ? 'Déjà validée' : 'Valide';
        const name = document.createElement('div');
        name.className = 'h3 mt-2';
        name.textContent = `${result.prenom} ${result.nom}`;
        status.append(label, name);
        validating = false;
        startButton.disabled = false;
    }

    startButton.addEventListener('click', async function () {
        if (validating) {
            return;
        }
        // Si un flux existe, le bouton sert à arrêter le scan au lieu de relancer la caméra.
        if (mediaStream) {
            stopScanner();
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
            video.srcObject = mediaStream;
            await video.play();
            loadingSpinner.classList.add('d-none');
            statusMessage.textContent = 'Caméra active. Présentez le QR code devant l’objectif.';
            startButton.disabled = false;
            startButton.textContent = 'Arrêter le scan';
            startButton.classList.remove('btn-primary');
            startButton.classList.add('btn-danger');
            // Analyse le flux vidéo en continu et valide le premier QR code détecté.
            controls = await reader.decodeFromVideoElement(video, (result, _error, scanControls) => {
                if (result) {
                    controls = scanControls;
                    validateQrCode(result.getText());
                }
            });
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
