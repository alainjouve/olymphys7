<?php

namespace App\Controller;


use App\Entity\Centrescia;
use App\Entity\Edition;
use App\Entity\Equipesadmin;
use App\Entity\Odpf\OdpfEditionsPassees;
use App\Entity\Odpf\OdpfEquipesPassees;
use App\Entity\Photos;
use App\Form\ConfirmType;
use App\Form\PhotosType;
use App\Form\TelechargementPhotosType;
use Imagick;
use ImagickException;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use ZipArchive;


class PhotosController extends AbstractController
{
    private RequestStack $requestStack;
    private \Doctrine\Persistence\ManagerRegistry $doctrine;


    public function __construct(RequestStack $requestStack, \Doctrine\Persistence\ManagerRegistry $doctrine)
    {
        $this->requestStack = $requestStack;
        $this->doctrine = $doctrine;
    }

    #[IsGranted("ROLE_PROF")]
    #[Route("/photos/deposephotos,{concours}", name: "photos_deposephotos")]
    public function deposephotos(Request $request, ValidatorInterface $validator, $concours)
    {
        $em = $this->doctrine->getManager();

        $repositoryEquipesadmin = $this->doctrine
            ->getManager()
            ->getRepository(Equipesadmin::class);
        $repositoryPhotos = $this->doctrine
            ->getManager()
            ->getRepository(Photos::class);


        $editionId = $this->requestStack->getSession()->get('edition')->getId();
        $edition = $this->doctrine->getRepository(Edition::class)->findOneBy(['id' => $editionId]);


        $user = $this->getUser();
        $id_user = $user->getId();
        $roles = $user->getRoles();
        in_array('ROLE_PROF', $roles) ? $role = 'ROLE_PROF' : $role = 'ROLE_COMITE';
        in_array('ROLE_ORGACIA', $roles) ? $centre = $user->getCentrecia()->getCentre() : $centre = '';
        $photos = new Photos();
        //$photos->setEdition($edition);
//$Photos->setSession($session);
        $form = $this->createForm(PhotosType::class, ['concours' => $concours, 'role' => $role, 'prof' => $user, 'centre' => $centre]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $equipe = $form->get('equipe')->getData();
//$equipe=$repositoryEquipesadmin->findOneBy(['id'=>$id_equipe]);
            $nom_equipe = $equipe->getTitreProjet();
            $edition = $equipe->getEdition();
            $numero_equipe = $equipe->getNumero();
            $files = $form->get('photoFiles')->getData();
            $editionpassee = $this->doctrine->getRepository(OdpfEditionsPassees::class)->findOneBy(['edition' => $edition->getEd()]);
            $equipepassee = $this->doctrine->getRepository(OdpfEquipesPassees::class)->findOneBy(['editionspassees' => $editionpassee, 'numero' => $equipe->getNumero()]);
            $type = true;
            if ($files) {
                $nombre = count($files);

                $fichiers_erreurs = [];
                $i = 0;
                foreach ($files as $file) {



                    //La checkbox m'apparaît pas dans la liste des paramètres transmis si elle est décochée
                    if (isset($_REQUEST['checkbox-' . explode('.',$file->getClientOriginalName())[0]])) {
                        $violations = $validator->validate(
                            $file,
                            [
                                new NotBlank(),
                                new File([
                                    'maxSize' => '13000k',
                                ])
                            ]
                        );
                        $typeImage = $file->guessExtension();//Les .HEIC donnent jpg
                        $originalFilename = $file->getClientOriginalName();
                        $parsedName = explode('.', $originalFilename);
                        $ext = end($parsedName);// détecte les .JPG et .HEIC

                        if (($typeImage != 'jpg') or ($ext != 'jpg')) {// on transforme  le fichier en .JPG
                            // dd('OK');
                            $nameExtLess = $parsedName[0];
                            $imax = count($parsedName);
                            for ($i = 1; $i <= $imax - 2; $i++) {// dans le cas où le nom de  fichier comporte plusieurs points
                                $nameExtLess = $nameExtLess . '.' . $parsedName[$i];
                            }
                            try {//on dépose le fichier dans le temp
                                $file->move(
                                    'temp/',
                                    $originalFilename
                                );
                            } catch (FileException $e) {

                            }
                            $this->setImageType($originalFilename, $nameExtLess, 'temp/');//appelle de la fonction de transformation de la compression

                            if (isset($_REQUEST['erreur'])) {

                                unlink('temp/' . $originalFilename);
                                $type = false;
                            }
                            if (!isset($_REQUEST['erreur'])) {
                                $type = true;
                                if (file_exists('temp/' . $nameExtLess . '.jpg')) {
                                    $size = filesize('temp/' . $nameExtLess . '.jpg');
                                } else($size = 10000000);
                                $file = new UploadedFile('temp/' . $nameExtLess . '.jpg', $nameExtLess . '.jpg', $size, null, true);
                                //unlink('temp/' . $originalFilename);
                            }
                        }


                        if (($violations->count() > 0) or ($type == false)) {
                            $violation = '';
                            /** @var ConstraintViolation $violation */
                            if (isset($violations[0])) {
                                $violation = 'fichier de taille supérieure à 7 M';
                            }
                            /*if ($ext != 'jpg') {
                                $violation = $violation . ':  fichier non jpeg ';
                            }*/
                            $fichiers_erreurs[$i] = $file->getClientOriginalName() . ' : ' . $violation;
                            $i++;
                        } else {
                            $photo = new Photos();
                            $photo->setEdition($edition);
                            $photo->setEditionspassees($editionpassee);
                            if ($concours == 'inter') {//Un membre du comité peut vouloir déposer une photo interacadémique lors du concours national
                                $photo->setNational(FALSE);
                            }
                            if (($equipe->getLettre() !== null) and ($concours == 'cn')) {

                                $photo->setNational(TRUE);
                            }
                            if ($equipe->getNumero() >= 100) { //ces "équipes" sont des équipes techniques remise des prix, ambiance du concours, etc, ...
                                $photo->setNational(TRUE);
                            }
                            if ($equipe->getNumero() >= 200) { //ces "équipes" sont des équipes techniques des CIA, ambiance du concours, etc, ...
                                $photo->setNational(FALSE);
                            }
                            $photo->setPhotoFile($file);//Vichuploader gère l'enregistrement dans le bon dossier, le renommage du fichier
                            $photo->setEquipe($equipe);
                            $photo->setEquipepassee($equipepassee);
                            $em->persist($photo);
                            $em->flush();

                        }
                    }
                }

                if (count($fichiers_erreurs) == 0) {
                    if ($nombre == 1) {
                        $message = 'Votre fichier a bien été déposé. Merci !';
                    } else {
                        $message = 'Vos fichiers ont bien été déposés. Merci !';
                    }
                    $request->getSession()
                        ->getFlashBag()
                        ->add('info', $message);
                } else {
                    $message = '';


                    foreach ($fichiers_erreurs as $erreur) {
                        $message = $message . $erreur . ', ';
                    }
                    if (count($fichiers_erreurs) == 1) {
                        $message = $message . ' n\'a pas pu être déposé';
                    }
                    if (count($fichiers_erreurs) > 1) {
                        $message = $message . ' n\'ont pas pu être déposés';
                    }


                    $request->getSession()
                        ->getFlashBag()
                        ->add('alert', 'Des erreurs ont été constatées : ' . $message);

                }
            }
            if (!$files) {
                $request->getSession()
                    ->getFlashBag()
                    ->add('alert', 'Pas fichier sélectionné: aucun dépôt effectué !');
            }
            return $this->redirectToRoute('photos_deposephotos', array('concours' => $concours));
        }
        $Form = $form->createView();

        return $this->render('photos/deposephotos.html.twig', [
            'form' => $Form, 'edition' => $edition, 'concours' => $concours,
        ]);
    }

