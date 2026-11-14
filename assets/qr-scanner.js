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

    function stopScanner() {
        controls?.stop();
        controls = undefined;
        startButton.disabled = false;
    }

    async function validateQrCode(qrText) {
        stopScanner();
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
            return;
        }

        let result;
        try {
            result = await response.json();
        } catch {
            status.className = 'alert alert-danger';
            status.textContent = 'Le serveur a renvoyé une réponse invalide.';
            return;
        }

        if (!response.ok) {
            status.className = 'alert alert-danger';
            status.textContent = result.message || 'Cette invitation n’a pas pu être validée.';
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
    }

    startButton.addEventListener('click', async function () {
        status.className = 'alert alert-info';
        status.textContent = 'Autorisez l’accès à la caméra et présentez le QR code devant l’objectif.';
        startButton.disabled = true;

        try {
            controls = await reader.decodeFromVideoDevice(undefined, video, (result, _error, scanControls) => {
                if (result) {
                    controls = scanControls;
                    validateQrCode(result.getText());
                }
            });
        } catch (error) {
            stopScanner();
            status.className = 'alert alert-danger';
            status.textContent = error.name === 'NotAllowedError'
                ? 'L’accès à la caméra est refusé. Autorisez-le dans les réglages du navigateur.'
                : 'Impossible de démarrer la caméra. Vérifiez ses autorisations et que le site utilise HTTPS.';
        }
    });
}
