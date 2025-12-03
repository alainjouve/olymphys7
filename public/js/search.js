function lanceRecherche() {
    connectToDatabase();
    document.getElementById("result").innerHTML = "Recherche en cours ...";
    showResult()
}

function showResult() {
    var str = document.getElementById('keyword').value;
    // vérification triviale
    if (str.length == 0) {
        document.getElementById("result").innerHTML = "Rien à rechercher ...";
        return;
    }
    // on doit travailler !
    var nbRes = document.getElementById("nb-result").value
    var fullword = document.getElementById("fullword").checked

    // appel via AJAX
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        if (this.readyState == 4 && this.status == 200) {
            document.getElementById("result").innerHTML = "Résultat de la recherche : " + this.responseText;
        }
    };
    var args = "kw=" + str + "&nbRes=" + nbRes + "&fullword=" + fullword
    xmlhttp.open("GET", "php_cherche_texte.php?" + args, true);
    xmlhttp.send();
}

// Cette fonction est là pour essayer de pallier la lenteur de la première recherche sur EX2
// Cette lenteur est probablement liée à la mise en cache sur le serveur des fichiers texte
// dans lesquels s'effectue la recherche.
// on lance une requête asynchrone sur un fichier php qui ne fait rien d'autre que de lire les textes.
// Cette foncton est lancée au chargement de cette page.
// Sur EX2, la mise en cache des 500 fichiers texte met environ 10s.
// Le fait de lancer la lecture en // sur le serveur doit permettre la mise en cache pendant que
// l'utilisateur interagit avec la page courante.
function preloadTexts() {
    // appel via AJAX de la lecture des textes
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", "php_loadTexts.php", true);
    xmlhttp.send();
}

function toggleHelp() {
    if (!document.getElementById('chkHelp').checked) {
        document.getElementById('divAide').style.display = "none"
    } else {
        document.getElementById('divAide').style.display = "block"
    }
}

// lancement asynchrone de la lecture des textes
//preloadTexts()