    public function setImageType($image, $nameExtLess, $path)
    {
        try {
            $imageOrig = new Imagick($path . $image);
            $imageOrig->readImage($path . $image);
            $imageOrig->setImageCompression(Imagick::COMPRESSION_JPEG);
            $imageOrig->setType(Imagick::IMGTYPE_TRUECOLOR);

            $imageOrig->writeImage($path . $nameExtLess . '.jpg');
        } catch (\Exception $e) {


            $_REQUEST['erreur'] = 'yes';

        }

    }

    /**
     * @throws \DateMalformedStringException
     */
    #[Isgranted("ROLE_PROF")]
    #[Route("/photos/gestion_photos, {infos}", name: "photos_gestion_photos")]
    public function gestion_photos(Request $request, $infos)
    {
        $slugger = new AsciiSlugger();
        $choix = explode('-', $infos)[3];
        $roles = $this->getUser()->getRoles();

        $repositoryEdition = $this->doctrine
            ->getManager()
            ->getRepository(Edition::class);

        $repositoryEquipesadmin = $this->doctrine
            ->getManager()
            ->getRepository(Equipesadmin::class);
        $repositoryPhotos = $this->doctrine
            ->getManager()
            ->getRepository(Photos::class);


        $repositoryCentrescia = $this->doctrine
            ->getManager()
            ->getRepository(Centrescia::class);
        $user = $this->getUser();
        $id_user = $user->getId();

        $concourseditioncentre = explode('-', $infos);
        $concours = $concourseditioncentre[0];

        $editionN = $repositoryEdition->find(['id' => $concourseditioncentre[1]]);
        $editionN1 = $repositoryEdition->findOneBy(['ed' => $editionN->getEd() - 1]);
        new \DateTime('now') >= $this->requestStack->getSession()->get('edition')->getDateOuvertureSite() ? $edition = $editionN : $edition = $editionN1;
        $datecia = $this->requestStack->getSession()->get('edition')->getConcoursCia()->format('Y-m-d');
        $datelimite = date_modify(new \DateTime($datecia), '+30days');
        if (new \DateTime('now') <= $edition->getDateOuvertureSite()) {//Dans la période de 30 jours qui suit le CIA , les profs peuvent gérer les photos des cia

            $concours = 'inter';
        }
        if ($concours == 'inter') {

            $qb = $repositoryEquipesadmin->createQueryBuilder('e')
                ->andWhere('e.edition =:edition')
                ->setParameter('edition', $edition)
                ->addOrderBy('e.numero', 'ASC');
            if (in_array('ROLE_COMITE', $user->getRoles())) {
                $centre = $repositoryCentrescia->find(['id' => $concourseditioncentre[2]]);//pour les membres du comité
                if ($centre == null) {
                    $request->getSession()
                        ->set('info', 'Les centres interacadémiques ne sont pas encore attribués pour la ' . $edition->getEd() . 'e édition');
                    $this->redirectToRoute('core_home');
                }
            }
            if ((in_array('ROLE_ORGACIA', $roles)) or (in_array('ROLE_SUPER_ADMIN', $roles)) or (in_array('ROLE_COMITE', $roles))) {
                $centre = $repositoryCentrescia->find(['id' => $concourseditioncentre[2]]);//pour les organisateurs cia
                $ville = $centre->getCentre();
                $qb->andWhere('e.centre=:centre')
                    ->setParameter('centre', $centre);
            }
            if (in_array('ROLE_PROF', $user->getRoles())) {
                if (new \DateTime('now') <= $datelimite) {


                    $ville = 'prof';
                    $qb->andWhere('e.idProf1 =:prof or e.idProf2 =:prof')
                        ->setParameter('prof', $id_user);
                }

            }

            $liste_equipes = $qb->getQuery()->getResult();


            $qb2 = $repositoryPhotos->createQueryBuilder('p')
                ->andWhere('p.national =:valeur')
                ->setParameter('valeur', '0')
                ->andWhere('p.edition =:edition')
                ->setParameter('edition', $edition)
                ->andWhere('p.equipe in(:equipes)')
                ->setParameter('equipes', $liste_equipes)
                ->leftJoin('p.equipe', 'e')
                ->addOrderBy('e.numero', 'ASC');


            /* if ($role=='ROLE_PROF'){
            $qb2->leftJoin('p.equipe','e')
            ->andWhere('e.idProf1 =:prof1')
            ->setParameter('prof1',$id_user)
            ->orWhere('e.idProf2 =:prof2')
            ->setParameter('prof2',$id_user);
            }*/
            $liste_photos = $qb2->getQuery()->getResult();


        }

        if ($concours == 'cn') {

            $equipe = $repositoryEquipesadmin->findOneBy(['id' => $concourseditioncentre[2]]);

            $equipes = $repositoryEquipesadmin->createQueryBuilder('eq')
                ->andWhere('eq.selectionnee = TRUE')
                ->andWhere('eq.idProf1 =:prof or eq.idProf2 =:prof')
                ->setParameter('prof', $id_user)
                ->andWhere('eq.edition =:edition')
                ->setParameter('edition', $edition)
                ->getQuery()->getResult();

            $qb2 = $repositoryPhotos->createQueryBuilder('p')
                ->andWhere('p.national = 1')
                ->andWhere('p.equipe in(:equipes)')
                ->setParameter('equipes', $equipes)
                ->leftJoin('p.equipe', 'e')
                ->addOrderBy('e.lettre', 'ASC');


        }
        $liste_photos = $qb2->getQuery()->getResult();
        if (!$liste_photos) {
            $request->getSession()
                ->set('info', 'Pas de photo pour le concours ' . $concours . ' de l\'édition ' . $edition->getEd() . ' à ce jour');
            return $this->redirectToRoute('fichiers_choix_equipe', array('choix' => 'liste_prof'));
        }

        if ($request->get('enregistrer')) {
            $em = $this->doctrine->getManager();
            $photo = $repositoryPhotos->find(['id' => $request->get('id')]);
            $photo->setComent($request->get('coment'));
            $nlleEquipe = $em->getRepository(Equipesadmin::class)->find($request->get('equipe'));
            $equipe = $photo->getEquipe();
            if ($equipe != $nlleEquipe) {
                $editionpassee = $this->doctrine->getRepository(OdpfEditionsPassees::class)->findOneBy(['edition' => $edition->getEd()]);
                $nllequipepassee = $this->doctrine->getRepository(OdpfEquipesPassees::class)->findOneBy(['numero' => $nlleEquipe->getNumero(), 'editionspassees' => $editionpassee]);

                $photo->setEquipe($nlleEquipe);
                $photo->setEquipepassee($nllequipepassee);
                $nomPhoto = $photo->getPhoto();
                $nomdecomp = explode('-', $nomPhoto);
                $finNom = $nomdecomp[count($nomdecomp) - 1];
                $numero = $nlleEquipe->getNumero();
                if ($nlleEquipe->getLettre()) {
                    $numero = $nlleEquipe->getNumero() . '-' . $nlleEquipe->getLettre();
                }
                $nouvNom = $edition->getEd() . '-' . $slugger->slug($nlleEquipe->getCentre())->toString() . '-eq' . $numero . '-' . $slugger->slug($nlleEquipe->getTitreProjet())->toString() . '-' . $finNom;
                $pathnouvNom = 'odpf/odpf-archives/' . $edition->getEd() . '/photoseq/' . $edition->getEd() . '-' . $slugger->slug($nlleEquipe->getCentre())->toString() . '-eq' . $numero . '-' . $slugger->slug($nlleEquipe->getTitreProjet())->toString() . '-' . $finNom;
                $pathnouvNomThumbs = 'odpf/odpf-archives/' . $edition->getEd() . '/photoseq/thumbs/' . $edition->getEd() . '-' . $slugger->slug($nlleEquipe->getCentre())->toString() . '-eq' . $numero . '-' . $slugger->slug($nlleEquipe->getTitreProjet())->toString() . '-' . $finNom;
                rename('odpf/odpf-archives/' . $edition->getEd() . '/photoseq/' . $photo->getPhoto(), $pathnouvNom);
                rename('odpf/odpf-archives/' . $edition->getEd() . '/photoseq/thumbs/' . $photo->getPhoto(), $pathnouvNomThumbs);
                $photo->setPhoto($nouvNom);

            }


            $em->persist($photo);
            $em->flush();
            return $this->redirectToRoute('photos_gestion_photos', array('infos' => $infos));

        }
        if ($request->get('supprimer')) {
            $photo = $repositoryPhotos->find(['id' => $request->get('id')]);

            return $this->redirectToRoute('photos_confirme_efface_photo', array('concours_photoid_infos' => $concours . ':' . $photo->getId() . ':' . $infos));
        }
        if ($liste_photos == []) {
            $request->getSession()->set('info', 'Vous n\'avez pas déposé de photo pour le concours ' . $concours . ' de l\'édition ' . $edition->getEd() . ' à ce jour');
            return $this->redirectToRoute('core_home');


        }


        if ($concours == 'inter') {
            $content = $this
                ->renderView('photos/gestion_photos_cia.html.twig', array(
                    'liste_photos' => $liste_photos, 'centre' => $ville, 'choix' => $choix,
                    'edition' => $edition, 'liste_equipes' => $liste_equipes, 'concours' => 'cia', 'infos' => $infos));
            return new Response($content);
        }

        if ($concours == 'cn') {

            $content = $this
                ->renderView('photos/gestion_photos_cn.html.twig', array('liste_photos' => $liste_photos,
                    'edition' => $edition, 'equipe' => $equipe, 'concours' => 'national', 'choix' => $choix, 'infos' => $infos));
            return new Response($content);
        }

    }

