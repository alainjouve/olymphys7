<?php

namespace App\Controller;

use App\Entity\Attributions;
use App\Entity\Cadeaux;
use App\Entity\Centrescia;
use App\Entity\Cia\HorairesSallesCia;
use App\Entity\Cia\JuresCia;
use App\Entity\Cia\NotesCia;
use App\Entity\Cia\RangsCia;
use App\Entity\Coefficients;
use App\Entity\Edition;
use App\Entity\Elevesinter;
use App\Entity\Equipes;
use App\Entity\Equipesadmin;
use App\Entity\Jures;
use App\Entity\Notes;
use App\Entity\Phrases;
use App\Entity\Prix;
use App\Entity\RecommandationsJuryCn;
use App\Entity\Repartprix;
use App\Entity\Uai;
use App\Entity\Visites;
use App\Form\EquipesType;
use App\Form\PrixExcelType;
use App\Form\PrixType;
use App\Form\RecommandationsCnType;
use App\Service\Mailer;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\UnicodeString;


class SecretariatjuryController extends AbstractController
{
    private RequestStack $requestStack;
    private ManagerRegistry $doctrine;

    public function __construct(RequestStack $requestStack, ManagerRegistry $doctrine)
    {
        $this->requestStack = $requestStack;
        $this->doctrine = $doctrine;
    }


    #[IsGranted('ROLE_SECRETARIAT_JURY')]
    #[Route("/secretariatjury/accueil", name: "secretariatjury_accueil")]
    public function accueil(): Response
    {
        $em = $this->doctrine->getManager();
        $edition = $this->requestStack->getSession()->get('edition');

        if (new \DateTime('now') < $this->requestStack->getSession()->get('edition')->getDateouverturesite()) {

            $edition = $this->doctrine->getRepository(Edition::class)->findOneBy(['ed' => $edition->getEd() - 1]);//pour la durée qui suit le cn
        }
        $repositoryEquipesadmin = $this->doctrine->getRepository(Equipesadmin::class);
        $repositoryEleves = $this->doctrine->getRepository(Elevesinter::class);
        $repositoryUai = $this->doctrine->getRepository(Uai::class);//les équipes de la table équipe
        /* $listEquipes = $repositoryEquipesadmin->createQueryBuilder('e')//Cette partie corrigée le 16-12-24 :
             ->select('e')
             ->andWhere('e.edition =:edition')
             ->andWhere('e.numero <:numero')
             ->setParameters(['edition' => $edition, 'numero' => 100])
             ->andWhere('e.selectionnee= TRUE')
             ->orderBy('e.lettre', 'ASC')
             ->getQuery()
             ->getResult();*/
        $listEquipes = $this->doctrine->getRepository(Equipes::class)->createQueryBuilder('e')
            ->leftJoin('e.equipeinter', 'eq')
            ->addOrderBy('eq.lettre', 'ASC')
            ->getQuery()->getResult();
        if ($listEquipes == []) {//Pour travailler sur les données de l'édition précédente avant le concours, les équipes ne sont pas connues alors

            $listEquipes = $this->doctrine->getRepository(Equipes::class)->findAll();

        }
        $lesEleves = [];
        $lycee = [];

        foreach ($listEquipes as $equipe) {
            $lettre = $equipe->getEquipeinter()->getLettre();
            $lesEleves[$lettre] = $repositoryEleves->findBy(['equipe' => $equipe->getEquipeinter()]);
            $uai = $equipe->getEquipeinter()->getUai();
            $lycee[$lettre] = $repositoryUai->findBy(['uai' => $uai]);

        }
        $tableau = [$listEquipes, $lesEleves, $lycee];
        $session = $this->requestStack->getSession();
        $session->set('tableau', $tableau);
        $content = $this->renderView('secretariatjury/accueil.html.twig',
            array(''));

        return new Response($content);
    }

    #[IsGranted('ROLE_SECRETARIAT_JURY')]
    #[Route("/secretariatjury/accueil_jury", name: "secretariatjury_accueil_jury")]
    public function accueilJury(): Response
    {
        $tableau = $this->requestStack->getSession()->get('tableau');
        $listEquipes = $tableau[0];
        $lesEleves = $tableau[1];
        $lycee = $tableau[2];
        $repositoryUser = $this->doctrine
            ->getManager()
            ->getRepository(User::class);
        $prof1 = [];
        $prof2 = [];
        foreach ($listEquipes as $equipe) {
            $lettre = $equipe->getLettre();
            $idprof1 = $equipe->getEquipeinter()->getIdProf1();
            $prof1[$lettre] = $repositoryUser->findBy(['id' => $idprof1]);
            $idprof2 = $equipe->getEquipeinter()->getIdProf2();
            $prof2[$lettre] = $repositoryUser->findBy(['id' => $idprof2]);
        }

        $content = $this->renderView('secretariatjury/accueil_jury.html.twig',
            array('listEquipes' => $listEquipes,
                'lesEleves' => $lesEleves,
                'prof1' => $prof1,
                'prof2' => $prof2,
                'lycee' => $lycee));

        return new Response($content);
    }

    #[IsGranted('ROLE_SECRETARIAT_JURY')]
    #[Route('/secretariatjury/vueglobale', name: "secretariatjury_vueglobale")]
    public function vueglobale(): Response
    {
        $em = $this->doctrine->getManager();
        $repositoryNotes = $this->doctrine->getRepository(Notes::class);

        $repositoryJures = $this->doctrine->getRepository(Jures::class);
        $listJures = $repositoryJures->findAll();

        $repositoryEquipes = $this->doctrine->getRepository(Equipes::class);
        $listEquipes = $repositoryEquipes->findAll();

        $nbre_equipes = 0;
        $progression = [];
        $nbre_jures = 0;
        foreach ($listEquipes as $equipe) {
            $nbre_equipes = $nbre_equipes + 1;
            $id_equipe = $equipe->getId();
            $lettre = $equipe->getEquipeinter()->getLettre();
            $nbre_jures = 0;
            foreach ($listJures as $jure) {
                $id_jure = $jure->getId();
                $nbre_jures += 1;
                //vérifie l'attribution du juré ! 0 si assiste, 1 si lecteur sinon Null
                $attrib = $repositoryJures->getAttribution($jure);;
                //récupère l'évaluation de l'équipe par le juré dans $note pour l'afficher
                if (!isset($attrib[$lettre])) {
                    $progression[$nbre_equipes][$nbre_jures] = 'ras';

                } elseif ($attrib[$lettre] == 1) {
                    $note = $repositoryNotes->EquipeDejaNotee($id_jure, $id_equipe);
                    $progression[$nbre_equipes][$nbre_jures] = (is_null($note)) ? '*' : $note->getTotalPoints();
                } else {
                    $note = $repositoryNotes->EquipeDejaNotee($id_jure, $id_equipe);
                    $progression[$nbre_equipes][$nbre_jures] = (is_null($note)) ? '*' : $note->getPoints();
                }
            }
        }

        $content = $this->renderView('secretariatjury/vueglobale.html.twig', array(
            'listJures' => $listJures,
            'listEquipes' => $listEquipes,
            'progression' => $progression,
            'nbre_equipes' => $nbre_equipes,
            'nbre_jures' => $nbre_jures,
        ));

        return new Response($content);
    }

    #[IsGranted('ROLE_COMITE')]
    #[Route("/secretariatjury/modifnotejurecn,{idequipe}, {idjure}", name: "modifnotejurecn")]
    public function modifnotejurecn(Request $request, $idequipe, $idjure)//Dans le cas où le jury c'est trompé d'équipe en examinant une équipe qui n'est pas de son jury mais en notant par mégarde  une autre équipe de son jury
    {
        //Il faut ajouter l'équipe au juré(qui sera considéré par défaut examinateur simple E)
        $equipe = $this->doctrine->getRepository(Equipes::class)->find($idequipe);
        $jure = $this->doctrine->getRepository(Jures::class)->find($idjure);
        $qb = $this->doctrine->getRepository(Equipes::class)->createQueryBuilder('e');


        $noteequipe = $this->doctrine->getRepository(Notes::class)->findOneBy(['jure' => $jure, 'equipe' => $equipe]);

        //Il faut transporter les notes à la bonne équipe
        $form = $this->createFormBuilder()
            ->add('equipe', EntityType::class, [
                'class' => Equipes::class,
                'query_builder' => $qb,
                'label' => 'Equipe qui a été réellement évaluée par le juré',
                'placeholder' => '',
            ])
            ->add('valider', SubmitType::class, ['label' => 'Valider'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() and $form->isValid()) {
            if ($noteequipe !== null) {
                $nllEquipe = $form->get('equipe')->getData();//equipe B dans laquelle la note de l'équipe A doivent être transférées
                $notenllequipe = new Notes();//notes de l'équipe B dans laquelle la note de l'équipe A doivent être transférées
                $notenllequipe->setJure($jure);  //transfert de la note de l'équipe A vers l'équipe B
                $notenllequipe->setEquipe($nllEquipe);
                $notenllequipe->setEcrit($noteequipe->getEcrit());
                $notenllequipe->setExper($noteequipe->getExper());
                $notenllequipe->setDemarche($noteequipe->getDemarche());
                $notenllequipe->setOral($noteequipe->getOral());
                $notenllequipe->setOrigin($noteequipe->getOrigin());
                $notenllequipe->setRepquestions($noteequipe->getRepquestions());
                $notenllequipe->setWgroupe($noteequipe->getWgroupe());
                $coefficients = $this->doctrine->getRepository(Coefficients::class)->findOneBy(['id' => 1]);
                $notenllequipe->setCoefficients($coefficients);
                $total = $notenllequipe->getPoints();
                $notenllequipe->setTotal($total);
                $this->doctrine->getManager()->persist($notenllequipe);//enregistrement de la notes de l'équipe B

                $attribution = $this->doctrine->getRepository(Attributions::class)->findOneBy(['equipe' => $equipe, 'jure' => $jure]);
                $nllattribution = new Attributions();
                $nllattribution->setEquipe($nllEquipe); //affectation de l'équipe B au juré
                $nllattribution->setJure($jure);
                $nllattribution->setEstLecteur(0);//Le juré n'est qu'examinateur dans ce cas.
                $this->doctrine->getManager()->remove($attribution);//supression de l'équipe A dans la liste équipes du juré
                $this->doctrine->getManager()->persist($nllattribution);
                $this->doctrine->getManager()->persist($notenllequipe);//hydratation de la base
                //$jure->removeNotej($noteequipe);//Suppresion de la note de l'équipe A de ce juré
                $this->doctrine->getManager()->remove($noteequipe);//suppresion de la notes de l'équipe A
                $this->doctrine->getManager()->persist($jure);//enregistrement du juré
                $this->doctrine->getManager()->flush();
            } else {

                $this->requestStack->getSession()->set('info', 'Le juré n\'a pas encore noté l\'équipe, veuillez modifier l\'affectation de ce juré dans le tableau de gestion des jurés');
            }
            return $this->redirectToRoute('secretariatjury_vueglobale');
        }
        return $this->render('secretariatjury/modifNoteJure.html.twig', ['form' => $form->createView(), 'equipe' => $equipe, 'jure' => $jure]);

    }

    #[IsGranted('ROLE_SECRETARIAT_JURY')]
    #[Route("/secretariatjury/classement", name: "secretariatjury_classement")]
    public function classement(): Response//L'appel de cette fonction permet de mettre à jour la table équipes avec le rang et le total de chaque équipe
    {
        // affiche les équipes dans l'ordre de la note brute
        $em = $this->doctrine->getManager();

        $repositoryEquipes = $this->doctrine->getRepository(Equipes::class);

        $coefficients = $this->doctrine->getRepository(Coefficients::class)->findOneBy(['id' => 1]);

        $listEquipes = $repositoryEquipes->createQueryBuilder('e')
            ->leftJoin('e.equipeinter', 'eq')
            ->orderBy('eq.lettre', 'ASC')
            ->getQuery()->getResult();

        foreach ($listEquipes as $equipe) {
            $listesNotes = $equipe->getNotess();

            $nbre_notes = count($equipe->getNotess());//a la place de $equipe->getNbNotes();

            $nbre_notes_ecrit = 0;
            $points_ecrit = 0;
            $points = 0;
            $moyenne_ecrit = 0;
            if ($nbre_notes == 0) {//L'équipe n'est pas encore notée
                $equipe->setTotal(0);
                $em->persist($equipe);
                $em->flush();
            } else {//calcul de la moyenne des notes de l'équipe
                foreach ($listesNotes as $note) {

                    $points = $points + $note->getPoints();//Calcul de la somme des points sans les notes d'écrit

                    if ($note->getEcrit() != 0) {

                        $nbre_notes_ecrit = $nbre_notes_ecrit + 1;
                    }//Détermination du nombre de notes d'écrit
                    $points_ecrit = $points_ecrit + $note->getEcrit() * $coefficients->getEcrit();//Détermination du total des poins de l'écrit

                }

                if ($nbre_notes_ecrit != 0) {
                    $moyenne_ecrit = $points_ecrit / $nbre_notes_ecrit;
                }
                //met à jour le nb de notes et le total
                $equipe->setNbNotes($nbre_notes);
                $equipe->setTotal(($points / $nbre_notes) + $moyenne_ecrit);//
                $em->persist($equipe);
                $em->flush();
            }

        }

        $nbre_equipes = count($listEquipes);
        /*$qb = $repositoryEquipes->createQueryBuilder('e');
        $qb->select('COUNT(e)');
        try {
            $nbre_equipes = $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException) {
        }*/

        $classement = $repositoryEquipes->classement(0, 0, $nbre_equipes);//

        $i = 1;
        foreach ($classement as $equipe) {//Enregistrement du rang de chaque équipe dans la table équipes
            $equipe->setRang($i);
            $em->persist($equipe);
            $i = $i + 1;

        }

        $em->flush();
        $content = $this->renderView('secretariatjury/classement.html.twig',
            array('classement' => $classement)
        );
        return new Response($content);
    }

    #[IsGranted('ROLE_SECRETARIAT_JURY')]
    #[Route("/secretariatjury/lesprix", name: "secretariatjury_lesprix")]
    public function lesprix(): Response
    { //affiche la liste des prix prévus
        $em = $this->doctrine->getManager();

        $repositoryPrix = $em->getRepository(Prix::class);

        $ListPremPrix = $repositoryPrix->findBy(['niveau' => '1er']);

        $ListDeuxPrix = $repositoryPrix->findBy(['niveau' => '2ème']);

        $ListTroisPrix = $repositoryPrix->findBy(['niveau' => '3ème']);

        $content = $this->renderView('secretariatjury/lesprix.html.twig',
            array('ListPremPrix' => $ListPremPrix,
                'ListDeuxPrix' => $ListDeuxPrix,
                'ListTroisPrix' => $ListTroisPrix)
        );
        return new Response($content);
    }

    #[IsGranted('ROLE_SECRETARIAT_JURY')]
    #[Route("/secretariatjury/modifier_prix/{id_prix}", name: "secretariatjury_modifier_prix", requirements: ["id_prix" => "\d{1}|\d{2}"])]
    public function modifier_prix(Request $request, $id_prix): Response
    { //permet de modifier le niveau d'un prix(id_prix), modifie alors le 'repartprix" (répartition des prix)

        $em = $this->doctrine->getManager();

        $repositoryPrix = $em->getRepository(Prix::class);

        $repositoryRepartprix = $em->getRepository(Repartprix::class);

        $prix = $repositoryPrix->find($id_prix);

        $form = $this->createForm(PrixType::class, $prix);

        $nbrePremPrix = 0;
        $nbreDeuxPrix = 0;
        $nbreTroisPrix = 0;

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $em->persist($prix);
            $em->flush();
            $niveau = $repositoryRepartprix->findOneBy(['niveau' => '1er']);
            try {
                $nbrePremPrix = $repositoryPrix->getNbrePrix('1er');
            } catch (NoResultException $e) {
            }
            $niveau->setNbreprix($nbrePremPrix);
            $em->persist($niveau);
            $niveau = $repositoryRepartprix->findOneBy(['niveau' => '2ème']);
            try {
                $nbreDeuxPrix = $repositoryPrix->getNbrePrix('2ème');
            } catch (NoResultException $e) {
            }
            $niveau->setNbreprix($nbreDeuxPrix);
            $em->persist($niveau);
            $niveau = $repositoryRepartprix->findOneBy(['niveau' => '3ème']);
            try {
                $nbreTroisPrix = $repositoryPrix->getNbrePrix('3ème');
            } catch (NoResultException $e) {
            }
            $niveau->setNbreprix($nbreTroisPrix);
            $em->persist($niveau);
            $em->flush();
            $request->getSession()->getFlashBag()->add('notice', 'Modifications bien enregistrées');
            return $this->redirectToroute('secretariatjury_lesprix');
        }
        $content = $this->renderView('secretariatjury/modifier_prix.html.twig',
            array(
                'prix' => $prix,
                'id_prix' => $id_prix,
                'form' => $form->createView(),
            ));
        return new Response($content);
    }

