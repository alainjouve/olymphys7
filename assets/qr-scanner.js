import { BrowserQRCodeReader } from '@zxing/browser';

const scanner = document.getElementById('invitation-qr-scanner');

if (scanner) {
    const video = document.getElementById('qr-video');
    const startButton = document.getElementById('qr-start');
    const status = document.getElementById('qr-status');
    const csrfToken = scanner.dataset.csrfToken;
    const validationUrl = scanner.dataset.validationUrl;
    const reader = new BrowserQRCodeReader();
    let controls;
    let validating = false;

    function stopScanner() {
        controls?.stop();
        controls = undefined;
        startButton.disabled = false;
    }

    async function validateQrCode(qrText) {
        if (validating) {
            return;
        }
        validating = true;
        stopScanner();
        startButton.disabled = true;
        status.className = 'alert alert-info';
        status.textContent = 'Vérification de l’invitation…';

        let response;
        try {
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
            status.textContent = 'Impossible de contacter le serveur. Vérifiez la connexion puis réessayez.';
            validating = false;
            startButton.disabled = false;
            return;
        }

        let result;
        try {
            result = await response.json();
        } catch {
            status.className = 'alert alert-danger';
            status.textContent = 'Le serveur a renvoyé une réponse invalide.';
            validating = false;
            startButton.disabled = false;
            return;
        }

        if (!response.ok) {
            status.className = 'alert alert-danger';
            status.textContent = result.message || 'Cette invitation n’a pas pu être validée.';
            validating = false;
            startButton.disabled = false;
            return;
        }

        status.className = result.alreadyPresent ? 'alert alert-warning' : 'alert alert-success';
        status.innerHTML = '';
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
        if (!window.isSecureContext) {
            status.className = 'alert alert-danger';
            status.textContent = 'La caméra nécessite une connexion HTTPS sécurisée.';
            return;
        }

        if (!navigator.mediaDevices?.getUserMedia) {
            status.className = 'alert alert-danger';
            status.textContent = 'Ce navigateur ne permet pas l’accès à la caméra. Ouvrez cette page avec un navigateur récent.';
            return;
        }

        status.className = 'alert alert-info';
        status.textContent = 'Autorisez l’accès à la caméra si le navigateur le demande.';
        startButton.disabled = true;
        video.setAttribute('autoplay', '');
        video.setAttribute('muted', '');
        video.setAttribute('playsinline', '');
        video.muted = true;
        video.playsInline = true;

        try {
            controls = await reader.decodeFromConstraints({
                audio: false,
                video: { facingMode: { ideal: 'environment' } },
            }, video, (result, _error, scanControls) => {
                if (result) {
                    controls = scanControls;
                    validateQrCode(result.getText());
                }
            });
        } catch (error) {
            stopScanner();
            status.className = 'alert alert-danger';
            if (error.name === 'NotAllowedError' || error.name === 'SecurityError') {
                status.textContent = 'L’accès à la caméra est refusé. Autorisez-le dans les réglages du navigateur puis réessayez.';
            } else if (error.name === 'NotFoundError' || error.name === 'OverconstrainedError') {
                status.textContent = 'Aucune caméra compatible n’a été trouvée sur cet appareil.';
            } else if (error.name === 'NotReadableError' || error.name === 'AbortError') {
                status.textContent = 'La caméra est utilisée par une autre application ou ne peut pas démarrer.';
            } else {
                status.textContent = `Impossible de démarrer la caméra${error.message ? ` : ${error.message}` : '.'}`;
            }
        }
    });
}