    #[IsGranted("ROLE_PROF")]
    #[Route("/photos/confirme_efface_photo, {concours_photoid_infos}", name: "photos_confirme_efface_photo")]
    public function confirme_efface_photo(Request $request, $concours_photoid_infos)
    {
        $filesystem = new Filesystem();
        $photoid_concours = explode(':', $concours_photoid_infos);
        $photoId = $photoid_concours[1];
        $concours = $photoid_concours[0];
        $infos = $photoid_concours[2];


        $repositoryPhotos = $this->doctrine
            ->getManager()
            ->getRepository(Photos::class);

        $photo = $repositoryPhotos->find(['id' => $photoId]);


        $Form = $this->createForm(ConfirmType::class);
        $Form->handleRequest($request);
        $form = $Form->createView();
        if ($Form->isSubmitted() && $Form->isValid()) {

            if ($Form->get('OUI')->isClicked()) {

                $em = $this->doctrine->getManager();
                $em->remove($photo);
                $em->flush();
                $filesystem->remove('/upload/photos/thumbs/' . $photo->getPhoto());
                return $this->redirectToRoute('photos_gestion_photos', array('infos' => $infos));
            }
            if ($Form->get('NON')->isClicked()) {
                return $this->redirectToRoute('photos_gestion_photos', array('infos' => $infos));
            }
        }


        $content = $this->renderView('/photos/confirm_supprimer.html.twig', array('form' => $form, 'photo' => $photo, 'concours' => $concours));
        return new Response($content);


    }