    #[IsGranted('ROLE_SECRETARIAT_JURY')]
    #[Route("/secretariatjury/approche", name: "secretariatjury_approche")]
    public function approche(Request $request): Response
    {
        $em = $this->doctrine->getManager();
        $repositoryEquipes = $em->getRepository(Equipes::class);
        $nbre_equipes = 0;

        $qb = $repositoryEquipes->createQueryBuilder('e');
        $qb->select('COUNT(e)');
        try {
            $nbre_equipes = $qb->getQuery()->getSingleScalarResult();//nombre total d'équipes
        } catch (NoResultException|NonUniqueResultException $e) {
        }

        $classement = $repositoryEquipes->classement('c', 0, $nbre_equipes);//on classe les équipes selon les couleurs par défaut
        $prixs = $this->doctrine->getManager()->getRepository(Repartprix::class)->findAll();
        $nbPrix = [];
        foreach ($prixs as $prix) {//On récupére le nombre d'équipes par prix
            if ($prix->getNiveau() == '1er') {
                $nbPrix[1] = $prix->getNbreprix();

            }
            if ($prix->getNiveau() == '2ème') {
                $nbPrix[2] = $prix->getNbreprix();
            }
            if ($prix->getNiveau() == '3ème') {
                $nbPrix[3] = $prix->getNbreprix();
            }
        }
        if ($request->query->get('RAZCouleur')) {//on place(lors le première visite de cette page) ou replace les couleurs par défaut selon le total
            //$classement = $repositoryEquipes->classement(0, 0, $nbre_equipes); //on classe les équipes selon leur total

            $premiersprix = $repositoryEquipes->classement(1, 0, $nbPrix[1]);//on cherche les équipes par prix selon leur total

            foreach ($premiersprix as $premierprix) {//on attribue les couleurs selon le prix et du classement selon le total
                $premierprix->setCouleur(1);
                $premierprix->setClassement('1er');
                $this->doctrine->getManager()->persist($premierprix);
                $this->doctrine->getManager()->flush();
            }

            $deuxiemesprix = $repositoryEquipes->classement(2, $nbPrix[1], $nbPrix[2]);

            foreach ($deuxiemesprix as $deuxiemeprix) {
                $deuxiemeprix->setCouleur(2);
                $deuxiemeprix->setClassement('2ème');
                $this->doctrine->getManager()->persist($deuxiemeprix);
                $this->doctrine->getManager()->flush();
            }
            $troisiemesprix = $repositoryEquipes->classement(3, $nbPrix[1] + $nbPrix[2], $nbPrix[3]);
            //dd($troisiemesprix);
            foreach ($troisiemesprix as $troisiemeprix) {
                $troisiemeprix->setCouleur(3);
                $troisiemeprix->setClassement('3ème');
                $this->doctrine->getManager()->persist($troisiemeprix);
                $this->doctrine->getManager()->flush();
            }
            $this->doctrine->getManager()->flush();
            $classement = $repositoryEquipes->classement('c', 0, $nbre_equipes);//
        }
        $valsClassement = ['1er', '2ème', '3ème'];
        foreach (range('A', 'Z') as $lettre) {//ON réactualise une équipe chaque fois qu'un bouton enregister est cliqué

            if ($request->query->get($lettre) != null) {//Si on a changé le prix d'une équipe et cliquer sur le bouton enregistrer

                $couleur = $request->query->get($lettre);//La valeur du champ de choix de nom lettre contient 0,1,2 ou 3
                $idequipe = $request->query->get('idEquipe');//le champ caché idEquipe contient l'id de l'équipe

                $equipe = $repositoryEquipes->findOneBy(['id' => $idequipe]);//ON trouve l'équipe qui correspond à l'id


                $equipe->setCouleur($couleur);//On attribue la couleur selon la valeur choisie

                $equipe->setRang($valsClassement[$couleur - 1]);
                $em->persist($equipe);
                $em->flush();
                $classement = $repositoryEquipes->classement('c', 0, $nbre_equipes);//On reclasse les équipes selon leur couleur donc leur prix apparent

            }

        }

        $content = $this->renderView('secretariatjury/approche.html.twig',
            array('classement' => $classement
            )
        );

        return new Response($content);
    }

    #[IsGranted('ROLE_SECRETARIAT_JURY')]
    #[Route("/secretariatjury/classement_definitif", name: "secretariatjury_classement_definitif")]
    public function classementdefinitif(): Response
    {
        $em = $this->doctrine->getManager();

        $repositoryEquipes = $em->getRepository(Equipes::class);
        $qb = $repositoryEquipes->createQueryBuilder('e')
            ->orderBy('e.couleur', 'ASC')
            ->leftJoin('e.equipeinter', 'i')
            ->addSelect('i')
            ->addOrderBy('i.lettre', 'ASC');

        $classement = $qb->getQuery()->getResult();

        $class = null;
        $i = 0;
        foreach ($classement as $equipe) {
            $i += 1;
            $couleur = $equipe->getCouleur();
            switch ($couleur) {
                case 1 :
                    $class = '1er';
                    break;
                case 2 :
                    $class = '2ème';
                    break;
                case 3 :
                    $class = '3ème';
                    break;

            }
            $equipe->setClassement($class);
            $equipe->setRang($i);
            $em->persist($equipe);
        }
        $em->flush();


        $content = $this->renderView('secretariatjury/classement_definitif.html.twig',
            array('classement' => $classement,)
        );
        return new Response($content);

    }

    #[IsGranted('ROLE_SECRETARIAT_JURY')]
    #[Route("/secretariatjury/mise_a_zero", name: "secretariatjury_mise_a_zero")]//enlève l'attribution des prix
    public function RaZ(): Response
    {
        $em = $this->doctrine->getManager();
        $repositoryEquipes = $em->getRepository(Equipes::class);

        $repositoryPrix = $em->getRepository(Prix::class);

        $ListPrix = $repositoryPrix->findAll();

        $ListeEquipes = $repositoryEquipes->findAll();

        foreach ($ListeEquipes as $equipe) {
            $equipe->setPrix(null);
            $em->persist($equipe);
        }

        foreach ($ListPrix as $Prix) {
            $Prix->setAttribue(0);// rajouter $em->persist($Prix);?
        }
        $em->flush();
        $content = $this->renderView('secretariatjury/RaZ.html.twig');
        return new Response($content);
    }

