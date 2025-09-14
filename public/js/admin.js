//Pour que les noms de fichiers s'affichent dans le champ de choix de fichier lors d'un upload
var selectElement=null;
window.onload = function() {
    selectElement = document.getElementById("OdpfEditionsPassees_affiche_file");
    selectElement.addEventListener("change", (event) => {
        let inputFile = event.currentTarget;
        $(inputFile).parent().find('.custom-file-label').html(inputFile.files[0].name);
        var sizeFile = document.getElementsByClassName('input-group-text')
        //$(sizeFile[1]).html(inputFile.files[0].size);
    });
}
$(document).ready(function () {


    $('#modalinfo').modal('show');
});

function choixedition(s){
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