    #[Route("/photos/voirgalerie {infos}", name: "photos_voir_galerie")]
    public function voirgalerie(Request $request, $infos)
    {

        $repositoryPhotos = $this->doctrine
            ->getManager()
            ->getRepository(Photos::class);
        $repositoryEquipe=$this->doctrine->getRepository(OdpfEquipesPassees::class);

        if (explode('-', $infos)[0] == 'equipe') {
            $idEquipe = explode('-', $infos)[1];
            $equipe = $repositoryEquipe->findOneBy(['id'=>$idEquipe]);
            $edition = $equipe->getEditionspassees();
            $photosequipes = $this->getPhotosEquipes($edition);
            $equipes=$this->getEquipes($edition);
            $keys=array_keys($equipes);


            if(isset(explode('-', $infos)[2])) {//on a cliqué sur une flèche équipe suivante ou précédente
                $numprecsuiv= explode('-', $infos)[2];
                $equipe = $repositoryEquipe->createQueryBuilder('e')
                ->select('e')
                ->where('e.editionspassees =:edition')
                ->andWhere('e.numero =:numero')
                    ->setParameter('edition', $edition)
                    ->setParameter('numero', $numprecsuiv)
                    ->getQuery()
                    ->getOneOrNullResult();
            }
            $numerosuiv=$equipe->getNumero()+1;
            $numeroprec=$equipe->getNumero()-1;
            foreach($keys as $key){//Pour encadrer l'équipe avec le numero précédent et le numero suivant
                if($equipes[$key]==$equipe) {
                    if($key<max($keys)) {
                        $numerosuiv = $equipes[$key + 1]->getNumero();
                    }
                    if($key==max($keys)) $numerosuiv=1;
                    if($key>min($keys)) {
                        $numeroprec = $equipes[$key - 1]->getNumero();
                    }
                    if($key==min($keys)) $numeroprec=$equipes[max($keys)]->getNumero();
                }
            }

            $photos = $repositoryPhotos->findBy(['equipepassee' => $equipe]);
            $listeEquipes = [$equipe];
            $edition = $equipe->getEditionspassees();
            return $this->render('photos/affiche_galerie_equipe.html.twig', ['photos' => $photos, 'liste_equipes' => $listeEquipes, 'edition' => $edition, 'photosequipes' => $photosequipes,'numero_prec'=>$numeroprec,'numero_suiv'=>$numerosuiv   ]);

        }
        if (explode('-', $infos)[0] == 'edition' or explode('-', $infos)[0] == 'editionEnCours') {

            $idEdition = explode('-', $infos)[1];

            if (explode('-', $infos)[0] == 'edition') {


                $edition = $this->doctrine->getRepository(OdpfEditionsPassees::class)->findOneBy(['id' => $idEdition]);
            }
            if (explode('-', $infos)[0] == 'editionEnCours') {

                $editionEnCours = $this->doctrine->getRepository(Edition::class)->findOneBy(['id' => $idEdition]);

                $edition = $this->doctrine->getRepository(OdpfEditionsPassees::class)->findOneBy(['edition' => $editionEnCours->getEd()]);
            }
            $photos = $this->getPhotosEquipes($edition);
            $listeEquipes = $this->doctrine->getRepository(OdpfEquipesPassees::class)
                ->createQueryBuilder('e')
                ->andWhere('e.editionspassees =:edition')
                ->setParameter('edition', $edition)
                ->addOrderBy('e.numero', 'ASC')
                ->getQuery()->getResult();
            if (isset($photos)) {
                return $this->render('photos/affiche_galerie_edition.html.twig', ['photos' => $photos, 'liste_equipes' => $listeEquipes, 'edition' => $edition]);
            } else {

                return $this->redirectToRoute('core_home');

            }
        };

    }