    #[IsGranted('ROLE_SECRETARIAT_JURY')]
    #[route("/secretariatjury/attrib_prix/{niveau}", name: "secretariatjury_attrib_prix", requirements: ["niveau" => "\d{1}"])]
    public function attrib_prix(Request $request, $niveau): RedirectResponse|Response
    {


        $em = $this->doctrine->getManager();
        $niveau_court = "";
        $niveau_long = "";
        switch ($niveau) {
            case 1:
                $niveau_court = '1er';
                $niveau_long = 'premiers';
                break;

            case 2:
                $niveau_court = '2ème';
                $niveau_long = 'deuxièmes';
                break;
            case 3:
                $niveau_court = '3ème';
                $niveau_long = 'troisièmes';
                break;
        }
        $repositoryEquipes = $em->getRepository(Equipes::class);
        $repositoryRepartprix = $em->getRepository(Repartprix::class);
        $repositoryPrix = $em->getRepository(Prix::class);

        $ListEquipes = $repositoryEquipes->findBy(['classement' => $niveau_court]);
        $NbrePrix = $repositoryRepartprix->findOneBy(['niveau' => $niveau_court])
            ->getNbreprix();


        $qb = $repositoryPrix->createQueryBuilder('p')
            ->where('p.niveau = :nivo')
            ->setParameter('nivo', $niveau_court);
        $prixNiveau = $qb->getQuery()->getResult();

        $prixNonAttrib = [];
        $i = 0;
        foreach ($prixNiveau as $prix) {
            if ($prix->getEquipe() == null) {

                $prixNonAttrib[$i] = $prix;
                $i = $i + 1;
            }

        }

        if ($request->query->get('equipe') != null) {
            $equipe = $repositoryEquipes->findOneBy(['id' => $request->query->get('equipe')]);
            $request->query->get('prix') == null ? $action = 'effacer' : $action = 'attribuer';
            if ($action == 'effacer') {
                $prix = $equipe->getPrix();
                $equipe->setPrix(null);
            }
            if ($action == 'attribuer') {
                $prix = $repositoryPrix->findOneBy(['id' => $request->query->get('prix')]);
                $equipe->setPrix($prix);

            }
            $em->persist($equipe);
            $em->flush();
            $request->getSession()->getFlashBag()->add('info', 'Prix bien enregistré');
            return $this->redirectToroute('secretariatjury_attrib_prix', array('niveau' => $niveau));
        }
        $content = $this->renderView('secretariatjury/attrib_prix.html.twig',
            array('ListEquipes' => $ListEquipes,
                'NbrePrix' => $NbrePrix,
                'niveau' => $niveau_long,
                'prixNonAttrib' => $prixNonAttrib
            )
        );
        return new Response($content);
    }

    #[IsGranted('ROLE_SECRETARIAT_JURY')]
    #[Route("/secretariatjury/edition_prix", name: "secretariatjury_edition_prix")]
    public function edition_prix(): Response
    {
        $listEquipes = $this->doctrine
            ->getManager()
            ->getRepository(Equipes::class)
            ->getEquipesPrix();
        //dd($listEquipes);
        $content = $this->renderView('secretariatjury/edition_prix.html.twig', array('listEquipes' => $listEquipes));
        return new Response($content);
    }

    #[IsGranted('ROLE_SECRETARIAT_JURY')]
    #[Route("/secretariatjury/edition_visites", name: "secretariatjury_edition_visites")]
    public function edition_visites(Request $request): Response
    {
        $em = $this->doctrine->getManager();
        $listEquipes = $this->doctrine->getRepository(Equipes::class)->findAll();

        if ($request->get('form')) {
            $idEquipe = $request->get('form')['id'];
            $idVisite = $request->get('form')['visite'];

            $visite = $this->doctrine->getRepository(Visites::class)->findOneBy(['id' => $idVisite]);
            $equipe = $this->doctrine->getRepository(Equipes::class)->findOneBy(['id' => $idEquipe]);
            isset($request->get('form')['enregistrer']) ? $action = 'enregistrer' : $action = 'effacer';
            if ($action == 'enregistrer') {
                //$visite->setEquipe($equipe);
                $equipe->setVisite($visite);
                //$this->doctrine->getManager()->persist($visite);
                $this->doctrine->getManager()->persist($equipe);
                $this->doctrine->getManager()->flush();
            }
            if ($action == 'effacer') {
                $equipe->setVisite(null);
                //$visite->setEquipe(null);
                $this->doctrine->getManager()->persist($equipe);
                $this->doctrine->getManager()->flush();
            }
            $request->initialize();
        }
        $listeVisite = $this->doctrine->getRepository(Visites::class)->findAll();
        $visitesNonAttr = [];
        $i = 0;
        foreach ($listeVisite as $visite) {
            if ($visite->getEquipe() === null) {
                $visitesNonAttr[$i] = $visite;
                $i++;
            }
        }
        $i = 0;
        foreach ($listEquipes as $equipe) {
            $placeholder = 'Choisir une visite';
            if ($equipe->getVisite() != null) {

                $visitesNonAttr[count($visitesNonAttr)] = $equipe->getVisite();
                $placeholder = $equipe->getVisite();
            }
            $form[$i] = $this->createFormBuilder($equipe)
                ->add('enregistrer', SubmitType::class)
                ->add('effacer', SubmitType::class)
                ->add('visite', EntityType::class, [
                    'class' => Visites::class,
                    'choices' => $visitesNonAttr,
                    'choice_label' => 'getIntitule',
                    'data' => $equipe->getVisite(),
                    'placeholder' => $placeholder,
                    'label' => null

                ])
                ->add('id', HiddenType::class, ['data' => $equipe->getId()])
                ->getForm();
            $Form[$i] = $form[$i]->createView();
            if ($equipe->getVisite() != null) {
                unset($visitesNonAttr[array_key_last($visitesNonAttr)]);
            }

            $i = $i + 1;
        }

        $content = $this->renderView('secretariatjury/edition_visites.html.twig', array('listEquipes' => $listEquipes, 'form' => $Form));
        return new Response($content);
    }

    /* #[IsGranted('ROLE_SECRETARIAT_JURY')]
     #[Route("/secretariatjury/attrib_cadeaux/{id_equipe}", name: "secretariatjury_attrib_cadeaux", requirements: ["id_equipe" => "\d{1}|\d{2}"])]
     public function attrib_cadeaux(Request $request, $id_equipe): RedirectResponse|Response
     {
         // repris de Olymphys4
         $repositoryEquipes = $this->doctrine
             ->getManager()
             ->getRepository(Equipes::class);
         $equipe = $repositoryEquipes->find($id_equipe);
         $repositoryCadeaux = $this->doctrine
             ->getManager()
             ->getRepository(Cadeaux::class);
         $cadeau = $equipe->getCadeau();
         $listeEquipes = $this->doctrine
             ->getManager()
             ->getRepository(Equipes::class)
             ->getEquipesCadeaux();

         if (is_null($cadeau)) {
             $flag = 0;
             $form = $this->createForm(EquipesType::class, $equipe,
                 array(
                     'Attrib_Phrases' => false,
                     'Attrib_Cadeaux' => true,
                     'Deja_Attrib' => false,
                 ));
             if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
                 $em = $this->doctrine->getManager();
                 $em->persist($equipe);
                 $cadeau = $equipe->getCadeau();
                 $em->persist($cadeau);
                 $em->flush();
                 $request->getSession()->getFlashBag()->add('notice', 'Notes bien enregistrées');
                 return $this->redirectToroute('secretariatjury_edition_cadeaux', array('listeEquipes' => $listeEquipes));
             }
         } else {
             $flag = 1;
             $em = $this->doctrine->getManager();
             $form = $this->createForm(EquipesType::class, $equipe,
                 array(
                     'Attrib_Phrases' => false,
                     'Attrib_Cadeaux' => true,
                     'Deja_Attrib' => true,
                 ));
             if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
                 if ($cadeau) {
                     $em->persist($cadeau);
                     $em->flush();


                     if ($cadeau->getEquipe() === null) {
                         $equipe->setCadeau(NULL);
                     }
                 }
                 $em->persist($equipe);
                 $em->flush();
                 $request->getSession()->getFlashBag()->add('notice', 'Cadeau attribué');
                 return $this->redirectToroute('secretariatjury_edition_cadeaux', array('listeEquipes' => $listeEquipes));
             }
         }
         $content = $this->renderView('secretariatjury/attrib_cadeaux.html.twig',
             array(
                 'equipe' => $equipe,
                 'form' => $form->createView(),
                 'attribue' => $flag,
             ));
         return new Response($content);
     }*/

    #[IsGranted('ROLE_SECRETARIAT_JURY')]
    #[Route("/secretariatjury/attrib-cadeaux/{compteur}", name: "secretariatjury_attrib_cadeaux")]
    public function lescadeaux(Request $request, $compteur): RedirectResponse|Response
    {
        $em = $this->doctrine->getManager();

        $repositoryEquipes = $em->getRepository(Equipes::class);

        $nbreEquipes = 0;
        try {
            $nbreEquipes = $repositoryEquipes->createQueryBuilder('e')
                ->select('COUNT(e)')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException $e) {
        }

        $compteur > $nbreEquipes ? $compteur = 1 : $compteur = $compteur;

        $listEquipesCadeaux = $repositoryEquipes->getEquipesCadeaux();

        $listEquipesPrix = $repositoryEquipes->getEquipesPrix();

        $equipe = $repositoryEquipes->findOneBy(['rang' => $compteur]);

        if (is_null($equipe)) {
            /* $content = $this->renderView('secretariatjury/edition_cadeaux2.html.twig',
                 array(
                     'listEquipesCadeaux' => $listEquipesCadeaux,
                     'listEquipesPrix' => $listEquipesPrix,
                     'listEquipes' => $equipes,
                     'nbreEquipes' => $nbreEquipes,
                     'compteur' => $compteur,));
             return new Response($content);*/
            return $this->redirectToRoute('secretariatjury_edition_cadeaux');
        }
        $cadeau = $equipe->getCadeau();
        if (is_null($cadeau)) {
            $flag = 0;
            $array = array(
                'Attrib_Phrases' => false,
                'Attrib_Cadeaux' => true,
                'Deja_Attrib' => false,
                'Attrib_Couleur' => false,
            );
        } else {
            $flag = 1;
            $array = array(
                'Attrib_Phrases' => false,
                'Attrib_Cadeaux' => true,
                'Deja_Attrib' => true,
                'Attrib_Couleur' => false,
            );
        }

        $form = $this->createForm(EquipesType::class, $equipe, $array);
        $equipeini = $this->doctrine->getRepository(Equipes::class)->find($equipe->getId());
        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {

            $em = $this->doctrine->getManager();
            $cadeau = $form->get('cadeau')->getData();
            //  dd($_REQUEST);

            if (isset($_REQUEST['cyberjury_equipes']['Effacer'])) {

                $equipe->setCadeau(null);
                //$cadeau->setAttribue(false);
            }
            if (isset($_REQUEST['cyberjury_equipes']['Enregistrer'])) {
                $equipe->setCadeau($cadeau);
                //$cadeau->setAttribue(true);
            }
            $em->persist($equipe);
            $em->persist($cadeau);
            $em->flush();
            $request->getSession()->getFlashBag()->add('info', 'Cadeaux bien enregistrés');
            if ($compteur <= $nbreEquipes) {
                return $this->redirectToroute('secretariatjury_attrib_cadeaux', array('compteur' => $compteur + 1));
            } else {
                $content = $this->renderView('secretariatjury/edition_cadeaux2.html.twig',
                    array('equipe' => $equipe,
                        'form' => $form->createView(),
                        'attribue' => $flag,
                        'listEquipesCadeaux' => $listEquipesCadeaux,
                        'listEquipesPrix' => $listEquipesPrix,
                        'nbreEquipes' => $nbreEquipes,
                        'compteur' => $compteur,));
                return new Response($content);
            }
        }

        $content = $this->renderView('secretariatjury/edition_cadeaux.html.twig',
            array('equipe' => $equipe,
                'form' => $form->createView(),
                'attribue' => $flag,
                'listEquipesCadeaux' => $listEquipesCadeaux,
                'listEquipesPrix' => $listEquipesPrix,
                'nbreEquipes' => $nbreEquipes,
                'compteur' => $compteur,));
        return new Response($content);
    }

    #[IsGranted('ROLE_SECRETARIAT_JURY')]
    #[Route("/secretariatjury/edition_cadeaux", name: "secretariatjury_edition_cadeaux")]
    public function edition_cadeaux(): Response
    {
        $em = $this->doctrine->getManager();
        $listEquipes = $em->getRepository(Equipes::class)
            ->getEquipesCadeaux();
        $content = $this->renderView('secretariatjury/edition_cadeaux2.html.twig', array('listEquipes' => $listEquipes));
        return new Response($content);
    }

