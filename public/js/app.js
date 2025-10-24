$(document).ready(function () {

    $("#form1").on('change', function () {
        $("#form1").submit();
    });

    var inputPhoto=document.getElementById('photos_photoFiles')
    var image=document.getElementById('photo_preview')
    var affichephotos=document.getElementById('affichephotos')
        if(inputPhoto!== null) {

        inputPhoto.addEventListener('change', function (event) {

            image.src = inputPhoto.value;
            var files = event.target.files; // Récupère le fichier sélectionné
            const paragraph = document.createElement('p');
            paragraph.innerHTML = '';
            affichephotos.innerHTML='';
            var container=document.createElement('div');
            container.className = 'row';
            affichephotos.appendChild(container);
            for (var i=0; i < files.length; i++) {

                const file = files[i];
                /*if (!file.type.startsWith('image/')) {
                    alert("Veuillez sélectionner uniquement des images");
                    continue;
                }*/
                console.log(file);
                var nameCheckbox=file.name.split('.')[0];

                //image=document.getElementById('photo_preview' + i);
                const image = document.createElement('img');
                const checkbox=document.createElement("input");
                checkbox.type = 'checkbox';
                checkbox.name = 'checkbox-'+ nameCheckbox;
                checkbox.label = 'Cocher pour téléverser';
                checkbox.checked = !checkbox.checked;
                image.width = 220;
                image.setAttribute('name', file.name);
                const reader = new FileReader();
                reader.onload = function(e) {

                    var div0=document.createElement('div');
                    div0.className='col';
                    var div1 =document.createElement( "div");
                    div1.setAttribute('class', 'card');
                    div1.style.width = '250px';
                    var div2=document.createElement( "div");
                    div2.setAttribute('class', 'card-header');
                    div2.innerHTML='photo-'+file.name;
                    var div3=document.createElement( "div");
                    div3.setAttribute('class', 'card-body');
                    container.append(div0);
                    div0.appendChild(div1);
                    div1.appendChild(div2);
                    div1.appendChild(div3);
                    image.src = e.target.result;
                    div3.appendChild(image);
                    div3.appendChild(checkbox);

                };

                reader.readAsDataURL(file);
            }





        })
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
    tableau.scrollTop = parseInt(localStorage['scrollPositionY']);
    tableau.scrollLeft = parseInt(localStorage['scrollPositionX']);
    window.scrollTo(0, parseInt(localStorage['scrollWindowY']));

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
$('#modalenvoiconseilscn').on('show.bs.modal', function (event) {//envoi des recommandations jury du cn
    var button = $(event.relatedTarget) // Button that triggered the modal
    var recipient = button.data('id');
    console.log(recipient)
    var modal = $(this)
    modal.find('.modal-title').text('Attention')
    modal.find('.modal-body input').val(recipient)
});
$('#modalenvoiconseils').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget) // Button that triggered the modal
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
