<?php

// Utils\StringUtils.php 
// MT 01/07/2025

// ++++ Fichier introduit comme RUSTINE TEMPORAIRE ???? ++++
// pour corriger l'affichage incorrect des caractères accentués des chaînes binaires
// Bug apparu en ligne fin juin 2025, alors que tout marche en local

// La méthode de classe convertBinaryString permet de sécuriser les chaînes binaires
// qui provoquent des affichages incorrects sur le site, et peut-être davantage ...


namespace App\Utils;

class StringUtils
{
    // méthode de classe de sécurisation des chaînes :
    // Si la chaîne n'est pas en UTF8 (multiByte), alors on suppose qu'elle est en LATIN1
    // et on retourne la chaîne convertie en UTF8
    public static function convertBinaryString(?string $string)
    {
        if (mb_check_encoding($string)) {
            return $string;
        } else {
            return iconv("LATIN1", "UTF-8//TRANSLIT//IGNORE", $string);
        }
    }
}
