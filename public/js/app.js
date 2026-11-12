document.addEventListener('DOMContentLoaded', function () {

    $("#form1").on('change', function () {
        $("#form1").submit();
    });

    var inputPhoto = document.querySelector('#photo-drop-zone input[type="file"]')
        || document.getElementById('photos_files')
        || document.getElementById('photos_photoFiles');
    var previewContainer = document.getElementById('affichephotos');
    var dropZone = document.getElementById('photo-drop-zone');
    var dropMessage = document.getElementById('photo-drop-message');

    function showPhotoPreviews(files) {
        if (!previewContainer) {
            return;
        }

        previewContainer.innerHTML = '';
        var container = document.createElement('div');
        container.className = 'row';
        previewContainer.appendChild(container);

        Array.from(files).forEach(function (file) {
            var column = document.createElement('div');
            column.className = 'col';
            var card = document.createElement('div');
            card.className = 'card';
            card.style.width = '250px';
            var header = document.createElement('div');
            header.className = 'card-header';
            header.textContent = file.name;
            var body = document.createElement('div');
            body.className = 'card-body';
            var image = document.createElement('img');
            image.width = 220;
            image.alt = file.name;
            var checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = 'checkbox#' + slugify(file.name.split('.')[0]);
            checkbox.checked = true;

            var reader = new FileReader();
            reader.onload = function (event) {
                image.src = event.target.result;
            };
            reader.readAsDataURL(file);

            body.append(image, checkbox);
            card.append(header, body);
            column.appendChild(card);
            container.appendChild(column);
        });
    }

    if (inputPhoto) {
        inputPhoto.addEventListener('change', function () {
            if (dropMessage) {
                dropMessage.textContent = '';
            }
            if (inputPhoto.files.length > 5) {
                inputPhoto.value = '';
                if (dropMessage) {
                    dropMessage.textContent = 'Vous pouvez choisir au maximum 5 photos à la fois.';
                }
                showPhotoPreviews([]);
                return;
            }
            showPhotoPreviews(inputPhoto.files);
        });
    }

    if (dropZone && inputPhoto) {
        dropZone.addEventListener('click', function (event) {
            if (event.target !== inputPhoto && event.target.tagName !== 'LABEL') {
                inputPhoto.click();
            }
        });
        dropZone.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                inputPhoto.click();
            }
        });
        ['dragenter', 'dragover'].forEach(function (eventName) {
            dropZone.addEventListener(eventName, function (event) {
                event.preventDefault();
                dropZone.classList.add('is-dragging');
            });
        });
        ['dragleave', 'drop'].forEach(function (eventName) {
            dropZone.addEventListener(eventName, function (event) {
                event.preventDefault();
                dropZone.classList.remove('is-dragging');
            });
        });
        dropZone.addEventListener('drop', function (event) {
            var droppedFiles = event.dataTransfer.files;
            if (droppedFiles.length > 5) {
                if (dropMessage) {
                    dropMessage.textContent = 'Vous pouvez déposer au maximum 5 photos à la fois.';
                }
                return;
            }
            var transfer = new DataTransfer();
            Array.from(droppedFiles).forEach(function (file) {
                transfer.items.add(file);
            });
            inputPhoto.files = transfer.files;
            inputPhoto.dispatchEvent(new Event('change', {bubbles: true}));
        });
    }

    var uploadForm = document.getElementById('photo-upload-form');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function (event) {
            event.preventDefault();

            var progress = document.getElementById('photo-upload-progress');
            var progressBar = document.getElementById('photo-upload-progress-bar');
            var status = document.getElementById('photo-upload-status');
            var submitButton = event.submitter;
            var formData = new FormData(uploadForm);
            if (submitButton && submitButton.name) {
                formData.append(submitButton.name, submitButton.value);
            }

            progress.style.display = 'flex';
            status.textContent = 'Envoi des photos…';
            if (submitButton) {
                submitButton.disabled = true;
            }

            var request = new XMLHttpRequest();
            request.open(uploadForm.method || 'POST', uploadForm.action);
            request.upload.addEventListener('progress', function (progressEvent) {
                if (progressEvent.lengthComputable) {
                    var percent = Math.round(progressEvent.loaded * 100 / progressEvent.total);
                    progressBar.style.width = percent + '%';
                    progressBar.textContent = percent + '%';
                    progress.setAttribute('aria-valuenow', String(percent));
                }
            });
            request.addEventListener('load', function () {
                if (request.status >= 200 && request.status < 400) {
                    status.textContent = 'Envoi terminé. Chargement du résultat…';
                    window.location.assign(request.responseURL || uploadForm.action);
                    return;
                }
                status.textContent = 'Le dépôt a échoué. Veuillez réessayer.';
                if (submitButton) {
                    submitButton.disabled = false;
                }
            });
            request.addEventListener('error', function () {
                status.textContent = 'Erreur réseau pendant le dépôt. Veuillez réessayer.';
                if (submitButton) {
                    submitButton.disabled = false;
                }
            });
            request.send(formData);
        });
    }


});