    #[IsGranted('ROLE_SECRETARIAT_JURY')]
    #[Route("/secretariatjury/edition_phrases", name: "secretariatjury_edition_phrases")]
    public function edition_phrases(Request $request): Response
    {
        $em = $this->doctrine->getManager();
        $listEquipes = $em->getRepository(Equipes::class)
            ->getEquipesPhrases();

        if ($request->query->get('phrase') !== null) {
            $idPhrase = $request->query->get('phrase');
            $phraseAGarder = $this->doctrine->getRepository(Phrases::class)->findOneBy(['id' => $idPhrase]);
            $listPhrases = $phraseAGarder->getEquipe()->getPhrases();
            foreach ($listPhrases as $phrase) {
                if ($phrase != $phraseAGarder) {
                    $phraseAGarder->getEquipe()->removePhrase($phrase);
                    $em->persist($phraseAGarder->getEquipe());

                    $phrase->setJure(null);
                    $phrase->setEquipe(null);
                    $em->remove($phrase);
                    $em->flush();

                }

            }
        }


        $content = $this->renderView('secretariatjury/edition_phrases.html.twig', array('listEquipes' => $listEquipes));
        return new Response($content);
    }

    #[IsGranted('ROLE_SECRETARIAT_JURY')]
    #[Route("/secretariatjury/palmares_complet", name: "secretariatjury_edition_palmares_complet")]
    public function tableau_palmares_complet(): Response
    {
        $tableau = $this->requestStack->getSession()->get('tableau');
        $listEquipes = $this->doctrine->getRepository(Equipes::class)
            ->getEquipesPalmaresJury();
        if ($tableau[0] == null) {
            $this->requestStack->getSession()->set('info', 'Pas d\'équipe sélectionnée pour l\'édition en cours');
            $content = $this->renderView('secretariatjury/accueil.html.twig');
            return new Response($content);
        }
        //$listEquipes=$tableau[0];
        $prof1 = [];
        $prof2 = [];
        $phrases = [];


        foreach ($listEquipes as $equipe) {


            $prof = $this->doctrine->getRepository(User::class)->find($equipe->getEquipeinter()->getIdProf1()->getId());
            $prof1[$equipe->getEquipeinter()->getLettre()] = $prof->getPrenomNom();
            $prof2[$equipe->getEquipeinter()->getLettre()] = '';
            if ($equipe->getEquipeinter()->getIdProf2()) {
                $prof = $this->doctrine->getRepository(User::class)->find($equipe->getEquipeinter()->getIdProf2()->getId());
                $prof2[$equipe->getEquipeinter()->getLettre()] = $prof->getPrenomNom();
            }
            $phraseseq = $equipe->getPhrases();
            $phrases[$equipe->getEquipeinter()->getLettre()] = '';
            if (count($phraseseq) != 0) {
                $phrases[$equipe->getEquipeinter()->getLettre()] = $phraseseq[0]->getPhrase() . ' ' . $phraseseq[0]->getLiaison()->getLiaison() . ' ' . $phraseseq[0]->getPrix();

            }
        }

        $content = $this->renderView('secretariatjury/edition_palmares_complet.html.twig',
            array('listEquipes' => $listEquipes,
                'lesEleves' => $tableau[1],
                'lycee' => $tableau[2],
                'prof1' => $prof1,
                'prof2' => $prof2,
                'phrases' => $phrases));
        return new Response($content);
    }

    #[IsGranted('ROLE_SECRETARIAT_JURY')]
    #[Route("/secretariatjury/excel_site", name: "secretariatjury_tableau_excel_palmares_site")]
    public function tableau_excel_palmares_site()
    {
        $em = $this->doctrine->getManager();
        $tableau = $this->requestStack->getSession()->get('tableau');

        if ($tableau[0] == null) {
            $this->requestStack->getSession()->set('info', 'Pas d\'équipe sélectionnée pour l\'édition en cours');
            $content = $this->renderView('secretariatjury/accueil.html.twig');
            return new Response($content);
        }
        $equipes = $tableau[0];
        $lesEleves = $tableau[1];
        $lycee = $tableau[2];

        $nbreEquipes = 0;
        $prof1 = [];
        $prof2 = [];

        $repositoryUser = $em->getRepository(User::class);

        foreach ($equipes as $equipe) {
            $lettre = $equipe->getEquipeInter()->getLettre();
            $idprof1 = $equipe->getEquipeinter()->getIdProf1();
            $prof1[$lettre] = $repositoryUser->findBy(['id' => $idprof1]);
            $idprof2 = $equipe->getEquipeinter()->getIdProf2();
            $prof2[$lettre] = $repositoryUser->findBy(['id' => $idprof2]);

        }
        $listEquipes = $em->getRepository(Equipes::class)
            ->getEquipesPalmares();

        $repositoryEquipes = $em->getRepository(Equipes::class);

        try {
            $nbreEquipes = $repositoryEquipes
                ->createQueryBuilder('e')
                ->select('COUNT(e)')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException $e) {
        }
        $edition = $this->requestStack->getSession()->get('edition');
        $date = $edition->getDate();
        $result = $date->format('d/m/Y');
        $edition = $edition->getEd();
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator("Olymphys")
            ->setLastModifiedBy("Olymphys")
            ->setTitle("Palmarès de la " . $edition . "ème édition - " . $result)
            ->setSubject("Palmarès")
            ->setDescription("Palmarès avec Office 2005 XLSX, generated using PHP classes.")
            ->setKeywords("office 2005 openxml php")
            ->setCategory("Test result file");

        $sheet = $spreadsheet->getActiveSheet();

        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)
            ->setFitToHeight(0);


        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(6);

        $spreadsheet->getDefaultStyle()->getAlignment()->setWrapText(true);

