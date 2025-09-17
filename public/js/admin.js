//Pour que les noms de fichiers s'affichent dans le champ de choix de fichier lors d'un upload
var selectElement=null;
window.onload = function() {
    selectElement = document.getElementById("OdpfEditionsPassees_affiche_file");
    if(selectElement!==null) {
        selectElement.addEventListener("change", (event) => {
            let inputFile = event.currentTarget;
            $(inputFile).parent().find('.custom-file-label').html(inputFile.files[0].name);
            var sizeFile = document.getElementsByClassName('input-group-text');
            //$(sizeFile[1]).html(inputFile.files[0].size);
            })
    }
}



$(document).ready(function () {


    $('#modalinfo').modal('show');
});

function choixedition(s){//pour la planche contact
    var ideditionpassee=s.value;
    console.log(ideditionpassee);
    $.ajax({
        url: '/photos/choixeditionpassee',
        type: "POST",
        data: {idEdPassee:ideditionpassee},

        success: function () {
            location.reload();
        },

        error: function (data) {
            alert("Error while submitting Data");
        },
    })


}
function imClick(id) {
    var chkBox = document.getElementById('form_photo-' + id);
    if(chkBox) {
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
    if(chkBoxes) {
        nbPhSel = 0;
        document.getElementById('sp_nbPhotosSel').innerHTML = nbPhSel
        for (c of chkBoxes) {
            c.checked = false
        }
    }

}
$(document).ready(function () {
    var checkboxes = document.querySelectorAll('input[type="checkbox"]');
    if(checkboxes) {
        checkboxes.forEach((checkbox) => {
            checkbox.addEventListener("change", (event) => {
                var checkboxeschecked = document.querySelectorAll('input[type="checkbox"]:checked');
                document.getElementById('sp_nbPhotosSel').innerHTML = checkboxeschecked.length;
            })

        });
    }

    var btup=document.getElementById('scroll-top-btn')
    if(btup!==null) {
        btup.addEventListener('click', function () {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    var btdown=document.getElementById('scroll-bottom-btn')
        if(btdown!==null) {
        btdown.addEventListener('click', function () {
                window.scrollTo({
                    top: document.body.scrollHeight,
                    behavior: 'smooth'
                });
            });
    }
})