function changejure(j) {//j est l'objet input qui a lancé la fonction, pour le formulaire de gestion des jures des cia

    var data_value = j.value;
    var data_type = j.name;
    var id_jure = j.id.split(data_type)[1];
    var formURL = document.getElementsByTagName('form')[2].action;
    $.ajax({
        url: formURL,
        type: "POST",
        data: {value: data_value, type: data_type, idjure: id_jure},

        success: function () {
            document.querySelector('#gestionjures').click()
        },

        error: function (data) {
            alert("Error while submitting Data");
        },
    });


}

function changejurecn(j) {//j est l'objet input qui a lancé la fonction, pour le formulaire de gestion des jures des cia

    var data_value = j.value;
    var data_type = j.name;
    var id_jure = j.id.split(data_type)[1];
    var formURL = document.getElementsByTagName('form')[3].action;

    $.ajax({
        url: formURL,
        type: "POST",
        data: {value: data_value, type: data_type, idjure: id_jure},

        success: function () {
            document.querySelector('#gestionjures').click()
        },

        error: function (data) {
            alert("Error while submitting Data");
        },
    });


}

$(window).on("unload", function () {//Pour que lors de la saisie de la répartition des jurés, le fenêtre revienne à la position définie par l'utilisateur après l'enregistrement des données
    var tableau = document.getElementById("changejurescontainer")
    var scrollPositionY = `${tableau.scrollTop}`;
    var scrollPositionX = `${tableau.scrollLeft}`;
    var scrollWindowY = `${window.scrollY}`
    localStorage.setItem("scrollPositionY", scrollPositionY);
    localStorage.setItem("scrollPositionX", scrollPositionX);
    localStorage.setItem("scrollWindowY", scrollWindowY);
});
$(window).on("load", function () {
    var tableau = document.getElementById("changejurescontainer")
    if (tableau) {
        tableau.scrollTop = parseInt(localStorage['scrollPositionY']);
        tableau.scrollLeft = parseInt(localStorage['scrollPositionX']);
        window.scrollTo(0, parseInt(localStorage['scrollWindowY']));
    }

});

function changeequipe(e, i, j) {
    var type = 'equipe';
    var data_value = e.value;
    var id_equipe = i;
    var id_jure = j;
    console.log(data_value);
    var formURL = document.getElementsByName('forme'.concat(id_equipe))[0].action;
    console.log(formURL);
    $.ajax({
        url: formURL,
        type: "GET",
        data: {type: type, value: data_value, idequipe: id_equipe, idjure: id_jure},

        success: function () {
            document.querySelector('#gestionjures').click()
            //window.history.back();
        },

        error: function (xhr, status, error) {
            alert(xhr.responseText);
        }
    });


}

function changeequipecia(e, i, j) {
    var type = 'equipe';
    var data_value = e.value;
    var id_equipe = i;
    var id_jure = j;
    console.log(data_value);
    var formURL = document.getElementById('form-'.concat(id_jure))[0].action;

    console.log(formURL);
    $.ajax({
        url: formURL,
        type: "GET",
        data: {type: type, value: data_value, idequipe: id_equipe, idjure: id_jure},

        success: function () {
            document.querySelector('#gestionjures').click()

        },

        error: function (data) {
            alert("Error while submitting Data");
        },
    });


}

$('#modalconfirmjure').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget) // Button that triggered the modal
    var recipient = button.data('idjure');

    var modal = $(this)
    modal.find('.modal-title').text('Attention!!!!')
    modal.find('.modal-body input').val(recipient)
});
$('#modalconfirmjurecn').on('show.bs.modal', function (event) {//envoi des conseils jury cia
    var button = $(event.relatedTarget) // Button that triggered the modal
    var recipient = button.data('idjure');

    var modal = $(this)
    modal.find('.modal-title').text('Attention!!!!')
    modal.find('.modal-body input').val(recipient)
});
$(document).on('show.bs.modal', '#modalenvoiconseilscn', function (event) {//envoi des recommandations jury du cn
    var button = $(event.relatedTarget) // Button that triggered the modal
    var recipient = button.data('id');
    console.log(recipient)
    var modal = $(this)
    modal.find('.modal-title').text('Attention')
    modal.find('.modal-body input').val(recipient)
});
$(document).on('show.bs.modal', '#modalenvoiconseils', function (event) {
    var button = $(event.relatedTarget) // Button that triggered the modal
    console.log(button);
    var recipient = button.data('idequipe');

    var modal = $(this)
    modal.find('.modal-title').text('Attention')
    modal.find('.modal-body input').val(recipient)
});

