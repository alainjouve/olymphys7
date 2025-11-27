<?php

namespace App\Controller;

use App\Entity\Odpf\OdpfFichierspasses;
use Doctrine\Persistence\ManagerRegistry;
use Mpdf\Tag\Article;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SearchController extends AbstractController
{
    private ManagerRegistry $doctrine;


    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;

    }
    #[Route("/search", name:"search")]
 public function search(Request $request) : Response
 {
            $repertoire='search/textes';
            $form=$this->createFormBuilder()
                ->add('text',TextType::class,[

                    'required'=>true,

                ])
                ->add('fullword',CheckboxType::class,[
                    'label'=>'',
                    'required'=>false,
                    'data'=>true,

                ])
                ->add('nbres',ChoiceType::class,[
                    'label'=>'Nombre',
                    'data'=> '25',
                    'choices'=>['25'=>25,'50'=>'50','100'=>'100'],
                    'attr'=>['style'=>'width: 100px']
                ])
                ->add('submit', SubmitType::class,
                [
                    'label' => 'Lance recherche ...',
                ])
                ->getForm();
           $form->handleRequest($request);
           if ($form->isSubmitted() && $form->isValid()) {

               $textes_a_rechercher=$form->get('text')->getData();
               $fullword=$form->get('fullword')->getData();
               $nsecmax=$form->get('nbres')->getData();

               if (!is_dir($repertoire)) {

                   return $this->redirectToRoute('search');
               }

               // le tableau des mots-clés à rechercher
               // on découpe la chaîne tapée par l'utiisateur en utilisant l'espace comme séparateur
               // et on supprime les chaînes vides (explode retourne des "" pour les espaces ...)
               $kwArray = array_filter(
                   explode(' ', $textes_a_rechercher),
                   function ($k) {
                       return $k != '';
                   }
               );

               // le tableau principal
               // C'est une liste d'associations <nom_fichier> => <tableau assoc. keyword => nbr occurences>
               $assocFileKWCount = [];

               // pour le chronmétrage de la recherche
               $depart = microtime(true);

               // Exploration du répertoire des textes
               $scandir = scandir($repertoire);
               // balayage des fichiers
               foreach ($scandir as $fichier) {
                   // élimination des fichiers spéciaux
                   if ($fichier[0] == '.') continue;
                   // récupération du contenu du fichier texte
                   $filesContents = file_get_contents($repertoire.'/' . $fichier);

                   // on recherche chaque mot-clé dans le texte avec preg_match_all
                   // si on le trouve, on l'associe au nom de fichier avec son nombre d'occurences
                   foreach ($kwArray as $kw) {
                       // recherche sur des mots entiers ?
                       if ($fullword == "true") {
                           // ~ est le délimiteur de l'expression régulière, \b est le délimiteur de mot,
                           // ui à la fin est là pour matcher en UTF8 (pour les accents) sans tenir
                           // compte de la casse. Voir la doc ici :
                           //  https://www.php.net/manual/en/regexp.introduction.php
                           $req = "~\b$kw\b~ui";
                       } else {
                           $req = "~$kw~ui";
                       };
                       $success = @preg_match_all($req, $filesContents, $out, PREG_PATTERN_ORDER);
                       if ($success !== false) {           // recherche sans erreur
                           if (count($out[0]) > 0) {       // mot trouvé !
                               $assocFileKWCount[$fichier][$kw] = count($out[0]);
                           }
                       }
                   }
               }
               // on ajoute pour chaque fichier le nombre total d'occurences de tous les mots clés
               // avec "nb match" comme mot clé.
               // "nb match" ne peut pas correspondre à un mot clé utilisateur, car contenant " "
               foreach ($assocFileKWCount as &$assocKWCount) {
                   $nbTotMatch = 0;
                   foreach ($assocKWCount as $c) {
                       $nbTotMatch += $c;
                   }
                   $assocKWCount["nb match"] = $nbTotMatch;
               }
               // tri du tableau précédent selon le nombre total d'occurences décroissant
               uasort($assocFileKWCount, function ($a, $b) {
                   if ($a["nb match"] > $b["nb match"]) return -1;
                   if ($a["nb match"] < $b["nb match"]) return 1;
                   return 0;
               });
               // on refait un tri en mettant en tête les fichiers avec le plus de mots clés trouvés
               uasort($assocFileKWCount, function ($a, $b) {
                   if (count($a) > count($b)) return -1;
                   if (count($a) < count($b)) return 1;
                   return 0;
               });

               // fin de la recherche : calcul de la durée totale
               $fin = microtime(true);
               $temps = $fin - $depart;

               // fabrication du contenu html exposant le résutat de la recherche
               // en limitant le nombre de sections à nsecmax
               echo "<span style='font-style:italic;'>" .
                   count($assocFileKWCount) . " fichiers(s) trouvé(s) en " .
                   number_format($temps, 3, ',') .
                   " s</span><br>";
               // $nsec = $nsecmax;
               $idxDoc = 0;
               foreach ($assocFileKWCount as $fichier => $kwCount) {
                   // $nsec--;
                   $idxDoc += 1;
                   if ($idxDoc > $nsecmax) {
                       echo "<hr><br>... limite de $nsecmax résultats affichés atteinte ... <br><hr>";
                       break;
                   }

                   // nettoyage du nom de fichier pour ne garder que le titre du rapport
                   $titre = explode('-', $fichier, $limit = 5); // nom rapport = dernier élément
                   $titre = substr(end($titre), 0, -4);    // retrait de ".txt"
                   $titre = str_replace('-', ' ', $titre);  // élimination des "-"

                   echo "<h3>$idxDoc -  $titre</h3><br>"; // le nom du fichier
                   // Affichage de l'édition
                   [$edition, $reste] = explode('-', $fichier, $limit = 2);
                   echo "Édition n°$edition - ";

                   //  fabrication de l'url du fichier pdf archivé
                   // encodage du nom du fichier comme portion d'url valable
                   $urlTitre = rawurlencode($fichier);
                   $pdfUrl = "https://www.olymphys.fr/public/odpf/odpf-archives/" .
                       $edition . "/fichiers/memoires/publie/" .
                       substr($urlTitre, 0, -3) . "pdf";

                   // écriture de la ligne d'info sur les termes trouvés et le lien vers le pdf
                   echo "</span>&nbsp;<a href=$pdfUrl target='_blank'>afficher le mémoire</a><br>";
                   echo "<span style='font-style:italic;'>";
                   echo "<b>mots-clés trouvés : <span style='background-color:yellow;'>" .
                       count($kwCount) - 1 . "/" .  count($kwArray) .
                       "</span></b>&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;";
                   echo  $kwCount["nb match"] . " occurences : ";
                   foreach ($kwCount as $kw => $count) {
                       if ($kw == "nb match") continue;
                       echo "$kw ($count)&nbsp;";
                   }
                   echo "</span><br>";
               }
           }


        return $this->render('search/search.html.twig', ['form' => $form->createView()]);


 }


}