    public function getPhotosEquipes($edition)
    {
        $repositoryPhotos = $this->doctrine
            ->getManager()
            ->getRepository(Photos::class);
        $listeEquipes = $this->doctrine->getRepository(OdpfEquipesPassees::class)
            ->createQueryBuilder('e')
            ->andWhere('e.editionspassees =:edition')
            ->setParameter('edition', $edition)
            ->addOrderBy('e.numero', 'ASC')
            ->getQuery()->getResult();
        foreach ($listeEquipes as $equipe) {
            $listPhotos = null;
            if ($equipe->isAutorisationsPhotos() == true) {
                $listPhotos = $repositoryPhotos->createQueryBuilder('p')
                    ->andWhere('p.equipepassee =:equipe')
                    ->setParameter('equipe', $equipe)
                    ->getQuery()->getResult();
            }

            if (null != $listPhotos) {
                $rand_keys = array_rand($listPhotos, 1);
                $equipe->getNumero() !== null ? $photos[$equipe->getNumero()] = $listPhotos[$rand_keys] : $photos[$equipe->getLettre()] = $listPhotos[$rand_keys];
            }

        }
        return $photos;

    }

    #[IsGranted("ROLE_PROF")]
    #[Route("/photos/telecharger_photos", name: "telecharger_photos")]
    public function telechargerPhotos(Request $request): Response
    {
        $editionN = $this->requestStack->getSession()->get('edition');
        $repositoryEdition = $this->doctrine->getRepository(Edition::class);
        $slugger = new AsciiSlugger();
        $user = $this->getUser();
        $id_user = $user->getId();
        $repositoryPhotos = $this->doctrine
            ->getManager()
            ->getRepository(Photos::class);
        $listePhotos = null;
        $editionN1 = $repositoryEdition->findOneBy(['ed' => $editionN->getEd() - 1]);
        new \DateTime('now') >= $editionN->getDateOuvertureSite() ? $edition = $editionN : $edition = $editionN1;

        if (in_array('ROLE_ORGACIA', $user->getRoles())) {
            $listePhotos = $repositoryPhotos->createQueryBuilder('p')
                ->leftJoin('p.equipe', 'e')
                ->where('e.centre=:centre')
                ->andWhere('p.edition =:edition')
                ->setParameter('centre', $user->getCentrecia())
                ->setParameter('edition', $edition)
                ->orderBy('e.numero', 'ASC')
                ->getQuery()->getResult();


            $form = $this->createForm(TelechargementPhotosType::class, null, ['listePhotos' => $listePhotos]);
            $form->handleRequest($request);
            if (($form->isSubmitted() && $form->isValid()) or ($request->get('telecharger'))) {
                $zipFile = new ZipArchive();
                $now = new \DateTime('now');
                $fileNameZip = $edition->getEd() . '-photos-' . $slugger->slug($user->getCentrecia())->toString() . '-' . $now->format('d-m-Y\-Hi-s') . '.zip';
                if ($zipFile->open($fileNameZip, ZipArchive::CREATE) === TRUE) {
                    foreach ($listePhotos as $photo) {
                        if ($request->get('telecharger')) {
                            $fileName = $this->getParameter('app.path.odpf_archives') . '/' . $photo->getEdition()->getEd() . '/photoseq/' . $photo->getPhoto();
                            $zipFile->addFromString(basename($fileName), file_get_contents($fileName));
                        } elseif ($form->get('check' . $photo->getId())->getData()) {
                            $fileName = $this->getParameter('app.path.odpf_archives') . '/' . $photo->getEdition()->getEd() . '/photoseq/' . $photo->getPhoto();
                            $zipFile->addFromString(basename($fileName), file_get_contents($fileName));

                        }


                    }
                    $zipFile->close();
                }
                $response = new Response(file_get_contents($fileNameZip));//voir https://stackoverflow.com/questions/20268025/symfony2-create-and-download-zip-file

                $disposition = HeaderUtils::makeDisposition(
                    HeaderUtils::DISPOSITION_ATTACHMENT,
                    $fileNameZip
                );
                $response->headers->set('Content-Type', 'application/zip');
                $response->headers->set('Content-Disposition', $disposition);

                @unlink($fileNameZip);
                return $response;


            }
        }
        if (in_array('ROLE_PROF', $user->getRoles())) {
            $listePhotos = $repositoryPhotos->createQueryBuilder('p')
                ->leftJoin('p.equipe', 'e')
                ->andWhere('p.edition =:edition')
                ->andWhere('e.idProf1=:prof1 or e.idProf2=:prof2')
                ->setParameter('prof1', $id_user)
                ->setParameter('prof2', $id_user)
                ->setParameter('edition', $this->requestStack->getSession()->get('edition'))
                ->getQuery()->getResult();

            $zipFile = new ZipArchive();
            $now = new \DateTime('now');
            $fileNameZip = $edition->getEd() . '-photos-' . '-' . $now->format('d-m-Y\-Hi-s') . '.zip';
            if ($zipFile->open($fileNameZip, ZipArchive::CREATE) === TRUE) {
                foreach ($listePhotos as $photo) {

                    $fileName = $this->getParameter('app.path.odpf_archives') . '/' . $photo->getEdition()->getEd() . '/photoseq/' . $photo->getPhoto();
                    if (file_exists($fileName)) {

                        $zipFile->addFromString(basename($fileName), file_get_contents($fileName));
                    }
                }
                $zipFile->close();
            }
            $response = new Response(file_get_contents($fileNameZip));//voir https://stackoverflow.com/questions/20268025/symfony2-create-and-download-zip-file

            $disposition = HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                $fileNameZip
            );
            $response->headers->set('Content-Type', 'application/zip');
            $response->headers->set('Content-Disposition', $disposition);

            @unlink($fileNameZip);
            return $response;


        }