        $borderArray = [
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '00000000'],
                ],
            ],
        ];
        $centerArray = [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'textRotation' => 0,
            'wrapText' => TRUE
        ];
        $vcenterArray = [
            'vertical' => Alignment::VERTICAL_CENTER,
            'textRotation' => 0,
            'wrapText' => TRUE
        ];
        $nblignes = $nbreEquipes * 4 + 3;

        $ligne = 3;

        $sheet->setCellValue('A' . $ligne, 'Académie')
            ->setCellValue('B' . $ligne, 'Lycée, sujet, élèves')
            ->setCellValue('C' . $ligne, 'Professeurs')
            ->mergeCells('D' . $ligne . ':E' . $ligne)
            ->setCellValue('D' . $ligne, 'Prix - Visite de laboratoire - Prix en matériel scientifique');
        $sheet->getStyle('A' . $ligne)->applyFromArray($borderArray);
        $sheet->getStyle('B' . $ligne)->applyFromArray($borderArray);
        $sheet->getStyle('C' . $ligne)->applyFromArray($borderArray);
        $sheet->getStyle('D' . $ligne)->applyFromArray($borderArray);
        $sheet->getStyle('E' . $ligne)->applyFromArray($borderArray);
        $sheet->getStyle('A' . $ligne . ':D' . $ligne)->getAlignment()->applyFromArray($centerArray);
        $ligne += 1;

        foreach ($listEquipes as $equipe) {
            $lettre = $equipe->getEquipeinter()->getLettre();

            $ligne4 = $ligne + 3;
            $classement = $equipe->getClassement();
            $rang = substr($classement, 0, 1);
            $indicOrdi = substr($classement, 1, strlen($classement));
            $richText = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
            $richText->createText($rang);
            $ordina = $richText->createTextRun($indicOrdi);
            $ordina->getFont()->setSuperscript(true)->setSize(9);
            $richText->createText(' prix');
            $sheet->mergeCells('A' . $ligne . ':A' . $ligne);

            $prefixelycee = 'Lycée ';
            if (str_contains($lycee[$lettre][0]->getNom(), 'Lycée') or str_contains($lycee[$lettre][0]->getNom(), 'lycée')) $prefixelycee = '';
            $sheet->setCellValue('A' . $ligne, mb_strtoupper($lycee[$lettre][0]->getAcademie()))
                ->setCellValue('B' . $ligne, $prefixelycee . $lycee[$lettre][0]->getNom() . " - " . $lycee[$lettre][0]->getCommune())
                ->setCellValue('C' . $ligne, $prof1[$lettre][0]->getPrenom() . " " . mb_strtoupper($prof1[$lettre][0]->getNom()))
                ->setCellValue('D' . $ligne, $richText);
            if ($equipe->getPhrases()[0] !== null) {
                $sheet->setCellValue('E' . $ligne, $equipe->getPhrases()[0]->getPhrase() . ' ' . $equipe->getPhrases()[0]->getLiaison()->getLiaison() . ' ' . $equipe->getPhrases()[0]->getPrix());
            } else {
                $sheet->setCellValue('E' . $ligne, 'Phrase');
            }
            $sheet->getStyle('A' . $ligne)->getFont()->setSize(7)->setBold(2);
            $sheet->getStyle('A' . $ligne . ':A' . $ligne4)->applyFromArray($borderArray);
            $sheet->getStyle('C' . $ligne)->getAlignment()->applyFromArray($centerArray);
            $sheet->getStyle('D' . $ligne . ':E' . $ligne)->getAlignment()->applyFromArray($vcenterArray);
            $sheet->getStyle('A' . $ligne . ':A' . $ligne4)->getAlignment()->applyFromArray($centerArray);
            $sheet->getStyle('A' . $ligne . ':E' . $ligne)->getFont()->getColor()->setRGB('000099');

            $lignes = $ligne + 3;
            $sheet->getStyle('D' . $ligne . ':E' . $lignes)->applyFromArray($borderArray);
            $sheet->getStyle('C' . $ligne . ':C' . $lignes)->applyFromArray($borderArray);
            $classement = $equipe->getClassement();
            $couleur = '0000';
            switch ($classement) {
                case '1er':
                    $couleur = 'ffccff';

                    break;
                case '2ème':
                    $couleur = '99ffcc';
                    break;
                case '3ème' :
                    $couleur = 'ccff99';
                    break;
            }

            $sheet->getStyle('D' . $ligne . ':E' . $ligne)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB($couleur);

            $ligne = $ligne + 1;

            $ligne3 = $ligne + 1;
            $sheet->mergeCells('B' . $ligne . ':B' . $ligne3);
            $sheet->setCellValue('B' . $ligne, $equipe->getEquipeinter()->getTitreProjet());
            if ($prof2[$lettre] != []) {
                $sheet->setCellValue('C' . $ligne, $prof2[$lettre][0]->getPrenom() . ' ' . mb_strtoupper($prof2[$lettre][0]->getNom()));
            }
            if ($equipe->getPrix() !== null) {
                $sheet->setCellValue('E' . $ligne, $equipe->getPrix()->getPrix());
            }
            $sheet->getStyle('B' . $ligne . ':B' . $ligne3)->applyFromArray($borderArray);
            $sheet->getStyle('B' . $ligne)->getAlignment()->applyFromArray($centerArray);
            $sheet->getStyle('B' . $ligne . ':B' . $ligne3)->getFont()->setBold(2)->getColor()->setRGB('ff0000');
            $sheet->getStyle('C' . $ligne)->getFont()->getColor()->setRGB('000099');
            $sheet->getStyle('C' . $ligne)->getAlignment()->applyFromArray($centerArray);
            $sheet->getStyle('D' . $ligne . ':E' . $ligne)->getAlignment()->applyFromArray($vcenterArray);

            $sheet->getStyle('D' . $ligne)->getAlignment()->applyFromArray($vcenterArray);


            $ligne = $ligne + 1;
            $sheet->setCellValue('D' . $ligne, 'Visite :');
            if ($equipe->getVisite() !== null) {
                $sheet->setCellValue('E' . $ligne, $equipe->getVisite()->getIntitule());
            }
            $sheet->getStyle('D' . $ligne . ':E' . $ligne)->getAlignment()->applyFromArray($vcenterArray);


            $ligne = $ligne + 1;
            $sheet->mergeCells('D' . $ligne . ':E' . $ligne);
            if ($equipe->getCadeau() !== null) {
                $sheet->setCellValue('D' . $ligne, $equipe->getCadeau()->getRaccourci());//. ' offert par ' . $equipe->getCadeau()->getFournisseur());
            }
            $sheet->getStyle('D' . $ligne . ':E' . $ligne)->getAlignment()->applyFromArray($vcenterArray);

            $listeleves = '';
            $nbre = count($lesEleves[$lettre]);
            $eleves = $lesEleves[$lettre];

            for ($i = 0; $i <= $nbre - 1; $i++) {
                $eleve = $eleves[$i];
                $prenom = $eleve->getPrenom();
                $nom = mb_strtoupper($eleve->getNom());
                if ($i < $nbre - 1) {
                    $listeleves .= $prenom . ' ' . $nom . ', ';
                } else {
                    $listeleves .= $prenom . ' ' . $nom;
                }
            }

            $sheet->setCellValue('B' . $ligne, $listeleves);
            $sheet->getStyle('B' . $ligne)->applyFromArray($borderArray);
            $sheet->getStyle('B' . $ligne)->getAlignment()->applyFromArray($vcenterArray);
            $sheet->getStyle('B' . $ligne)->getFont()->getColor()->setRGB('000099');

            $ligne = $ligne + 1;
        }

        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(80);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(110);
        $spreadsheet->getActiveSheet()->getStyle('A1:F' . $nblignes)
            ->getAlignment()->setWrapText(true);


        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="palmares.xls"');
        header('Cache-Control: max-age=0');

        $writer = new Xls($spreadsheet);
        $writer->save('php://output');


    }

    #[IsGranted('ROLE_SECRETARIAT_JURY')]
    #[Route("/secretariatjury/excel_jury", name: "secretariatjury_tableau_excel_palmares_jury")]//Tableau excel de travail utilisé pendant la tremise des rpix
    public function tableau_excel_palmares_jury()
    {
        $em = $this->doctrine->getManager();

        $nbreEquipes = 0;
        $mloyal = 'Caroline';
        $voix = 'Nathalie';
        $tableau = $this->requestStack->getSession()->get('tableau');
        if ($tableau[0] == null) {
            $this->requestStack->getSession()->set('info', 'Pas d\'équipe sélectionnée pour l\'édition en cours');
            $content = $this->renderView('secretariatjury/accueil.html.twig');
            return new Response($content);
        }
        $lycee = $tableau[2];

        $repositoryEquipes = $em->getRepository(Equipes::class);

        try {
            $nbreEquipes = $repositoryEquipes->createQueryBuilder('e')
                ->select('COUNT(e)')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException $e) {
        }
        $listEquipes = $em->getRepository(Equipes::class)
            ->getEquipesPalmaresJury();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator("Olymphys")
            ->setLastModifiedBy("Olymphys")
            ->setTitle("Palmarès de la " . $this->requestStack->getSession()->get('edition')->getEd() . "ème édition")
            ->setSubject("Palmarès")
            ->setDescription("Palmarès avec Office 2005 XLSX, generated using PHP classes.")
            ->setKeywords("office 2005 openxml php")
            ->setCategory("Test result file");
        $spreadsheet->getActiveSheet()->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(6);
        $spreadsheet->getDefaultStyle()->getAlignment()->setWrapText(true);
        $sheet = $spreadsheet->getActiveSheet();
        $borderArray = [
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '00000000'],
                ],
            ],
        ];
        $vcenterArray = [
            'vertical' => Alignment::VERTICAL_CENTER,
            'textRotation' => 0,
            'wrapText' => TRUE
        ];
        $styleText = array('font' => array(
            'bold' => false,
            'size' => 14,
            'name' => 'Calibri',
        ),);
        $ligne = 1;
        $avertissementPhrase = '';
        foreach ($listEquipes as $equipe) {
            $sheet->getRowDimension($ligne)->setRowHeight(30);
            $lettre = $equipe->getEquipeinter()->getLettre();
            $sheet->mergeCells('B' . $ligne . ':C' . $ligne);
            $sheet->setCellValue('A' . $ligne, $voix);//voix ff
            $sheet->setCellValue('B' . $ligne, 'Remise du ' . $equipe->getClassement() . ' Prix');
            $sheet->getStyle('A' . $ligne . ':D' . $ligne)->getAlignment()->applyFromArray($vcenterArray);
            $sheet->getStyle('A' . $ligne . ':D' . $ligne)
                ->applyFromArray($styleText);
            if ($equipe->getPrix() !== null) {
                // $voix=$equipe->getPrix()->getVoix();

                $sheet->getStyle('A' . $ligne . ':D' . $ligne)->applyFromArray($borderArray);

                $sheet->setCellValue('D' . $ligne, $equipe->getPrix()->getPrix());
                $sheet->getStyle('A' . $ligne . ':D' . $ligne)
                    ->applyFromArray($styleText);
                $sheet->mergeCells('B' . $ligne . ':C' . $ligne);
                $sheet->getStyle('A' . $ligne . ':D' . $ligne)
                    ->applyFromArray($styleText);
                $sheet->getRowDimension($ligne)->setRowHeight(40);
                if ($equipe->getPrix()->getIntervenant()) {
                    $ligne += 1;
                    $sheet->getRowDimension($ligne)->setRowHeight(40);
                    $sheet->mergeCells('B' . $ligne . ':D' . $ligne);
                    // $voix=$equipe->getPrix()->getVoix();
                    $sheet->setCellValue('A' . $ligne, $voix);
                    $sheet->setCellValue('B' . $ligne, 'Ce prix est remis par ' . $equipe->getPrix()->getIntervenant());
                    $sheet->mergeCells('B' . $ligne . ':D' . $ligne);
                    $sheet->getStyle('A' . $ligne . ':D' . $ligne)
                        ->applyFromArray($styleText);
                    $sheet->getStyle('A' . $ligne . ':D' . $ligne)->applyFromArray($borderArray);
                }
            }


            $ligne = $ligne + 1;
            $sheet->getRowDimension($ligne)->setRowHeight(30);

            $sheet->mergeCells('B' . $ligne . ':D' . $ligne);
            $remispar = $mloyal; //remplacer $remis par par $voix1 et $voix2

            if ($equipe->getPhrases()[0] != null) {
                $sheet->setCellValue('A' . $ligne, $remispar);
                $sheet->setCellValue('B' . $ligne, $equipe->getPhrases()[0]->getPhrase() . ' ' . $equipe->getPhrases()[0]->getLiaison()->getLiaison() . ' ' . $equipe->getPhrases()[0]->getPrix());
            } else {
                $avertissementPhrase = $avertissementPhrase . 'L\'équipe ' . $equipe->getEquipeinter()->getLettre() . ' n\'a pas de phrase amusante <br>';

            }
            $sheet->getStyle('B' . $ligne)->getAlignment()->applyFromArray($vcenterArray);
            $sheet->getStyle('A' . $ligne . ':D' . $ligne)
                ->applyFromArray($styleText);
            $sheet->getStyle('A' . $ligne . ':D' . $ligne)->applyFromArray($borderArray);

            $ligne++;
            $remispar = $voix;
            $sheet->getRowDimension($ligne)->setRowHeight(40);
            if ($equipe->getVisite() !== null) {
                $sheet->setCellValue('A' . $ligne, $remispar);
                $sheet->setCellValue('B' . $ligne, 'Vous visiterez');
                $sheet->mergeCells('C' . $ligne . ':D' . $ligne);

                $sheet->setCellValue('C' . $ligne, $equipe->getVisite()->getIntitule());
            }

            $ligne = $this->getLigne($sheet, $ligne, $styleText, $borderArray);
            $ligne++;
            $sheet->getRowDimension($ligne)->setRowHeight(40);
            $sheet->setCellValue('A' . $ligne, $remispar);
            $sheet->setCellValue('B' . $ligne, 'Votre lycée recevra');
            $sheet->mergeCells('C' . $ligne . ':D' . $ligne);
            if ($equipe->getCadeau() !== null) {
                $sheet->setCellValue('C' . $ligne, $equipe->getCadeau()->getRaccourci());//. ' offert par ' . $equipe->getCadeau()->getFournisseur());
            }
            $ligne = $this->getLigne($sheet, $ligne, $styleText, $borderArray);
            $remispar = $mloyal;
            $lignep = $ligne + 1;
            $sheet->getRowDimension($ligne)->setRowHeight(40);
            $sheet->setCellValue('A' . $ligne, $remispar);

            $sheet->mergeCells('B' . $ligne . ':B' . $lignep);
            $sheet->setCellValue('B' . $ligne, 'J\'appelle')
                ->setCellValue('C' . $ligne, 'l\'equipe ' . $equipe->getEquipeinter()->getLettre())
                ->setCellValue('D' . $ligne, $equipe->getEquipeinter()->getTitreProjet());
            $sheet->getStyle('D' . $ligne)->getAlignment()->applyFromArray($vcenterArray);
            $sheet->getStyle('B' . $ligne)->getAlignment()
                ->setVertical(Alignment::VERTICAL_CENTER);
            $aligne = $ligne;
            $ligne = $ligne + 1;
            $sheet->getRowDimension($ligne)->setRowHeight(40);

            $sheet->setCellValue('C' . $ligne, 'AC. ' . $lycee[$lettre][0]->getAcademie())
                ->setCellValue('D' . $ligne, 'Lycee ' . $lycee[$lettre][0]->getNom() . "\n" . $lycee[$lettre][0]->getCommune());
            $sheet->getStyle('C' . $ligne)->getAlignment()->applyFromArray($vcenterArray);
            $sheet->getStyle('D' . $ligne)->getAlignment()->applyFromArray($vcenterArray);
            $sheet->getStyle('A' . $aligne . ':D' . $ligne)
                ->applyFromArray($styleText);
            $sheet->getStyle('A' . $aligne . ':D' . $lignep)->applyFromArray($borderArray);
            $ligne = $ligne + 2;
            $sheet->mergeCells('A' . $ligne . ':D' . $ligne);
            $spreadsheet->getActiveSheet()->setBreak('A' . $ligne, \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::BREAK_ROW);//permet d'obtenir un saut de page à chaque équipe donc une page par équipe.

            $ligne++;
            //$spreadsheet->getActiveSheet()->getStyle('A' . $ligne)->getFont()->getColor()->setARGB(Color::COLOR_RED);
            $spreadsheet->getActiveSheet()->getStyle('A' . $ligne . ':D' . $ligne)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $spreadsheet->getActiveSheet()->getStyle('A' . $ligne . ':D' . $ligne)->getFill()->getStartColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLACK);

            $ligne = $ligne + 1;
        }
        if ($avertissementPhrase != '') {
            $avertissementPhrase = $avertissementPhrase . 'Tableau excel non créé, compléter les phrases amusantes manquantes';
            $this->requestStack->getSession()->set('info', $avertissementPhrase);
            return $this->redirectToRoute('secretariatjury_accueil');

        }

        $nblignes = 5 * $nbreEquipes + 2;
        $sheet->getColumnDimension('A')->setWidth(32);
        $sheet->getColumnDimension('B')->setWidth(32);
        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(120);
        $sheet->getColumnDimension('E')->setWidth(80);

        $spreadsheet->getActiveSheet()->getStyle('A1:F' . $nblignes)
            ->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToHeight(0);
        $spreadsheet->getActiveSheet()->getPageSetup()->setHorizontalCentered(true);
        $spreadsheet->getActiveSheet()->getPageSetup()->setVerticalCentered(true);
        $spreadsheet->getActiveSheet()->getHeaderFooter()->setOddFooter('&14Page &P sur &N');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="proclamation.xls"');
        header('Cache-Control: max-age=0');
        $writer = new Xls($spreadsheet);
        $writer->save('php://output');

    }

    public function getLigne(Worksheet $sheet, $ligne, array $styleText, array $borderArray): int
    {
        $sheet->getStyle('C' . $ligne . ':D' . $ligne)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A' . $ligne . ':D' . $ligne)
            ->applyFromArray($styleText);
        $sheet->getStyle('A' . $ligne . ':D' . $ligne)->applyFromArray($borderArray);

        $ligne += 1;
        return $ligne;
    }


    #[IsGranted('ROLE_SECRETARIAT_JURY')]
    #[Route("/secretariatjury/excel_prix", name: "secretariatjury_excel_prix")]
    public function excel_prix(Request $request): RedirectResponse|Response
    {  //fonction appelée à partir de l'admin page les prix dans le PrixCrudcontroller

        $defaultData = ['message' => 'Charger le fichier excel pour le palmares'];
        $form = $this->createFormBuilder($defaultData)
            ->add('fichier', FileType::class)
            ->add('save', SubmitType::class)
            ->getForm();


        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $fichier = $data['fichier'];
            $spreadsheet = IOFactory::load($fichier);
            $worksheet = $spreadsheet->getActiveSheet();

            $highestRow = $spreadsheet->getActiveSheet()->getHighestRow();

            $em = $this->doctrine->getManager();


            for ($row = 2; $row <= $highestRow; ++$row) {
                $prix = new Prix();
                $niveau = $worksheet->getCell(2, $row)->getValue();
                $prix->setNiveau($niveau);
                $prix_nom = $worksheet->getCell(3, $row)->getValue();
                $prix->setPrix($prix_nom);
                $equipe = $worksheet->getCell(4, $row)->getValue();
                $prix->setEquipe($equipe);
                $voix = $worksheet->getCell(5, $row)->getValue();
                $prix->setVoix($voix);
                $intervenant = $worksheet->getCell(6, $row)->getValue();
                $prix->setIntervenant($intervenant);
                $remisPar = $worksheet->getCell(7, $row)->getValue();
                $prix->setRemisPar($remisPar);


                $em->persist($prix);
                $em->flush();

            }

            return $this->redirectToRoute('dashboard');
        }
        $content = $this
            ->renderView('secretariatadmin\charge_donnees_excel.html.twig', array('titre' => 'Remplissage des prix', 'form' => $form->createView(),));
        return new Response($content);


    }

    #[IsGranted('ROLE_SECRETARIAT_JURY')]
    #[Route("/secretariatjury/excel_diaporama_cn", name: "secretariatjury_tableau_excel_diaporama_cn")]//Tableau excel de travail utilisé pour la création du diaporama
        //ne comporte que le nom du prix, le nom et coordonnées des équipes dans l'ordre d'appel lors de la remise des prix
    public function tableau_excel_diaporama_cn()
    {
        $em = $this->doctrine->getManager();

        $nbreEquipes = 0;
        $tableau = $this->requestStack->getSession()->get('tableau');
        if ($tableau[0] == null) {
            $this->requestStack->getSession()->set('info', 'Pas d\'équipe sélectionnée pour l\'édition en cours');
            $content = $this->renderView('secretariatjury/accueil.html.twig');
            return new Response($content);
        }
        $lycee = $tableau[2];

        $repositoryEquipes = $em->getRepository(Equipes::class);

        try {
            $nbreEquipes = $repositoryEquipes->createQueryBuilder('e')
                ->select('COUNT(e)')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException $e) {
        }
        $listEquipes = $em->getRepository(Equipes::class)
            ->getEquipesPalmaresJury();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator("Olymphys")
            ->setLastModifiedBy("Olymphys")
            ->setTitle("Creation du diaporama " . $this->requestStack->getSession()->get('edition')->getEd() . "ème édition")
            ->setSubject("Diaporama")
            ->setDescription("Diaporama avec Office 2005 XLSX, generated using PHP classes.")
            ->setKeywords("office 2005 openxml php")
            ->setCategory("Test result file");
        $spreadsheet->getActiveSheet()->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(6);
        $spreadsheet->getDefaultStyle()->getAlignment()->setWrapText(true);
        $sheet = $spreadsheet->getActiveSheet();
        $borderArray = [
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '00000000'],
                ],
            ],
        ];
        $vcenterArray = [
            'vertical' => Alignment::VERTICAL_CENTER,
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'textRotation' => 0,
            'wrapText' => TRUE
        ];
        $styleText = array('font' => array(
            'bold' => false,
            'size' => 14,
            'name' => 'Calibri',
        ),);
        $styleLettre = array('font' => array(
            'bold' => true,
            'size' => 18,
            'name' => 'Calibri',
        ),);
        $bordures = [
            'borders' => [
                'bottom' => ['borderStyle' => 'hair', 'color' => ['argb' => '00000000']],
                'top' => ['borderStyle' => 'hair', 'color' => ['argb' => '00000000']],
                'right' => ['borderStyle' => 'hair', 'color' => ['argb' => '00000000']],
                'left' => ['borderStyle' => 'hair', 'color' => ['argb' => '00000000']],
            ],
        ];
        $ligne = 1;
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(60);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(80);
        $sheet->getColumnDimension('E')->setWidth(80);
        $sheet->mergeCells('A' . $ligne . ':E' . $ligne);
        $sheet->setCellValue('A' . $ligne, 'Diaporama de la remise des prix - Ordre d\'appel des équipes ');
        $sheet->getStyle('A' . $ligne)->applyFromArray($styleText);
        $sheet->getStyle('A' . $ligne)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $ligne = $ligne + 2;

        foreach ($listEquipes as $equipe) {
            if ($equipe->getClassement() != null and $equipe->getPrix() != null) {
                $sheet->getRowDimension($ligne)->setRowHeight(50);
                $lettre = $equipe->getEquipeinter()->getLettre();
                $sheet->setCellValue('C' . $ligne, $lettre);
                $sheet->setCellValue('A' . $ligne, $equipe->getClassement() . ' Prix');
                $sheet->setCellValue('B' . $ligne, $equipe->getPrix()->getPrix());//Donne l'intitulé du prix
                $sheet->getStyle('C' . $ligne)->applyFromArray($styleLettre)->applyFromArray($borderArray);
                $sheet->getStyle('A' . $ligne . ':B' . $ligne)->applyFromArray($styleText);
                $sheet->setCellValue('D' . $ligne, $equipe->getEquipeInter()->getTitreProjet());
                $sheet->getStyle('D' . $ligne)->applyFromArray($styleText)->applyFromArray($borderArray);
                $sheet->setCellValue('E' . $ligne, $equipe->getEquipeInter()->getLyceeNomAcademie());
                $sheet->getStyle('E' . $ligne)->applyFromArray($styleText)->applyFromArray($borderArray);
                $spreadsheet->getActiveSheet()->getStyle('A' . $ligne . ':E' . $ligne)
                    ->applyFromArray($borderArray);
                $ligne++;
                $ligne++;
            }
        }
        $nblignes = $ligne;
        $spreadsheet->getActiveSheet()->getStyle('A3:D' . $nblignes)
            ->getAlignment()->setWrapText(true)->applyFromArray($vcenterArray)->applyFromArray($bordures);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToHeight(0);
        $spreadsheet->getActiveSheet()->getPageSetup()->setHorizontalCentered(true);
        $spreadsheet->getActiveSheet()->getPageSetup()->setVerticalCentered(true);
        if ($nblignes > 25) {
            $spreadsheet->getActiveSheet()->getHeaderFooter()->setOddFooter('&14Page &P sur &N');
        }

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="ordre_proclamation.xls"');
        header('Cache-Control: max-age=0');

        $writer = new Xls($spreadsheet);
        ob_end_clean();//erreur 500 sur le site(pas en local)si cette ligne n'est pas présente
        $writer->save('php://output');


    }

    #[IsGranted('ROLE_SECRETARIAT_JURY')]
    #[Route("/secretariatjury/gestionjures", name: "secretariatjury_gestionjures")]
    public function gestionjures(Request $request)//Cette fonction est appelée à chaque changement d'un champ du formulaire via une fontion JQUERY et ajax dans app.js
    {   //Ainsi l'organisateur peut saisir le tableau à la "volée"

        $listeEquipes = $this->doctrine->getRepository(Equipes::class)->createQueryBuilder('e')
            ->leftJoin('e.equipeinter', 'eq')
            ->addOrderBy('eq.lettre', 'ASC')
            ->getQuery()->getResult();
        $attributionsRepo = $this->doctrine->getRepository(Attributions::class);

        //$request contient les infos à traiter
        if ($request->get('idjure') !== null) {//pour la modif des données perso du juré
            $idJure = $request->get('idjure');
            $val = $request->get('value');
            $type = $request->get('type');
            $jure = $this->doctrine->getRepository(Jures::class)->find($idJure);
            $userJure = $this->doctrine->getRepository(User::class)->find($jure->getIduser()->getId());
            switch ($type) {
                case 'prenom':
                    $jure->setPrenomJure($val);
                    $userJure->setPrenom($val);
                    $this->doctrine->getManager()->persist($userJure);//pour être en cohérence avec la table user
                    break;
                case 'nom' :
                    $jure->setNomJure($val);
                    $userJure->setNom($val);
                    $this->doctrine->getManager()->persist($userJure);//pour être en cohérence avec la table user
                    break;
                case 'initiales':
                    $jure->setInitialesJure($val);
                    break;

            }
            $this->doctrine->getManager()->persist($jure);
            $this->doctrine->getManager()->flush();
            //$this->redirectToRoute('secretariatjuryCia_gestionjures');
        }

        if ($request->query->get('idequipe') != null) {//pour la modification des attribtions des équipes

            $idJure = $request->query->get('idjure');
            $attrib = $request->query->get('value');
            $idequipe = $request->query->get('idequipe');
            $jure = $this->doctrine->getRepository(Jures::class)->find($idJure);
            $equipe = $this->doctrine->getRepository(Equipes::class)->find($idequipe);
            $attribution = $attributionsRepo->findOneBy(['jure' => $jure, 'equipe' => $equipe]);
            if ($attrib != '') {
                $value = null;
                switch ($attrib) {
                    case 'E':
                        $value = 0;
                        break;
                    case 'L':
                        $value = 1;
                        break;
                    case 'R':
                        $value = 2;
                        break;

                }

                if ($attribution !== null) {
                    $attribution->setEstLecteur($value);
                    $this->doctrine->getManager()->persist($attribution);
                } else {
                    $attribution = new Attributions();
                    $attribution->setJure($jure);
                    $attribution->setEquipe(($equipe));
                    $attribution->setEstLecteur($value);
                    $this->doctrine->getManager()->persist($attribution);
                    $jure->addAttribution($attribution);
                    $this->doctrine->getManager()->persist($jure);
                }
                $this->doctrine->getManager()->flush();
            }

            if ($attrib == '') {//Le champ est vide pas d'affectation du juré à cette équipe
                if ($attribution != null) {
                    $note = $this->doctrine->getRepository(Notes::class)->findOneBy(['jure' => $jure, 'equipe' => $equipe]);
                    if ($note != null) {
                        $jure->removeNote($note);
                    }
                    $jure->removeAttribution($attribution);
                    $this->doctrine->getManager()->remove($attribution);
                    $this->doctrine->getManager()->flush();
                }
            }
            $listejures = $this->doctrine->getRepository(Jures::class)->createQueryBuilder('j')
                ->addOrderBy('j.nomJure', 'ASC')
                ->getQuery()->getResult();
            return $this->render('secretariatjury/gestionjures.html.twig', array('listejures' => $listejures, 'listeEquipes' => $listeEquipes));


        }

        if ($request->query->get('jureID') !== null) {//la fenêtre modale de confirmation de suppresion du juré a été validée, elle renvoie l'id du juré

            $idJure = $request->query->get('jureID');
            $jure = $this->doctrine->getRepository(Jures::class)->find($idJure);
            $notes = $jure->getNotesj();
            if ($notes !== null) {
                foreach ($notes as $note) {
                    $jure->removeNote($note);
                    $this->doctrine->getManager()->remove($note);

                }

            }
            $this->doctrine->getManager()->remove($jure);
            $this->doctrine->getManager()->flush();
            $idJure = null;//Dans le cas où le formulaire est envoyé dès le clic sur un des input

        }


        $listejures = $this->doctrine->getRepository(Jures::class)->createQueryBuilder('j')
            ->addOrderBy('j.nomJure', 'ASC')
            ->getQuery()->getResult();

        return $this->render('secretariatjury/gestionjures.html.twig', array('listejures' => $listejures, 'listeEquipes' => $listeEquipes));

    }

    #[IsGranted('ROLE_SECRETARIAT_JURY')]
    #[Route("/secretariatjury/tableauexcelRepartition", name: "secretariatjury_tableauexcel_repartition")]
    public function tableauexcelRepartition()
    {
        $listejures = $this->doctrine->getRepository(Jures::class)->createQueryBuilder('j')
            ->orderBy('j.nomJure', 'ASC')
            ->getQuery()->getResult();
        $listeEquipes = $this->doctrine->getRepository(Equipes::class)->createQueryBuilder('e')
            ->leftJoin('e.equipeinter', 'eq')
            ->addOrderBy('eq.lettre', 'ASC')
            ->getQuery()->getResult();

        $styleAlignment = [

            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],

        ];
        $styleArray = [

            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'top' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
                'left' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
                'right' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
                'bottom' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
                'inside' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ]
            ],

        ];
        $styleArrayTop = [

            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'top' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
                'left' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
                'right' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
                'bottom' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
                'inside' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ]
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'FFFFFACD',
                ],

            ],

        ];
        $spreadsheet = new Spreadsheet();

        $spreadsheet->getProperties()
            ->setCreator("Olymphys")
            ->setLastModifiedBy("Olymphys")
            ->setTitle("CN-Tableau destiné au comité")
            ->setSubject("Tableau destiné aux organisateurs")
            ->setDescription("Office 2007 XLSX répartition des jurés")
            ->setKeywords("Office 2007 XLSX")
            ->setCategory("Test result file");
        $spreadsheet->getActiveSheet()->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setHorizontalCentered(true);
        $spreadsheet->getActiveSheet()->getPageMargins()->setTop(0.4);
        $spreadsheet->getActiveSheet()->getPageMargins()->setRight(0.4);
        $spreadsheet->getActiveSheet()->getPageMargins()->setLeft(0.4);
        $spreadsheet->getActiveSheet()->getPageMargins()->setBottom(0.4);;
        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setWorksheet($spreadsheet->getActiveSheet());
        $drawing->setName('Logo');
        $drawing->setDescription('Logo');
        $drawing->setPath('./odpf/odpf-images/site-logo-285x75.png');
        $drawing->setResizeProportional(false);
        $drawing->setHeight(37);
        $drawing->setWidth(60);
        $drawing->setCoordinates('A1');
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getRowDimension(1)->setRowHeight(30, 'pt');
        $sheet->getStyle('A2')->applyFromArray($styleAlignment);
        $sheet->getStyle('A2')->getFont()->setSize(16);
        $sheet->getStyle('A3')->applyFromArray($styleAlignment);
        $sheet->getStyle('A3')->getFont()->setSize(20);

        $lettres = ['D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE'];
        $sheet->mergeCells('A2:' . $lettres[count($listeEquipes) - 1] . '2', Worksheet::MERGE_CELL_CONTENT_HIDE);
        $sheet->mergeCells('A3:' . $lettres[count($listeEquipes) - 1] . '3', Worksheet::MERGE_CELL_CONTENT_HIDE);
        $sheet->getRowDimension(2)->setRowHeight(20, 'pt');
        $sheet->getRowDimension(3)->setRowHeight(35, 'pt');
        $sheet->mergeCells('D5:' . $lettres[count($listeEquipes) - 1] . '5', Worksheet::MERGE_CELL_CONTENT_HIDE);
        $sheet->getStyle('D5:' . $lettres[count($listeEquipes) - 1] . '5')->applyFromArray($styleArray);
        $sheet
            ->setCellValue('A2', 'Olympiades de Physique France ' . $this->requestStack->getSession()->get('edition')->getEd() . 'e édition');

        $sheet
            ->setCellValue('A3', 'Concours National - Répartition des jurés');
        $sheet
            ->setCellValue('D5', 'Lettres des équipes');

        $ligne = 9;

        $sheet->getRowDimension($ligne)->setRowHeight(25, 'pt');

        $sheet
            ->setCellValue('A' . $ligne, 'Prénom juré')
            ->setCellValue('B' . $ligne, 'Nom juré')
            ->setCellValue('C' . $ligne, 'Initiales')
            ->setCellValue('C' . $ligne - 2, 'salle')
            ->setCellValue('C' . $ligne - 3, 'horaire')
            ->setCellValue('C' . $ligne - 1, 'ordre');
        $i = 0;
        $spreadsheet->getActiveSheet()->getStyle('A' . $ligne . ':' . $lettres[count($listeEquipes) - 1] . $ligne)->applyFromArray($styleArrayTop);
        foreach ($listeEquipes as $equipe) {

            $sheet->setCellValue($lettres[$i] . $ligne, $equipe->getEquipeinter()->getLettre());
            $sheet->getStyle($lettres[$i] . $ligne - 3)->applyFromArray($styleArray);
            $sheet->getStyle($lettres[$i] . $ligne - 2)->applyFromArray($styleArray);
            $sheet->getStyle($lettres[$i] . $ligne - 1)->applyFromArray($styleArray);
            if ($equipe->getHeure() != null) {
                $sheet->setCellValue($lettres[$i] . $ligne - 3, $equipe->getHeure());
            }
            $sheet->setCellValue($lettres[$i] . $ligne - 2, $equipe->getSalle());
            $sheet->setCellValue($lettres[$i] . $ligne - 1, $equipe->getordre());

            $i = $i + 1;
        }
        $i = 0;

        $ligne += 1;
        $sheet->getRowDimension($ligne)->setRowHeight(25, 'pt');
        foreach ($listejures as $jure) {
            $sheet
                ->setCellValue('A' . $ligne, $jure->getPrenomJure())
                ->setCellValue('B' . $ligne, $jure->getNomJure())
                ->setCellValue('C' . $ligne, $jure->getInitialesJure());
            $spreadsheet->getActiveSheet()->getStyle('A' . $ligne . ':' . $lettres[count($listeEquipes) - 1] . $ligne)->applyFromArray($styleArray);
            $sheet->getRowDimension($ligne)->setRowHeight(25, 'pt');
            $attributionsJure = $jure->getAttributions();
            $lecteur = ['E', 'L', 'R'];
            foreach ($listeEquipes as $equipe) {
                foreach ($attributionsJure as $attribution) {
                    if ($attribution->getEquipe() == $equipe) {
                        $sheet->setCellValue($lettres[$i] . $ligne, 'E');
                        if ($attribution->getEstLecteur()) {
                            $sheet->setCellValue($lettres[$i] . $ligne, $lecteur[$attribution->getEstLecteur()]);
                        }
                    }

                }
                $i += 1;
            }
            //


            foreach (range('A', $lettres[$i - 1]) as $letra) {
                $sheet->getColumnDimension($letra)->setAutoSize(true);
            }
            $i = 0;
            $ligne += 1;

        }
        //$sheet->getStyle('A2:' . $lettres[$i - 1] . '2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('00AA66');


        $writer = new Xls($spreadsheet);
        //$writer->save('temp/repartition_des_jures.xls');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename=' . $this->requestStack->getSession()->get('edition')->getEd() . '-repartition_des_jures_du_concours_national.xls');
        header('Cache-Control: max-age=0');
        //$writer= PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        //$writer =  \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
        // $writer =IOFactory::createWriter($spreadsheet, 'Xlsx');
        ob_end_clean();
        $writer->save('php://output');


    }

    #[IsGranted('ROLE_SECRETARIAT_JURY')]
    #[Route("/secretariatjury_creejure", name: "secretariatjury_creeJure")]
    public function creeJure(Request $request, UserPasswordHasherInterface $passwordEncoder, Mailer $mailer)
    {

        $this->requestStack->getSession()->set('info', '');
        $slugger = new AsciiSlugger();
        $repositoryUser = $this->doctrine->getRepository(User::class);
        $user = new User();
        $form = $this->createFormBuilder($user)
            ->add('email', RepeatedType::class, [
                'first_options' => ['label' => 'Email'],
                'second_options' => ['label' => 'Saisir de nouveau l\'email'],
            ])
            ->add('nom', TextType::class)
            ->add('prenom', TextType::class)
            ->add('valider', SubmitType::class)
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('valider')->isClicked()) {
                $email = $form->get('email')->getData();
                $nom = $form->get('nom')->getData();
                $prenom = $form->get('prenom')->getData();
                $userIni = $repositoryUser->findOneBy(['email' => $email]);
                //Tout d'abord on crée le compte olymphys si le juré n'en a pas encore
                if ($userIni === null) {// Le mail ne correspond à aucun compte olymphys
                    //On crée le user
                    try {

                        $user->setNom($nom);//Elimine les caractères ésotériques
                        $prenomNorm = ucfirst(strtolower($slugger->slug($prenom)));//prépare la normalisation du prénom
                        if (count(explode('-', $prenom)) > 1) {//Si le prénom est composé

                            $prenomNorm = '';
                            $i = 0;
                            $arrayPrenom = explode('-', $prenom);

                            foreach ($arrayPrenom as $sprenom) {//Pour obtenir un prénom composé de la form Prenom-Prenom
                                if ($i == 0) {
                                    $prenomNorm = ucfirst(strtolower($sprenom)) . '-';
                                    $i++;

                                } elseif ($i < count(explode('-', $prenom)) - 1) {

                                    $prenomNorm = $prenomNorm . ucfirst(strtolower($sprenom)) . '-';

                                } elseif ($i == count(explode('-', $prenom)) - 1) {
                                    $prenomNorm = $prenomNorm . ucfirst(strtolower($sprenom));

                                }
                            }

                        }

                        $user->setPrenom($prenom);
                        $user->setEmail($email);
                        $roles = $user->getRoles();
                        $roles[count($roles)] = 'ROLE_JURY';
                        $user->setRoles($roles);
                        $username = $prenomNorm[0] . '_' . $slugger->slug($nom);//Création d'un username avec caratères ASCII
                        $pwd = $prenomNorm;
                        $i = 1;
                        while ($repositoryUser->findBy(['username' => $username])) {//pour éviter des logins identiques on ajoute un numéro à la fin
                            $username = $username . $i;
                            $i = +1;
                        }
                        $user->setUsername($username);
                        $user->setPassword($passwordEncoder->hashPassword($user, $pwd));
                        $this->doctrine->getManager()->persist($user);
                        $this->doctrine->getManager()->flush();
                        //$mailer->sendInscriptionUserJure($user, $pwd);//On envoie au nouvel user ses identifiants avec copie au comité
                    } catch (\Exception $e) {
                        $texte = 'Une erreur est survenue lors de l\'inscription de ce jure :' . $e;
                        $this->requestStack->getSession()->set('info', $texte);//Un emodale surgira si une erreur est survenue lors de la création du juré
                    }

                } else {
                    $user = $repositoryUser->findOneBy(['email' => $email]);
                    $roles = $user->getRoles();
                    if (!in_array('ROLE_JURY', $roles)) {
                        $roles[count($roles)] = 'ROLE_JURY';
                        $user->setRoles($roles);
                    };//Si le compte Olymphys existe déjà, on s'assure que son rôle sera jury
                    $this->doctrine->getManager()->persist($user);
                    $this->doctrine->getManager()->flush();
                }
                $jure = $this->doctrine->getRepository(Jures::class)->findOneBy(['iduser' => $user]);
                if ($jure === null) {//le juré n'existe pas encore
                    $jure = new Jures(); //On crée ce juré
                    $jure->setIduser($user); //On associe le jurécia à compte olymphys
                    $jure->setNomJure($user->getNom());
                    $jure->setPrenomJure($user->getPrenom());
                    if (str_contains($slugger->slug($prenom), '-')) {//Pour éliminer les caratères non ASCII et tenir compte d'un prénom composé
                        $initiales = mb_strtoupper(explode('-', $slugger->slug($prenom))[0][0] . explode('-', $slugger->slug($prenom))[1][0] . $slugger->slug($nom[0]));
                    } elseif (str_contains($slugger->slug($prenom), '_')) {//Pour éliminer les caratères non ASCII  et tenir compte d'un prénom composé mal saisi
                        $initiales = mb_strtoupper(explode('-', $slugger->slug($prenom))[0][0] . explode('-', $slugger->slug($prenom))[1][0] . $slugger->slug($nom[0]));
                    } else {
                        $initiales = mb_strtoupper($slugger->slug($prenom))[0] . mb_strtoupper($slugger->slug($nom))[0];
                    }
                    $jure->setInitialesJure($initiales);
                    $this->doctrine->getManager()->persist($jure);
                    $this->doctrine->getManager()->flush();
                    //$mailer->sendInscriptionJureCN($jure);//envoie d'un mail au juré pour l'informer que son compte jurévcia est ouvert avec copie au comité
                } else {
                    $texte = 'Ce juré existe déjà !';
                    $this->requestStack->getSession()->set('info', $texte);//fenêtre modale d'avertissement déclenchée

                }
                return $this->redirectToRoute('secretariatjury_gestionjures');
            }
        }
        return $this->render('secretariatjury/creejure.html.twig', ['form' => $form->createView()]);
    }

    #[IsGranted('ROLE_SECRETARIAT_JURY')]
    #[Route("/secretariatjury_attribhorairessalles", name: "secretariatjury_attrib_horaires_salles")]
    public function attribHorairesSalles(Request $request)
    {

        $idEquipe = $request->query->get('idequipe');//on récupére les datas envoyées par ajax dans le query via la méthode get
        $type = $request->query->get('type');
        $valeur = $request->query->get('value');
        $equipe = $this->doctrine->getRepository(Equipes::class)->find($idEquipe);
        if ($type == 'heure') {
            $equipe->setHeure($valeur);
            $this->doctrine->getManager()->persist($equipe);
            $this->doctrine->getManager()->flush();
        }
        if ($type == 'salle') {
            $equipe->setSalle($valeur);
            $this->doctrine->getManager()->persist($equipe);
            $this->doctrine->getManager()->flush();
        }
        if ($type == 'ordre') {
            $equipe->setOrdre($valeur);
            $this->doctrine->getManager()->persist($equipe);
            $this->doctrine->getManager()->flush();
        }

        return $this->redirectToRoute('secretariatjury_gestionjures');
    }


    #[IsGranted('ROLE_SECRETARIAT_JURY')]
    #[Route("/secretariatjury_effacerHeure,{idequipe}", name: "secretariatjury_effacer_heure")]
    public function effacerHeure(Request $request, $idequipe)
    {
        $equipe = $this->doctrine->getRepository(Equipes::class)->find($idequipe);
        $equipe->setHeure('00:00');
        $this->doctrine->getManager()->persist($equipe);
        $this->doctrine->getManager()->flush();
        return $this->redirectToRoute('secretariatjury_gestionjures');
    }

    #[\Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted('ROLE_SECRETARIAT_JURY')]
    #[Route("/secretariatjury/charge_jures", name: "secretariatjury_charge_jures")]
    public function charge_jures(Request $request, UserPasswordHasherInterface $passwordEncoder, Mailer $mailer): RedirectResponse|Response //Pour charger le tableau fourni par Pierre
    {

        $defaultData = ['message' => 'Charger le fichier Jures'];
        $form = $this->createFormBuilder($defaultData)
            ->add('fichier', FileType::class,
                ['label' => 'Le fichier doit être de type .csv'])
            ->add('save', SubmitType::class)
            ->getForm();


        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $fichier = $data['fichier'];
            //$spreadsheet = IOFactory::load($fichier);

            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
            $spreadsheet = $reader->load($fichier);
            $worksheet = $spreadsheet->getActiveSheet();

            $highestRow = $spreadsheet->getActiveSheet()->getHighestDataRow();

            $em = $this->doctrine->getManager();
            //$lettres = range('A','Z') ;
            $repositoryEquipes = $this->doctrine->getManager()
                ->getRepository(Equipes::class);
            $equipes = $repositoryEquipes->createQueryBuilder('e')
                ->leftJoin('e.equipeinter', 'eq')
                ->orderBy('eq.lettre', 'ASC')
                ->getQuery()->getResult();


            $repositoryUser = $this->doctrine->getRepository(User::class);
            $message = '';

            for ($row = 2; $row <= $highestRow; ++$row) {

                $nom = $worksheet->getCell([2, $row])->getValue();
                $prenom = $worksheet->getCell([1, $row])->getValue();
                $email = $worksheet->getCell([3, $row])->getValue();
                $initiales = $worksheet->getCell([4, $row])->getValue();

                $qb = $repositoryUser->createQueryBuilder('u');
                $user = $qb//vérification que le juré a déjà un compte user
                ->where('u.email =:email')
                    ->setParameter('email', $email)
                    ->getQuery()->getOneOrNullResult();
                if ($user == null) {//L'user n'existe pas il faut d'abord le créer


                    $user = new User();
                    $slugger = new AsciiSlugger();
                    $prenomNorm = ucfirst(strtolower($slugger->slug($prenom)));
                    $username = $prenomNorm[0] . '_' . $slugger->slug($nom);//Création d'un username avec caratères ASCII
                    $pwd = $prenomNorm;
                    $i = 1;
                    while ($repositoryUser->findBy(['username' => $username])) {//pour éviter des logins identiques on ajoute un numéro à la fin
                        $username = $username . $i;
                        $i = +1;
                    }
                    $user->setUsername($username);
                    $user->setPassword($passwordEncoder->hashPassword($user, $pwd));
                    $user->setEmail($email);
                    $user->setPrenom($prenom);
                    $user->setNom($nom);
                    $user->setRoles(['ROLE_JURY']);
                    $this->doctrine->getManager()->persist($user);
                    $this->doctrine->getManager()->flush();
                    $mailer->sendInscriptionJure($user, $pwd);

                    $message = $message .'<br>'.$prenom.' '. $nom . ' ne correspond pas à un user existant et a été créé, les identifiants ont été envoyé';
                }
                //Si l'user existe
                if ($user !== null) {//certains jurés sont parfois aussi organisateur des cia avec un autre compte.on ne sélectionne que le compte de role jury
                    if (in_array('ROLE_JURY', $user->getRoles())) {//il faut que l'user soit déclaré memebre du jury avant le chargement des attributions
                        $jure = $this->doctrine->getRepository(Jures::class)->findOneBy(['iduser' => $user]);//Evite le problème en cas d'homonymie

                        if ($jure == null) {
                            $jure = new Jures();
                            $jure->setIduser($user);
                        }
                        $jure->setPrenomJure($prenom);
                        $jure->setNomJure($nom);
                        $jure->setInitialesJure($initiales);

                        $colonne = 5;
                        foreach ($equipes as $equipe) {
                            /* $equipe=$repositoryEquipes->createQueryBuilder('e')
                                 ->leftJoin('e.equipeinter','eq')
                                 ->where('eq.lettre =:lettre')
                                 ->setParameter('lettre',  $worksheet->getCell($colonne, 1)->getValue())
                                 ->getQuery()->getSingleResult();*/
                            $value = $worksheet->getCell([$colonne, $row])->getValue();//Le tableau comporte les attributions des jurés classées par lettre équipe croissantes, vide  pas attribué, x examinateur,  L lecteur, R rapporteur

                            switch ($value) {
                                case 'x'://x est la valeur pour les équipes examinée par un juré dans le tableau de Pierre
                                    $value = 0;//0 signifie équipe éxaminée sans lire le mémoire
                                    break;
                                case 'L' :
                                    $value = 1;//1 : lecteur et  évaluation du mémoire
                                    break;
                                case 'R' :
                                    $value = 2;//2 : rapporteur et évaluation du mémoire
                                    break;


                            }
                            //$method = 'set' . $equipe->getEquipeinter()->getLettre();
                            //$jure->$method($value);//rempli la table jures
                            //remplissage de la table attributions
                            $attributions = $jure->getAttributions();//On vérifie que l'équipe n'a pas été déjà attribué au juré
                            $test = false;
                            if ($attributions != null) {
                                foreach ($attributions as $attribution) {//Mise à jour des attributions lors de la lecture du tableau
                                    if ($attribution->getEquipe() == $equipe) {
                                        $test = true;
                                        if ($value != '') {
                                            $attribution->setEstLecteur($value);
                                            $em->persist($attribution);

                                        } else {//Suppression de l'attribution si la cellule est vide.

                                            $note = $this->doctrine->getRepository(Notes::class)->findOneBy(['jure' => $jure, 'equipe' => $equipe]);
                                            if ($note != null) {
                                                $this->doctrine->getManager()->remove($note);
                                            }
                                            $jure->removeAttribution($attribution);
                                            $this->doctrine->getManager()->flush();

                                        }

                                    }
                                }
                                if ($value != '') {
                                    if ($test == false) {//L'attribution est nouvelle , on la crée
                                        $attribution = new Attributions();
                                        $attribution->setJure($jure);
                                        $attribution->setEquipe($equipe);
                                        $attribution->setEstLecteur($value);
                                        $jure->addAttribution($attribution);
                                        $em->persist($attribution);
                                    }

                                }
                            }

                            $colonne += 1;//Chaque colonne correspond à une équipe repérée par sa lettre
                        }
                        $em->persist($jure);
                        $em->flush();
                    } else {//L'user existe mais n'a pas le role JURY
                        $message = $message .'<br>'.$renom.' '.$nom . ' n\'a pas le  ROLE_JURY  et n\'a pu être affecté au jury';
                    }
                }

            }

            $this->requestStack->getSession()->set('info', $message);
            return $this->redirectToRoute('secretariatjury_gestionjures');
        }
        $content = $this
            ->renderView('secretariatadmin\charge_donnees_excel.html.twig', array('titre' => 'Remplissage de la table Jurés', 'form' => $form->createView(),));
        return new Response($content);
    }

    #[IsGranted('ROLE_COMITE')]
    #[Route("secretariatjury/liste_recommandations", name: "secretariatjury_liste_recommandations")]
    public function liste_recommandations(Request $request): Response
    {
        $recommandations = $this->doctrine->getRepository(RecommandationsJuryCn::class)->createQueryBuilder('r')
            ->leftJoin('r.equipe', 'eq')
            ->leftJoin('eq.equipeinter', 'eqi')
            ->orderBy('eqi.lettre', 'ASC')
            ->getQuery()->getResult();

        $listeEquipes = $this->requestStack->getSession()->get('tableau')[0];
        return $this->render('secretariatjury/liste_recommandations.html.twig', ['recommandations' => $recommandations]);


    }

    #[IsGranted('ROLE_COMITE')]
    #[Route("secretariatjury/modif_recommandations,{id}", name: "secretariatjury_modif_recommandations")]
    public function modif_recommandations(Request $request, $id): Response
    {
        $equipe = $this->doctrine->getRepository(Equipes::class)->find($id);
        $recommandation = $this->doctrine->getRepository(RecommandationsJuryCn::class)->findOneBy(['equipe' => $equipe]);
        if ($recommandation == null) {
            $recommandation = new RecommandationsJuryCn();
            $recommandation->setEquipe($equipe);
        }
        $form = $this->createForm(RecommandationsCnType::class, $recommandation);
        $form->handleRequest($request);
        if ($form->isSubmitted() and $form->isValid()) {
            /* $repoRecommandations = $this->doctrine->getRepository(RecommandationsJuryCN::class);
           $valid = $repoRecommandations->valid_nomber_word($recommandation->getTexte());
            if ($valid == false) {
                $this->requestStack->getSession()->set('info', 'Le nombre de mots dépasse 250, veuillez simplifier s\'il vous plaît');
                return $this->render('secretariatjury/modif_recommandation.html.twig', ['form' => $form->createView(), 'recommandation' => $recommandation]);

            }*/
            $this->doctrine->getManager()->persist($recommandation);
            $this->doctrine->getManager()->flush();
            return $this->redirectToRoute('secretariatjury_liste_recommandations');
        }
        return $this->render('secretariatjury/modif_recommandation.html.twig', ['form' => $form->createView(), 'recommandation' => $recommandation]);


    }

    #[IsGranted('ROLE_COMITE')]
    #[Route("secretariatjury/envoi_recommandations", name: "secretariatjury_envoi_recommandations")]
    public function envoi_recommandations(Request $request, Mailer $mailer): Response
    {
        $id = $request->query->get('idrecommandation');
        $recommandations = $this->doctrine->getRepository(RecommandationsJuryCn::class)->createQueryBuilder('r')
            ->leftJoin('r.equipe', 'eq')
            ->leftJoin('eq.equipeinter', 'equi')
            ->orderBy('equi.lettre', 'ASC')
            ->getQuery()->getResult();

        $recommandation = $this->doctrine->getRepository(RecommandationsJuryCn::class)->find($id);
        $equipeinter = $recommandation->getEquipe()->getEquipeinter();
        $prof1 = $equipeinter->getIdProf1();
        $prof2 = $equipeinter->getIdProf2();
        $mailer->sendConseilCn($recommandation, $prof1, $prof2);
        $recommandation->setEnvoyee(true);
        $this->doctrine->getManager()->persist($recommandation);
        $this->doctrine->getManager()->flush();
        return $this->render('secretariatjury/liste_recommandations.html.twig', ['recommandations' => $recommandations]);


    }
}