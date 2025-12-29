<?php

namespace App\Controller;

use App\Entity\Edition;
use App\Entity\Elevesinter;
use App\Entity\Equipes;
use App\Entity\Equipesadmin;
use App\Entity\InscriptionsCN;
use App\Entity\Jures;
use App\Entity\Uai;
use App\Entity\User;
use App\Service\CreateInvitationPdf;
use App\Service\Mailer;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use DoctrineExtensions\Query\Mysql\Time;
use Fpdf\Fpdf;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints\Ip;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function mysql_xdevapi\getSession;


class SecretariatadminController extends AbstractController
{

    public $password;
    private UserPasswordHasherInterface $passwordEncoder;
    private EntityManagerInterface $em;
    private ManagerRegistry $doctrine;
    private RequestStack $requestStack;

    public function __construct(EntityManagerInterface      $em,
                                ValidatorInterface          $validator,
                                ManagerRegistry             $doctrine,
                                UserPasswordHasherInterface $passwordEncoder, RequestStack $requestStack)
    {
        $this->em = $em;
        $this->doctrine = $doctrine;
        $this->requestStack = $requestStack;


        $this->passwordEncoder = $passwordEncoder;


    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route("/secretariatadmin/charge_uai", name: "secretariatadmin_charge_uai")]
    public function charge_uai(Request $request): RedirectResponse|Response
    {
        $defaultData = ['message' => 'Charger le fichier des élèves '];
        $form = $this->createFormBuilder($defaultData)
            ->add('fichier', FileType::class)
            ->add('save', SubmitType::class)
            ->getForm();

        $repositoryUai = $this
            ->doctrine
            ->getManager()
            ->getRepository(Uai::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $fichier = $data['fichier'];
            $spreadsheet = IOFactory::load($fichier);
            $worksheet = $spreadsheet->getActiveSheet();

            $highestRow = $worksheet->getHighestRow();

            $em = $this->doctrine->getManager();

            for ($row = 2; $row <= $highestRow; ++$row) {

                $value = $worksheet->getCell('A' . $row)->getValue();//On lit le uai
                $uai = $repositoryUai->findOneByUai($value);//On vérifie si  cet uai est déjà dans la base
                if (!$uai) { // si le uai n'existe pas, on le crée
                    $uai = new Uai();

                    //sinon on garde les précédentes données tout en les mettant à jour
                    //dd($value);
                    $uai->setUai($value);
                }
                $value = $worksheet->getCell('Q' . $row)->getValue();
                $uai->setNature($value);
                // $value = $worksheet->getCell('J' . $row)->getValue();
                // $uai->setAcheminement($value);
                //$value = $worksheet->getCellByColumnAndRow(4, $row)->getValue();
                //$uai->setSigle($value);
                $value = $worksheet->getCell('F' . $row)->getValue();
                $uai->setCommune($value);
                $value = $worksheet->getCell('J' . $row)->getValue();
                if ($value == null) {
                    $uai->setAcademie('Etranger');
                } else {
                    $uai->setAcademie($value);
                }
                $value = $worksheet->getCell('R' . $row)->getValue();
                if ($value == null) $value = 'France';
                $uai->setPays($value);
                $value = $worksheet->getCell('I' . $row)->getValue();

                $uai->setDepartement($value);
                /* $value = $worksheet->getCell('C' . $row)->getValue()  //Données qui n'ont plus court en 2024
                 $uai->setDenominationPrincipale($value);
                 $value = $worksheet->getCell('B' . $row)->getValue();
                 $uai->setAppellationOfficielle($value);

                */
                $value = $worksheet->getCell('B' . $row)->getValue();
                $uai->setNom(ucwords(strtolower($value)));//à changer les noms composés n'ont plus de majuscule au deuxième nom
                $value = $worksheet->getCell('C' . $row)->getValue();
                $uai->setAdresse($value);
                $value = $worksheet->getCell('D' . $row)->getValue();
                $uai->setBoitePostale($value);
                $value = $worksheet->getCell('E' . $row)->getValue();
                $uai->setCodePostal($value);

                $value = $worksheet->getCell('L' . $row)->getValue();
                $uai->setCoordonneeX($value);
                $value = $worksheet->getCell('M' . $row)->getValue();
                $uai->setCoordonneeY($value);
                $this->em->persist($uai);
                $this->em->flush();

            }
            return $this->redirectToRoute('core_home');
        }
        $content = $this
            ->renderView('secretariatadmin\charge_donnees_excel.html.twig', array('form' => $form->createView(), 'titre' => 'Enregistrer le uai'));
        return new Response($content);

    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route("/secretariatadmin/charge_user", name: "secretariatadmin_charge_user")]
    public function charge_user(Request $request): RedirectResponse|Response
    {
        $defaultData = ['message' => 'Charger le fichier '];
        $form = $this->createFormBuilder($defaultData)
            ->add('fichier', FileType::class)
            ->add('save', SubmitType::class)
            ->getForm();

        $repositoryUser = $this
            ->doctrine
            ->getManager()
            ->getRepository(User::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $fichier = $data['fichier'];
            $spreadsheet = IOFactory::load($fichier);
            $worksheet = $spreadsheet->getActiveSheet();

            $highestRow = $worksheet->getHighestRow();

            $em = $this->doctrine->getManager();

            for ($row = 2; $row <= $highestRow; ++$row) {

                $value = $worksheet->getCellByColumnAndRow(2, $row)->getValue();//on récupère le username
                $username = $value;
                if ($username != null) {
                    $user = $repositoryUser->findOneByUsername($username);
                    if ($user == null) {
                        $user = new user();
                        $user->setCreatedAt(new DateTime('now'));
                        $user->setLastVisit(new DateTime('now'));
                    } //si l'user n'est pas existant on le crée sinon on écrase les anciennes valeurs pour une mise à jour
                    $user->setUsername($username);
                    $value = $worksheet->getCellByColumnAndRow(3, $row)->getValue();//on récupère le role

                    $user->setRoles([$value]);
                    $value = $worksheet->getCellByColumnAndRow(4, $row)->getValue();//password
                    $password = $this->passwordEncoder->hashPassword($user, $value);
                    $user->setPassword($password);
                    $value = $worksheet->getCellByColumnAndRow(5, $row)->getValue();//actif
                    $user->setIsactive($value);
                    $value = $worksheet->getCellByColumnAndRow(6, $row)->getValue();//email
                    $user->setEmail($value);


                    $value = $worksheet->getCellByColumnAndRow(8, $row)->getValue(); //uai
                    $user->setuai($value);
                    $value = $worksheet->getCellByColumnAndRow(9, $row)->getValue(); //adresse
                    $user->setAdresse($value);
                    $value = $worksheet->getCellByColumnAndRow(10, $row)->getValue(); //ville
                    $user->setVille($value);
                    $value = $worksheet->getCellByColumnAndRow(11, $row)->getValue();//code
                    $user->setCode($value);
                    $value = $worksheet->getCellByColumnAndRow(12, $row)->getValue(); //nom
                    $user->setNom($value);
                    $value = $worksheet->getCellByColumnAndRow(13, $row)->getValue();//prenom
                    $user->setPrenom($value);
                    $value = $worksheet->getCellByColumnAndRow(14, $row)->getValue();//phone
                    $user->setPhone($value);
                    $user->setUpdatedAt(new DateTime('now'));

                    /*$errors = $this->validator->validate($user);
                     if (count($errors) > 0) {
                                 $errorsString = (string) $errors;
                                 throw new \Exception($errorsString);
                             }*/
                    $em->persist($user);


                    $em->flush();
                }
            }

            return $this->redirectToRoute('core_home');
        }
        $content = $this
            ->renderView('secretariatadmin\charge_donnees_excel.html.twig', array('form' => $form->createView(), 'titre' => 'Enregistrer les users'));
        return new Response($content);
    }


    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route("/secretariatadmin/cree_equipes", name: "secretariatadmin_cree_equipes")]
    public function cree_equipes(Request $request): RedirectResponse|Response
    {
        $session = $this->requestStack->getSession();
        $form = $this->createFormBuilder()
            ->add('Creer', SubmitType::class)
            ->getForm();

        $repositoryEquipesadmin = $this
            ->doctrine
            ->getManager()
            ->getRepository(Equipesadmin::class);
        $repositoryEquipes = $this
            ->doctrine
            ->getManager()
            ->getRepository(Equipes::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $listEquipesinter = $repositoryEquipesadmin->createQueryBuilder('e')
                ->select('e')
                ->andwhere('e.edition =:edition')
                ->setParameter('edition', $session->get('edition'))
                ->andwhere('e.selectionnee = 1')
                ->orderBy('e.lettre', 'ASC')
                ->getQuery()
                ->getResult();

            $em = $this->doctrine->getManager();
            $i = 1;
            foreach ($listEquipesinter as $equipesel) {

                if (!$repositoryEquipes->findOneBy(['equipeinter' => $equipesel])) {//Vérification de l'existence de cette équipe
                    $equipe = new equipes();
                } else {
                    $equipe = $repositoryEquipes->findOneBy(['equipeinter' => $equipesel]);
                }

                $equipe->setEquipeinter($equipesel);
                $equipe->setOrdre(1);
                $equipe->setRang($i);
                $equipe->setCouleur(0);
                $date = new DateTime('now');
                $heure = '00:00';
                $equipe->setHeure($heure);
                $equipe->setSalle('000');
                $equipe->setClassement(0);

                //$equipe->setTitreProjet($equipesel->getTitreProjet());

                $em->persist($equipe);
                $em->flush();
                $i++;
            }

            return $this->redirectToRoute('core_home');
        }
        $content = $this
            ->renderView('secretariatadmin\creer_equipes.html.twig', array('form' => $form->createView(),));
        return new Response($content);
    }


    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route("/secretariatadmin/charge_equipe_id_uai", name: "secretariatadmin_charge_equipe_id_uai")]
    public function charge_equipe_id_uai(Request $request): RedirectResponse
    {
        $repositoryEquipes = $this->doctrine
            ->getManager()
            ->getRepository(Equipesadmin::class);
        $repositoryUai = $this->doctrine
            ->getManager()
            ->getRepository(Uai::class);
        $equipes = $repositoryEquipes->findAll();
        $em = $this->doctrine->getManager();
        $uais = $repositoryUai->findAll();
        foreach ($equipes as $equipe) {
            foreach ($uais as $uai) {
                if ($uai->getUai() == $equipe->getUai()) {
                    $equipe->setUaiId($uai);
                }
            }
            $em->persist($equipe);
            $em->flush();

        }
        return $this->redirectToRoute('core_home');


    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route("/secretariatadmin/set_editon_equipe", name: "secretariatadmin_set_editon_equipe")]
    public function set_edition_equipe(Request $request): RedirectResponse
    {
        $repositoryEquipes = $this->doctrine
            ->getManager()
            ->getRepository(Equipesadmin::class);
        $repositoryEleves = $this->doctrine
            ->getManager()
            ->getRepository(Elevesinter::class);
        $repositoryEdition = $this->doctrine
            ->getManager()
            ->getRepository(Edition::class);
        $qb = $repositoryEquipes->CreateQueryBuilder('e')
            ->where('e.edition is NULL')
            ->andWhere('e.numero <:nombre')
            ->setParameter('nombre', '100');

        $Equipes = $qb->getQuery()->getResult();


        $edition = $repositoryEdition->find(['id' => 1]);

        foreach ($Equipes as $equipe) {
            if (null == $equipe->getEdition()) {

                $em = $this->doctrine->getManager();
                $equipe->setEdition($edition);
                $em->persist($equipe);
                $em->flush();
            }

        }
        return $this->redirectToRoute('core_home');
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route("/secretariatadmin/modif_equipe,{idequipe}", name: "modif_equipe")]
    public function modif_equipe(Request $request, $idequipe): RedirectResponse|Response
    {
        $em = $this->doctrine->getManager();
        $repositoryEquipesadmin = $this->doctrine
            ->getManager()
            ->getRepository(Equipesadmin::class);


        $repositoryElevesinter = $this->doctrine
            ->getManager()
            ->getRepository(Elevesinter::class);

        $equipe = $repositoryEquipesadmin->findOneById(['id' => $idequipe]);
        $listeEleves = $repositoryElevesinter->findByEquipe(['equipe' => $equipe]);
        $i = 0;
        $form[$i] = $this->createFormBuilder($equipe)
            ->add('titreprojet', TextType::class, [
                'mapped' => false,
                'data' => $equipe->getTitreprojet(),

            ])
            ->add('saveE', SubmitType::class, ['label' => 'Sauvegarder'])
            ->getForm();
        $form[$i]->handleRequest($request);
        $formview[$i] = $form[$i]->createView();
        if ($form[$i]->isSubmitted() && $form[$i]->isValid()) {
            if ($form[$i]->get('saveE')->isClicked()) {
                $em->persist($equipe);
                $em->flush();
            }
            return $this->redirectToRoute('modif_equipe', array('idequipe' => $idequipe));
        }
        $i++;
        foreach ($listeEleves as $eleve) {
            $form[$i] = $this->createFormBuilder()
                ->add('nom', TextType::class, [
                    'mapped' => false,
                    'data' => $eleve->getNom(),
                ])
                ->add('prenom', TextType::class, [
                    'mapped' => false,
                    'data' => $eleve->getPrenom(),
                ])
                ->add('courriel', EmailType::class, [
                    'mapped' => false,
                    'data' => $eleve->getCourriel(),
                ])
                ->add('id', HiddenType::class, [
                    'mapped' => false,
                    'data' => $eleve->getId(),
                ])
                ->add('save' . $i, SubmitType::class, ['label' => 'Sauvegarder'])
                ->getForm();
            $form[$i]->handleRequest($request);

            $formview[$i] = $form[$i]->createView();
            $i++;
        }
        $imax = $i;

        for ($i = 1; $i < $imax; $i++) {
            if ($form[$i]->isSubmitted() && $form[$i]->isValid()) {

                if ($form[$i]->get('save' . $i)->isClicked()) {


                    $elevemodif = $repositoryElevesinter->findOneById(['id' => $form[$i]->get('id')->getData()]);
                    $elevemodif->setNom($form[$i]->get('nom')->getData());
                    $elevemodif->setPrenom($form[$i]->get('prenom')->getData());
                    $elevemodif->setCourriel($form[$i]->get('courriel')->getData());
                    $em->persist($elevemodif);
                    $em->flush();
                }

                return $this->redirectToRoute('modif_equipe', array('idequipe' => $idequipe));
            }


        }


        return $this->render('adminfichiers/modif_equipe.html.twig', [
            'formtab' => $formview, 'equipe' => $equipe]);
    }


    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route("/secretariatadmin/youtube_remise_des prix", name: "secretariatadmin_youtube_remise_des_prix")]
    public function youtube_remise_des_prix(Request $request): RedirectResponse|Response

    {
        $repositoryEdition = $this->doctrine->getRepository(Edition::class);
        $editions = $repositoryEdition->findAll();
        $i = 0;
        foreach ($editions as $edition_) {
            $ids[$i] = $edition_->getId();
            $i++;
        }
        $id = max($ids);
        $edition = $repositoryEdition->findOneBy(['id' => $id]);


        $form = $this->createFormBuilder()
            ->add('lien', TextType::class, [
                'required' => false,
                'data' => $edition->getLienYoutube()

            ])
            ->add('valider', SubmitType::class);
        $Form = $form->getForm();
        $Form->handleRequest($request);
        if ($Form->isSubmitted() && $Form->isValid()) {

            $edition->setLienYoutube($Form->get('lien')->getData());

            $this->em->persist($edition);
            $this->em->flush();

            return $this->redirectToRoute('core_home');

        }
        return $this->render('core/lien_video.html.twig', array('form' => $Form->createView()));

    }

    #[IsGranted("ROLE_JURY")]
    #[Route("/secretariatadmin/invitations_cn", name: "invitations_cn")]
    public function createinvitationCnPdf(Request $request, Mailer $mailer): Response
    {
        $robots = $this->doctrine->getRepository(InscriptionsCN::class)->createQueryBuilder('i')
            ->select('i')
            ->where('i.ipAdress is not null')
            ->getQuery()->getResult();
        $ipAdresses = [];
        foreach ($robots as $robot) {//on teste si le robot fait des requêtes en série, auquel cas il est redirigé ailleurs
            if (in_array($_SERVER['REMOTE_ADDR'], $robot->getIpAdresses())) {

                return $this->redirect('https://rien');

            }

        }
        $slugger = new AsciiSlugger();

        if ($_SERVER['SERVER_NAME'] == 'www.olymphys.fr') {
            $path = 'https://www.olymphys.fr/public/odpf/odpf-images/';
        };
        if (str_contains($_SERVER['SERVER_NAME'], 'olympessais.')) {
            $path = 'https://www.olymphys.fr/public/odpf/odpf-images/';
        };
        if ($_SERVER['SERVER_NAME'] == '127.0.0.1' or $_SERVER['SERVER_NAME'] == 'localhost') {
            $path = 'odpf/odpf-images/';
        }
        $builder = $this->createFormBuilder()
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'NOM']

            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['placeholder' => 'Prénom']

            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['placeholder' => 'E-mail']

            ])
            ->add('mail2', EmailType::class, [//antirobot si on rend public le formulaire:  les robots vont remplir ce champ qui doit rester vide
                'label' => ' ',
                'required' => false,
                'attr' => ['placeholder' => 'E-mail', 'color' => 'white', 'hidden' => 'true']

            ]);
        if ($this->getUser()) {
            $builder->add('politesse', ChoiceType::class, [
                'choices' => ['le plaisir' => 'le plaisir', 'l\'honneur' => 'l\'honneur'],
                'label' => 'Choisir la formule de politesse qui convient'
            ]);
        }
        $builder->add('valider', SubmitType::class, ['label' => 'Créer et envoyer l\'invitation']);
        $form = $builder->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('mail2')->getData() == null) {//pour éviter les robots si on décide rendre publique cette fonction
                $nom = $slugger->slug(mb_strtoupper($form['nom']->getData()));
                $prenom = $slugger->slug($form['prenom']->getData());
                $politesse = 'le plaisir';
                $flyer = 'flyer-inscriptionExt-.pdf';
                if ($this->getUser()) {
                    $politesse = $form['politesse']->getData();
                    $flyer = 'flyer.pdf';
                }
                $mail = $form['email']->getData();
                $quidam = [$nom, $prenom, $mail];
                $createPdf = new CreateInvitationPdf();
                $pdf = $createPdf->createInvitationPdf($quidam, '32');
                $fileNamepdf = $this->getParameter('app.path.tempdirectory') . '/32-' . $prenom . '_' . $nom . '.pdf';
                $flyer = $this->getParameter('app.path.odpf_archives') . '/32/documents/' . $flyer;
                $pdf->Output('F', $fileNamepdf);
                $e = null;
                try {
                    $mailer->sendIntivationCn($quidam, $fileNamepdf, $flyer, $politesse);
                } catch (\Exception $e) {


                }

                if ($e === null) {

                    $inscription = new InscriptionsCN();
                    $inscription->setNom($nom);
                    $inscription->setPrenom($prenom);
                    //$inscription->setEmail($mail);
                    $qualite = 'Visiteur';
                    if ($this->getUser()) {
                        if (in_array('ROLE_COMITE', $this->getUser()->getRoles())) {
                            $qualite = '';
                        }
                        if (in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
                            $qualite = '';
                        }
                        if (in_array('ROLE_JURY', $this->getUser()->getRoles())) {

                            $qualite = '';
                        }
                        if (in_array('ROLE_ORGACIA', $this->getUser()->getRoles())) {

                            $qualite = '';
                        }
                    }
                    $inscription->setEmail($mail);
                    $inscription->setQualite($qualite);
                    $this->em->persist($inscription);
                    $this->em->flush();


                    $this->requestStack->getSession()->set('info', 'L\'invitation  de  ' . $prenom . ' ' . $nom . ' à bien été envoyée à l\'adresse : ' . $mail);

                } else {

                    $this->requestStack->getSession()->set('info', 'Une erreur est survenue lors de l\'envoi de l\'invitation.');
                }

                unlink($fileNamepdf);
                if ($this->getUser()) {
                    if (in_array('ROLE_COMITE', $this->getUser()->getRoles())) {
                        return $this->redirectToRoute('admin');
                    }
                    if (in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
                        return $this->redirectToRoute('admin');
                    }
                    if (in_array('ROLE_JURY', $this->getUser()->getRoles())) {

                        return $this->redirectToRoute('fichiers_choix_equipe', ['choix' => 'liste_cn_comite']);
                    }
                    if (in_array('ROLE_ORGACIA', $this->getUser()->getRoles())) {

                        return $this->redirectToRoute('fichiers_choix_equipe', ['choix' => $this->getUser()->getCentrecia()->getCentre()]);
                    }
                }
                //pour les visiteurs, on redirige vers la page du concours national


                return $this->redirectToRoute('core_pages', ['choix' => 'le_concours_national']);


            }
            //on relève l'adresse IP du robot
            $inscription = new InscriptionsCN();
            $inscription->setNom('robot');
            $inscription->setPrenom('robot');
            $inscription->setEmail('robot');
            $inscription->setIpAdress($_SERVER['REMOTE_ADDR']);
            $this->em->persist($inscription);
            $this->em->flush();


        }

        return $this->render('secretariatadmin/invitations_cn.html.twig', ['form' => $form->createView()]);
    }

    #[IsGranted('ROLE_COMITE')]
    #[Route("/secretariatadmin/liste_excel_invitations_cn", name: "liste_excel_invitations_cn")]
    public function listeExcelInvitation()
    {

        $invitations = $this->doctrine->getRepository(InscriptionsCN::class)->findAll();
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator("Olymphys")
            ->setLastModifiedBy("Olymphys")
            ->setTitle("CN  32e édition -Tableau destiné au comité")
            ->setSubject("Tableau destiné au comité")
            ->setDescription("Office 2007 XLSX liste des invitations au cn")
            ->setKeywords("Office 2007 XLSX")
            ->setCategory("Test result file");

        $sheet = $spreadsheet->getActiveSheet();
        $ligne = 1;
        $sheet->getStyle('A' . $ligne)->getFont()->setBold(true)->setSize('18');
        $sheet->getRowDimension($ligne)->setRowHeight(30);
        $sheet->setCellValue("A" . $ligne, "Listes des personnes invitées pour les Olympiades de Physique France");
        /*foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'] as $letter) {
            $sheet->getColumnDimension($letter)->setAutoSize(true);
        }*/
        $sheet->getStyle('A' . $ligne . ':D' . $ligne)
            ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getColumnDimension("A")->setWidth(40);
        $sheet->getColumnDimension("B")->setWidth(40);
        $sheet->getColumnDimension("C")->setWidth(40);
        $sheet->getColumnDimension("D")->setWidth(40);
        $ligne++;
        $sheet->getStyle('A' . $ligne . ':D' . $ligne)
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension($ligne)->setRowHeight(20);
        $sheet->getStyle('A' . $ligne . ':D' . $ligne)->getFont()->setBold(true)->setSize('14');
        $sheet
            ->setCellValue('A' . $ligne, 'NOM')
            ->setCellValue('B' . $ligne, 'Prénom')
            ->setCellValue('C' . $ligne, 'Qualité')
            ->setCellValue('D' . $ligne, 'Email');
        $ligne++;

        foreach ($invitations as $invitation) {
            $sheet->getRowDimension($ligne)->setRowHeight(20);
            $sheet
                ->setCellValue('A' . $ligne, $invitation->getNom())
                ->setCellValue('B' . $ligne, $invitation->getPrenom())
                ->setCellValue('C' . $ligne, $invitation->getQualite())
                ->setCellValue('D' . $ligne, $invitation->getEmail());

            $ligne++;
        }

        $filename = 'Liste_des invitations au cn de Marseille.xls';
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xls($spreadsheet);
//$writer= PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
//$writer =  \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
// $writer =IOFactory::createWriter($spreadsheet, 'Xlsx');
        ob_end_clean();
        $writer->save('php://output');


    }

}