        return $this->render('photos/telechargements_photos.html.twig', ['listePhotos' => $listePhotos, 'form' => $form->createView(), 'centre' => $user->getCentrecia()]);
    }

    #[Route("/photos/carousel_equipe,{equipeId},{photoId}", name: "carousel_equipe")]
    public function carousel_equipe(Request $request, $equipeId, $photoId)// Affiche le carousel des grandes photos
    {
        $equipe = $this->doctrine->getRepository(Equipesadmin::class)->find($equipeId);
        $photo = $this->doctrine->getRepository(Photos::class)->find($photoId);
        $photos = $this->doctrine->getRepository(Photos::class)->findBy(['equipe' => $equipe]);
        $photoreord = [];
        $i = 0;
        foreach ($photos as $photo) {//reclasse les photos pour que la photo sur laquelle on a cliqué soit active dans le carousel des grandes photos
            if ($photo->getId() == $photoId) {
                $photoreord[$i] = $photo;
                $i++;
            } elseif ($i > 0) {
                $photoreord[$i] = $photo;
                $i++;
            }
        }
        if (count($photoreord) < count($photos)) {
            foreach ($photos as $photo) {
                if ($i < count($photos)) {
                    $photoreord[$i] = $photo;
                    $i++;
                }
            }
        }


        return $this->render('photos/carousel_equipe.html.twig', ['photos' => $photoreord, 'equipe' => $equipe]);


    }
    public function getEquipes($edition) : array
    {
        $repositoryEquipe=$this->doctrine->getRepository(OdpfEquipesPassees::class);
        $listequipes=$repositoryEquipe->findBy(['editionspassees'=>$edition],['numero'=>'ASC']);
        $repositoryPhotos = $this->doctrine
            ->getManager()
            ->getRepository(Photos::class);
        $equipes=[];
        $i=0;
        foreach ($listequipes as $equipe) {
            $listPhotos = null;
            if ($equipe->isAutorisationsPhotos() == true) {//Elimine les équipes qui n'ont pas de photos(souvent celles qui ont abandonné
                $listPhotos = $repositoryPhotos->createQueryBuilder('p')
                    ->andWhere('p.equipepassee =:equipe')
                    ->setParameter('equipe', $equipe)
                    ->getQuery()->getResult();
            }

            if (null != $listPhotos) {
                $equipes[$i] = $equipe;
                $i++;
            }

        }
        return $equipes;
    }
}

