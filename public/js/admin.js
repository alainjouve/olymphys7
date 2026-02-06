//Pour que les noms de fichiers s'affichent dans le champ de choix de fichier lors d'un upload
var selectElement = null;
window.onload = function () {
    selectElement = document.getElementById("OdpfEditionsPassees_affiche_file");
    if (selectElement !== null) {
        selectElement.addEventListener("change", (event) => {
            let inputFile = event.currentTarget;
            $(inputFile).parent().find('.custom-file-label').html(inputFile.files[0].name);
            var sizeFile = document.getElementsByClassName('input-group-text');
            //$(sizeFile[1]).html(inputFile.files[0].size);
        })
    }
    if (document.getElementById('edit-Cadeaux-form')) {
        //window.removeEventListener('beforeunload', warnOnLeave);
        //formIsDirty = true;
        window.addEventListener('beforeunload', (event) => {
            event.stopImmediatePropagation();
        }, true);
        window.onbeforeunload = null;

        // Désactive l'avertissement natif d'EasyAdmin 3+
        document.getElementById('edit-Cadeaux-form').classList.remove('ea-edit-form', 'ea-new-form');

    }
}


$(document).ready(function () {


    $('#modalinfo').modal('show');
});

function choixedition(s) {//pour la planche contact
    var ideditionpassee = s.value;
    console.log(ideditionpassee);
    var url = '/public/index.php/photos/choixeditionpassee'
    if (window.location.href.includes('localhost')) {
        url = '/photos/choixeditionpassee'
    }

    $.ajax({
        url: url,
        type: "POST",
        data: {idEdPassee: ideditionpassee},

        success: function () {
            window.location.reload();
        },

        error: function (data) {
            alert("Error while submitting Data");
        },
    })


}

function imClick(id) {
    var chkBox = document.getElementById('form_photo-' + id);
    if (chkBox) {
        chkBox.checked = !chkBox.checked


// Création de l'événement
        const changeEvent = new Event('change', {
            bubbles: true,
            cancelable: true
        });

// Dispatch de l'événement
        chkBox.dispatchEvent(changeEvent);
    }

}

function raz_sel() {
    var chkBoxes = document.getElementsByClassName("form-check-input");
    if (chkBoxes) {
        nbPhSel = 0;
        document.getElementById('sp_nbPhotosSel').innerHTML = nbPhSel
        for (c of chkBoxes) {
            c.checked = false
        }
        document.getElementById('form_telecharger').disabled = true
    }

}