function confirmer() {

    console.log('OK');
    var form = document.getElementsByName("form1");
    console.log(form)
    var formURL = "/secretariatjuryCia/confirm_gestion_jures"//document.getElementsByTagName('form')[0].action;
    var prenoms = [];
    for (i = 0; i < form.length; i++) {
        prenoms[i] = form[i].getElementsByTagName('input')
        console.log(prenoms[i]);

    }


    $.ajax({
        url: formURL,
        type: "POST",
        data: {values: prenoms},

        success: function () {
            document.querySelector('#gestionjures').click()

        },

        error: function (data) {
            alert("Error while submitting Data");
        },
    });
}

function modifheure(j) {//j est l'objet input qui a lancé la fonction, pour le formulaire de gestion des jures des cia


    var data_type = j.name;
    var id_equipe = j.id.split(data_type)[1];
    var data_value = j.value;
    var formURL = document.getElementById('formhoraires').action;


    console.log(data_type);
    console.log(id_equipe);

    console.log(data_value);
    $.ajax({
        url: formURL,
        type: "GET",
        data: {value: data_value, type: data_type, idequipe: id_equipe},

        success: function () {
            document.querySelector('#gestionjures').click()//Recharge la page pour actualiser l'affichage
        },

        error: function (data) {
            alert("Error while submitting Data");
        },
    });


}

function modifsalle(j) {//j est l'objet input qui a lancé la fonction, pour le formulaire de gestion des jures des cia

    var data_value = j.value;
    var data_type = j.name;
    var id_equipe = j.id.split(data_type)[1];

    var formURL = document.getElementById('formsalles').action;
    console.log(data_type);
    console.log(id_equipe);

    console.log(data_value);

    $.ajax({
        url: formURL,
        type: "GET",
        data: {value: data_value, type: data_type, idequipe: id_equipe},

        success: function () {
            document.querySelector('#gestionjures').click()
        },

        error: function (data) {
            alert("Error while submitting Data");
        },
    });


}

function modifordre(j) {//j est l'objet input qui a lancé la fonction, pour le formulaire de gestion des jures des cia

    var data_value = j.value;
    var data_type = j.name;
    var id_equipe = j.id.split(data_type)[1];

    var formURL = document.getElementById('formordre').action;
    console.log(data_type);
    console.log(id_equipe);

    console.log(data_value);

    $.ajax({
        url: formURL,
        type: "GET",
        data: {value: data_value, type: data_type, idequipe: id_equipe},

        success: function () {
            document.querySelector('#gestionjures').click()
        },

        error: function (data) {
            alert("Error while submitting Data");
        },
    });


}

function allcheck(check) {
    var checkboxes = document.querySelectorAll('input[type=checkbox]')
    for (var i in checkboxes) {
        checkboxes[i].checked = check;
    }
}

function slugify(str) {
    return str
        .toLowerCase()
        .trim()
        .replace(/[^\w\s-]/g, "")
        .replace(/[\s_-]+/g, "-")
        .replace(/^-+|-+$/g, "");
}

function imClick(id) {
    var checkbox = document.getElementById('form_photo-' + id);
    if (checkbox) {
        checkbox.checked = !checkbox.checked;
        checkbox.dispatchEvent(new Event('change', {bubbles: true}));
    }
}

function raz_sel() {
    var count = document.getElementById('sp_nbPhotosSel');
    var submit = document.getElementById('form_telecharger');
    var form = submit ? submit.closest('form') : null;

    if (form) {
        form.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
            checkbox.checked = false;
        });
    }
    if (count) {
        count.textContent = '0';
    }
    if (submit) {
        submit.disabled = true;
    }
}

function setSujetPhoto(id) {
    var input = document.getElementById('sujet_photo-' + id);
    if (!input) {
        return;
    }

    $.ajax({
        url: input.dataset.url,
        type: 'POST',
        data: {idPhoto: id, idSujetPhoto: input.value},
        error: function () {
            alert('Erreur lors de l’enregistrement du type de sujet.');
        }
    });
}

$(document).ready(function () {
    var count = document.getElementById('sp_nbPhotosSel');
    var submit = document.getElementById('form_telecharger');
    var form = submit ? submit.closest('form') : null;

    if (form && count && submit) {
        form.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                var selectedCount = form.querySelectorAll('input[type="checkbox"]:checked').length;
                count.textContent = selectedCount;
                submit.disabled = selectedCount === 0;
            });
        });
        submit.disabled = form.querySelectorAll('input[type="checkbox"]:checked').length === 0;
    }

    var scrollTopButton = document.getElementById('scroll-top-btn');
    if (scrollTopButton) {
        scrollTopButton.addEventListener('click', function () {
            window.scrollTo({top: 0, behavior: 'smooth'});
        });
    }

    var scrollBottomButton = document.getElementById('scroll-bottom-btn');
    if (scrollBottomButton) {
        scrollBottomButton.addEventListener('click', function () {
            window.scrollTo({top: document.documentElement.scrollHeight, behavior: 'smooth'});
        });
    }
});