$(document).ready(function () {
    var checkboxes = document.querySelectorAll('input[type="checkbox"]');
    if (checkboxes) {
        checkboxes.forEach((checkbox) => {
            checkbox.addEventListener("change", (event) => {
                var checkboxeschecked = document.querySelectorAll('input[type="checkbox"]:checked');
                document.getElementById('sp_nbPhotosSel').innerHTML = checkboxeschecked.length;
                document.getElementById('form_telecharger').disabled = (checkboxeschecked.length == 0)
            })
        });
    }
    if (document.getElementById('form_telecharger') !== null) {
        document.getElementById('form_telecharger').disabled = true;
    }

    var btup = document.getElementById('scroll-top-btn')
    if (btup !== null) {
        btup.addEventListener('click', function () {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    var btdown = document.getElementById('scroll-bottom-btn')
    if (btdown !== null) {
        btdown.addEventListener('click', function () {
            window.scrollTo({
                top: document.body.scrollHeight,
                behavior: 'smooth'
            });
        });
    }
    var inputPhoto = document.getElementById('Photos_photoFile')
    var image = document.getElementById('photo')
    var nom_photo = document.getElementById('nom_photo')
    if (inputPhoto !== null) {
        inputPhoto.addEventListener('change', function (event) {
            image.src = inputPhoto.value;

            const file = event.target.files[0]; // Récupère le fichier sélectionné
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();

                // Lors de la lecture du fichier
                reader.onload = function (e) {
                    // Met à jour la source de l'image avec le contenu lu
                    image.src = e.target.result;
                    nom_photo.innerHTML = event.target.files[0].name;
                };

                // Lecture du fichier sous forme d'URL de données
                reader.readAsDataURL(file);
            } else {
                // Si le fichier n'est pas une image ou si aucun fichier n'est sélectionné
                image.src = '';
                inputPhoto.value = null;
                alert("Cette photo n'est pas conforme");
            }

        });
    }


})

function setSujetPhoto(id) {
    var input = document.getElementById('sujet_photo-' + id)
    var typeSujet = input.value
    var url = '/public/index.php/photos/set_type_sujet_photo'
    if (window.location.href.includes('localhost')) {
        url = '/photos/set_type_sujet_photo'
    }
    $.ajax({
        url: url,
        type: "POST",
        data: {idPhoto: id, idSujetPhoto: typeSujet},


        error: function (data) {
            alert("Error while submitting Data");
        },

    })

}

function choixtypesujet(s)//Permet de sélectionner le type de sujet des photos de la table des photos
{
    //var select=document.getElementById('choixtypesujet');
    var typesujet = s.value;
    var url = '/public/index.php/photos/choix_type_sujet_photo'
    if (window.location.href.includes('localhost')) {
        url = '/photos/choix_type_sujet_photo'
    }
    $.ajax({
        url: url,
        type: "POST",
        data: {idSujetPhoto: typesujet},
        success: function () {
            window.location.reload();
        },

        error: function (data) {
            alert("Error while submitting Data");
        },
    })
}


function choixEquipe(s)//Permet de sélectionner le type de sujet des photos de la table des photos
{
    //var select=document.getElementById('choixtypesujet');
    var idEquipe = s.value;
    var url = '/public/index.php/photos/choix_equipe_photo'
    console.log(idEquipe);
    if (window.location.href.includes('localhost')) {
        url = '/photos/choix_equipe_photo'
    }
    $.ajax({
        url: url,
        type: "POST",
        data: {idEquipe: idEquipe},
        success: function () {
            window.location.reload();
        },

        error: function (data) {
            alert("Error while submitting Data");
        },

    })


}

function changelotcadeau(sel, idlot, type) {

    console.log(sel.value)
    var numlot = sel.value;
    var url = '/cadeaux/changecadeau'
    //formIsDirty = true;
    $.ajax({
        url: url,
        type: "PATCH",//Pour que la requête de déclanche pas l'erreur MethodNotAllowedHttpException
        data: {
            idlot: idlot,
            numlot: numlot,
            type: type
        },
        success: function () {
            window.location.reload();
        },

        error: function (data) {
            alert("Error while submitting Data");
        },

    })
}

function changecontenucadeau(cont, idlot, type) {

    console.log(cont.value)
    var contenu = cont.value;
    var url = '/cadeaux/changecadeau'
    //formIsDirty = true;
    $.ajax({
        url: url,
        type: "PATCH",//Pour que la requête ne déclenche pas l'erreur MethodNotAllowedHttpException
        data: {
            idlot: idlot,
            contenu: contenu,
            type: type
        },
        success: function () {
            window.location.reload();
        },

        error: function (data) {
            alert("Error while submitting Data");
        },

    })
}

function changeequipecadeau(eq, idlot, type) {

    console.log(eq.value)
    var idEquipe = eq.value;

    var url = '/cadeaux/changecadeau'
    $.ajax({
        url: url,
        type: "PATCH",//Pour que la requête de déclanche pas l'erreur MethodNotAllowedHttpException
        data: {
            idlot: idlot,
            idEquipe: idEquipe,
            type: type
        },
        success: function () {
            window.location.reload();
        },

        error: function (data) {
            alert("Error while submitting Data");
        },

    })
}

function changefournisseurcadeau(four, idlot, type) {

    console.log(four.value)
    var fournisseur = four.value;

    var url = '/cadeaux/changecadeau'
    //formIsDirty = true;
    $.ajax({
        url: url,
        type: "PATCH",//Pour que la requête de déclanche pas l'erreur MethodNotAllowedHttpException
        data: {
            idlot: idlot,
            fournisseur: fournisseur,
            type: type

        },
        success: function () {
            window.location.reload();
        },

        error: function (data) {
            alert("Error while submitting Data");
        },

    })
}

function changemontantcadeau(mont, idlot, type) {

    console.log(mont.value)
    var montant = mont.value;
    var url = '/cadeaux/changecadeau'
    //formIsDirty = true;
    $.ajax({
        url: url,
        type: "PATCH",//Pour que la requête de déclanche pas l'erreur MethodNotAllowedHttpException
        data: {
            idlot: idlot,
            montant: montant,
            type: type
        },
        success: function () {
            window.location.reload();
        },

        error: function (data) {
            alert("Error while submitting Data");
        },

    })
}

function changeraccourcicadeau(rac, idlot, type) {//change le raccourci d'un lot de cadeau

    console.log(rac.value)
    var raccourci = rac.value;
    var url = '/cadeaux/changecadeau'
    //formIsDirty = true;
    $.ajax({
        url: url,
        type: "PATCH",//Pour que la requête de déclanche pas l'erreur MethodNotAllowedHttpException
        data: {
            idlot: idlot,
            raccourci: raccourci,
            type: type
        },
        success: function () {
            window.location.reload();
        },

        error: function (data) {
            alert("Error while submitting Data");
        },

    })
}