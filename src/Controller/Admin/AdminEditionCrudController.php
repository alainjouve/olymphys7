<?php

namespace App\Controller\Admin;

use App\Entity\Edition;
use App\Entity\Elevesinter;
use App\Entity\Equipes;
use App\Entity\Equipesadmin;
use App\Entity\Fichiersequipes;
use App\Entity\Odpf\OdpfArticle;
use App\Entity\Odpf\OdpfEditionsPassees;
use App\Entity\Odpf\OdpfEquipesPassees;
use App\Entity\Odpf\OdpfFichierspasses;
use App\Entity\Photos;
use App\Entity\Videosequipes;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use setasign\Fpdi\PdfParser\PdfParser;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

use App\Entity\Odpf\OdpfVideosequipes;
use App\Service\CreatePageEdPassee;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use function PHPUnit\Framework\directoryExists;

class AdminEditionCrudController extends AbstractCrudController
{
    private RequestStack $requestStack;
    private EntityManagerInterface $em;
    private ManagerRegistry $doctrine;

    public function __construct(RequestStack $requestStack, EntityManagerInterface $entitymanager, ManagerRegistry $doctrine, ParameterBagInterface $parameterBag)
    {
        $this->requestStack = $requestStack;
        $this->em = $entitymanager;
        $this->doctrine = $doctrine;

    }


    public static function getEntityFqcn(): string
    {
        return Edition::class;
    }
    public function configureCrud(Crud $crud): Crud
    {
        return $crud->showEntityActionsInlined()
            ->overrideTemplates(['crud/index'=> 'bundles/EasyAdminBundle/indexEntities.html.twig', ])
            ->setPageTitle(Crud::PAGE_INDEX, 'Réglage des éditions')
            ->setSearchFields(['id', 'ed', 'ville', 'lieu'])
            ->setPaginatorPageSize(30)
            ->setDefaultSort(['ed' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        $ed = TextField::new('ed');
        $ville = TextField::new('ville');
        $date = DateTimeField::new('date');
        $lieu = TextField::new('lieu');
        $dateouverturesite = DateTimeField::new('dateouverturesite');
        $dateclotureinscription = DateTimeField::new('dateclotureinscription');
        $datelimcia = DateTimeField::new('datelimcia');
        $datelimnat = DateTimeField::new('datelimnat');
        $concourscia = DateField::new('concourscia');
        $concourscn = DateField::new('concourscn');
        $nomParrain = TextField::new('nomParrain', 'Parrain');
        $titreParrain = TextField::new('titreParrain', 'titre parrain');
        $id = IntegerField::new('id', 'ID');
        $annee = TextField::new('annee', 'année');
        if (Crud::PAGE_INDEX === $pageName) {
            return [$ed, $ville, $date, $lieu, $dateouverturesite, $dateclotureinscription, $datelimcia, $datelimnat, $concourscia, $concourscn, $nomParrain, $titreParrain];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $ed, $annee, $date, $ville, $lieu, $datelimcia, $datelimnat, $dateouverturesite, $concourscia, $concourscn, $dateclotureinscription, $nomParrain, $titreParrain];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$ed, $annee, $ville, $date, $lieu, $dateouverturesite, $dateclotureinscription, $datelimcia, $datelimnat, $concourscia, $concourscn, $nomParrain, $titreParrain];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$ed, $annee, $ville, $date, $lieu, $dateouverturesite, $dateclotureinscription, $datelimcia, $datelimnat, $concourscia, $concourscn, $nomParrain, $titreParrain];
        }
    }

    public function configureActions(Actions $actions): Actions
    {

        $creerEditionPassee = Action::new('creer_edition_passee', '', 'fa fa-cubes')
            ->linkToCrudAction('creer_edition_passee')->setHtmlAttributes(['title'=>'Créer l\'édition passée']);//->createAsBatchAction();
        return $actions->update('index', Action::EDIT, function  (Action $action) {
                            return $action->setIcon('fa fa-pencil-alt')->setLabel(false);})
                         ->update('index', Action::DELETE, function  (Action $action) {
                         return $action->setIcon('fa fa-trash-alt')->setLabel(false);})
                            ->add(Crud::PAGE_INDEX, $creerEditionPassee);


    }

    public function getRedirectResponseAfterSave(AdminContext $context, string $action): RedirectResponse
    {

        if ($action == 'new') {
            $this->creer_edition_passee($context);
        }
        return parent::getRedirectResponseAfterSave($context, $action); // TODO: Change the autogenerated stub
    }

    /**
     * @throws NonUniqueResultException
     */
    public function creer_edition_passee(AdminContext $context): RedirectResponse//sera complètement modifiée et simplifiée(pas de gestion des fichiers, photos, equipespassees )
    {

        $filesystem = new Filesystem();
        $idedition = null;
        $numEd = null;
        $edition = null;

        if ($context->getRequest()->query->get('crudAction') == 'creer_edition_passee')//lorsqu'on a cliquer sur le bouton créer une édition passée de l'édition
        {
            $idedition = $context->getRequest()->query->get('entityId');//la requête contient l'id de l'édition
            $edition = $this->doctrine->getRepository(Edition::class)->find($idedition);
            $numEd = $edition->getEd();
        }
        if ($context->getRequest()->query->get('crudAction') == 'new') {//au moment de créer la nouvelle édition
            $numEd = $_REQUEST['Edition']['ed'];//la requête contient un tableau de string Edition qui n'est pas un objet de lasse édition
            $edition = $this->doctrine->getRepository(Edition::class)->findOneBy(['ed' => $numEd]);//On retrouve la classe correspondante
        }

        $repositoryEquipes = $this->doctrine->getRepository(Equipesadmin::class);
        $repositoryEquipesNat = $this->doctrine->getRepository(Equipes::class);
        $repositoryFichiersequipes = $this->doctrine->getRepository(Fichiersequipes::class);
        $repositoryOdpfEditionsPassees = $this->doctrine->getRepository(OdpfEditionsPassees::class);
        $repositoryEquipesPassees = $this->doctrine->getRepository(OdpfEquipesPassees::class);
        $repositoryEleves = $this->doctrine->getRepository(Elevesinter::class);
        $repositoryOdpfFichierspasses = $this->doctrine->getRepository(OdpfFichierspasses::class);
        $repositoryOdpfArticles = $this->doctrine->getRepository(OdpfArticle::class);

        $repositoryVideos = $this->doctrine->getRepository(Videosequipes::class);
        $repositoryVideospassees = $this->doctrine->getRepository(OdpfVideosequipes::class);
        $editionPassee = $repositoryOdpfEditionsPassees->findOneBy(['edition' => $numEd]);//On teste si cette édition est délà créee
        if ($editionPassee === null) {

            $editionPassee = new OdpfEditionsPassees();
            $editionPassee->setEdition($numEd);//Le champ edition de l'édition passée contient le n° de l'édition
        }
        //On teste l'existance des répertoires du dossier public de l'édtion et on les créee s'ils n'existent pas
        if (!file_exists($this->getParameter('app.path.odpf_archives') . '/' . $editionPassee->getEdition() . '/fichiers')) {

            $dir = $this->getParameter('app.path.odpf_archives') . '/' . $editionPassee->getEdition() . '/fichiers';
            $filesystem->mkdir($dir);
        }
        if (!file_exists($this->getParameter('app.path.odpf_archives') . '/' . $editionPassee->getEdition() . '/parrain')) {
            //mkdir($this->getParameter('app.path.odpf_archives') . '/' . $OdpfEquipepassee->getEdition()->getEdition());
            $filesystem->mkdir($this->getParameter('app.path.odpf_archives') . '/' . $editionPassee->getEdition() . '/parrain');
        }
        if (!file_exists($this->getParameter('app.path.odpf_archives') . '/' . $editionPassee->getEdition() . '/affiche')) {
            //mkdir($this->getParameter('app.path.odpf_archives') . '/' . $OdpfEquipepassee->getEdition()->getEdition());
            //$dir=
            $filesystem->mkdir($this->getParameter('app.path.odpf_archives') . '/' . $editionPassee->getEdition() . '/affiche');
        }
        if (!file_exists($this->getParameter('app.path.odpf_archives') . '/' . $editionPassee->getEdition() . '/photoseq')) {
            //mkdir($this->getParameter('app.path.odpf_archives') . '/' . $OdpfEquipepassee->getEditionspassees()->getEdition());
            $filesystem->mkdir($this->getParameter('app.path.odpf_archives') . '/' . $editionPassee->getEdition() . '/photoseq');
        }
        if (!file_exists($this->getParameter('app.path.odpf_archives') . '/' . $editionPassee->getEdition() . '/photoseq/thumbs')) {
            //mkdir($this->getParameter('app.path.odpf_archives') . '/' . $OdpfEquipepassee->getEditionspassees()->getEdition());
            $filesystem->mkdir($this->getParameter('app.path.odpf_archives') . '/' . $editionPassee->getEdition() . '/photoseq/thumbs');
        }
        if (!file_exists($this->getParameter('app.path.odpf_archives') . '/' . $editionPassee->getEdition() . '/documents')) {
            //mkdir($this->getParameter('app.path.odpf_archives') . '/' . $OdpfEquipepassee->getEditionspassees()->getEdition());
            $filesystem->mkdir($this->getParameter('app.path.odpf_archives') . '/' . $editionPassee->getEdition() . '/documents');
        }
        //ON remplit les champs de l'édition passée avec ceux de l'édition créee
        $editionPassee->setAnnee($edition->getAnnee());
        $editionPassee->setLieu($edition->getLieu());
        $editionPassee->setVille($edition->getVille());
        $editionPassee->setPseudo($edition->getEd());
        setlocale(LC_TIME, 'fr_FR.UTF8', 'fr.UTF8', 'fr_FR.UTF-8', 'fr.UTF-8');
        $date = 'du ' . date_format($edition->getDateouverturesite(), 'd F Y ') . ' au ' . date_format($edition->getDateclotureinscription(), ' d %F Y ');
        $editionPassee->setDateinscription($date);
        $date = date_format($edition->getConcourscia(), 'd F Y');

        $editionPassee->setDateCia($date);
        $date = date_format($edition->getConcourscn(), 'd F Y');
        $editionPassee->setDateCn($date);
        $editionPassee->setNomParrain($edition->getNomParrain());
        $editionPassee->setTitreParrain($edition->getTitreParrain());
        $this->em->persist($editionPassee);
        $this->em->flush();
        $listeEquipes = $repositoryEquipes->findBy(['edition' => $edition]);

        /* transfert des équipes , provisoire, pour la transition d'olymphys vers opdf*/

        $i = 0;
        foreach ($listeEquipes as $equipe) {//On créee les équipes passées

            $OdpfEquipepassee = $repositoryEquipesPassees->createQueryBuilder('e')
                ->where('e.numero =:numero')
                ->andWhere('e.editionspassees= :edition')
                ->setParameters(['numero' => $equipe->getNumero(), 'edition' => $editionPassee])
                ->getQuery()->getOneOrNullResult();

            if ($OdpfEquipepassee === null) {
                $OdpfEquipepassee = new OdpfEquipesPassees();
            }
            $OdpfEquipepassee->setEditionspassees($editionPassee);
            $OdpfEquipepassee->setNumero($equipe->getNumero());
            if ($equipe->getUaiId() != null) {

                $OdpfEquipepassee->setLettre($equipe->getLettre());
                $OdpfEquipepassee->setLycee($equipe->getUaiId()->getNom());
                $OdpfEquipepassee->setVille($equipe->getUaiId()->getCommune());
                $OdpfEquipepassee->setAcademie($equipe->getLyceeAcademie());
                $nomsProfs1 = ucfirst($equipe->getPrenomProf1()) . ' ' . mb_strtoupper($equipe->getNomProf1());
                $equipe->getIdProf2() != null ? $nomsProfs = $nomsProfs1 . ', ' . $equipe->getPrenomProf2() . ' ' . $equipe->getNomProf2() : $nomsProfs = $nomsProfs1;
                $OdpfEquipepassee->setProfs($nomsProfs);
                $listeEleves = $repositoryEleves->findBy(['equipe' => $equipe]);
                $nomsEleves = '';
                foreach ($listeEleves as $eleve) {
                    $nomsEleves = $nomsEleves . ucfirst($eleve->getPrenom()) . ' ' . $eleve->getNom() . ', ';
                }
                $OdpfEquipepassee->setEleves($nomsEleves);
            }
            if ($OdpfEquipepassee->getNumero()) {
                $OdpfEquipepassee->setTitreProjet($equipe->getTitreProjet());
                $OdpfEquipepassee->setSelectionnee($equipe->getSelectionnee());
                $equipenat = $repositoryEquipesNat->findOneBy(['equipeinter' => $equipe]);
                if ($equipenat) {
                    $OdpfEquipepassee->setPrix($equipenat->getPrix()->getPrix());
                    $OdpfEquipepassee->setPalmares($equipenat->getClassement());
                }
                //$editionPassee->addOdpfEquipesPassee($OneOdpfEquipepassee);//Cette ligne bloque sur le site :
                //dd($OdpfEquipepassee->getNumero());->findOneBy(['equipeinter'=>$equipe]);
                $this->em->persist($OdpfEquipepassee);
                $this->em->flush();
            }
            /* transfert des memoires, resumés et presentations du CN du répertoire prive vers le répertoire publie*/
            $listeFichiers = $repositoryFichiersequipes->createQueryBuilder('f')
                ->where('f.equipe =:equipe')
                ->andWhere('f.typefichier <:value')
                ->andWhere('f.national =:national')
                ->setParameter('equipe', $equipe)
                ->setParameter('value', 4)
                ->setParameter('national', true)
                ->getQuery()->getResult();


            if ($listeFichiers) {
                foreach ($listeFichiers as $fichier) {
                    if ($fichier->getTypefichier() < 4) {
                        if (!file_exists($this->getParameter('app.path.odpf_archives') . '/' . $OdpfEquipepassee->getEditionspassees()->getEdition() . '/fichiers/' . $this->getParameter('type_fichier')[$fichier->getTypefichier() <= 1 ? 0 : $fichier->getTypefichier()] . '/publie')) {
                            //mkdir($this->getParameter('app.path.odpf_archives') . '/' . $OdpfEquipepassee->getEdition()->getEdition());
                            $filesystem->mkdir($this->getParameter('app.path.odpf_archives') . '/' . $OdpfEquipepassee->getEditionspassees()->getEdition() . '/fichiers/' . $this->getParameter('type_fichier')[$fichier->getTypefichier() <= 1 ? 0 : $fichier->getTypefichier()] . '/publie');

                        }
                        if (!file_exists($this->getParameter('app.path.odpf_archives') . '/' . $OdpfEquipepassee->getEditionspassees()->getEdition() . '/fichiers/' . $this->getParameter('type_fichier')[$fichier->getTypefichier() <= 1 ? 0 : $fichier->getTypefichier()] . '/prive')) {
                            //mkdir($this->getParameter('app.path.odpf_archives') . '/' . $OdpfEquipepassee->getEdition()->getEdition());
                            $filesystem->mkdir($this->getParameter('app.path.odpf_archives') . '/' . $OdpfEquipepassee->getEditionspassees()->getEdition() . '/fichiers/' . $this->getParameter('type_fichier')[$fichier->getTypefichier() <= 1 ? 0 : $fichier->getTypefichier()] . '/prive');
                            //on protège les répertoires privés (autorisations, fiches sécurités, questionnaires, fichiers équipes non sélectionnées
                            if (!file_exists($this->getParameter('app.path.odpf_archives') . '/' . $OdpfEquipepassee->getEditionspassees()->getEdition() . '/fichiers/' . $this->getParameter('type_fichier')[$fichier->getTypefichier() <= 1 ? 0 : $fichier->getTypefichier()] . '/prive/.htaccess')) {
                                $file = fopen($this->getParameter('app.path.odpf_archives') . '/' . $OdpfEquipepassee->getEditionspassees()->getEdition() . '/fichiers/' . $this->getParameter('type_fichier')[$fichier->getTypefichier() <= 1 ? 0 : $fichier->getTypefichier()] . '/prive/.htaccess', 'w');
                                //on crée le fichier .htaccess qui protège le répertoire
                                $data = '<LIMIT GET POST>
                                order deny,allow
                                deny from all
                                </LIMIT>';
                                fwrite($file, $data);
                                fclose($file);
                            }
                        }
                    }
                    $odpfFichier = $repositoryOdpfFichierspasses->findOneBy(['equipepassee' => $OdpfEquipepassee, 'typefichier' => $fichier->getTypefichier()]);

                    if ($odpfFichier === null) {
                        $odpfFichier = new OdpfFichierspasses();

                        $odpfFichier->setEquipePassee($OdpfEquipepassee);
                        $odpfFichier->setTypefichier($fichier->getTypefichier());
                        $odpfFichier->setNational($fichier->getNational());
                        $odpfFichier->setEditionspassees($editionPassee);
                        //dd($this->getParameter('app.path.fichiers') . '/' . $this->getParameter('type_fichier')[$fichier->getTypefichier() == 1 ? 0 : $fichier->getTypefichier()] . '/' . $fichier->getFichier());

                        $odpfFichier->setNomFichier($fichier->getFichier());
                    }
                    $odpfFichier->setNational($fichier->getNational());//met à jour les fichiers déposés pour le  CN
                    $odpfFichier->setUpdatedAt(new DateTime('now'));
                    $this->em->persist($odpfFichier);
                    $this->em->flush();
                    if ($odpfFichier->getTypefichier() ==0 ) {
                        $this->indexation($odpfFichier);//on indexe les fichiers pour le moteur de recherche
                    }
                }

            }

            $listeVideos = $repositoryVideos->findBy(['equipe' => $equipe]);

            if ($listeVideos != null) {
                foreach ($listeVideos as $video) {
                    $repositoryVideospassees->findOneBy(['lien' => $video->getLien()]) == null ? $videopassee = new OdpfVideosequipes() : $videopassee = $repositoryVideospassees->findOneBy(['lien' => $video->getLien()]);
                    $videopassee->setEquipe($OdpfEquipepassee);
                    $videopassee->setLien($video->getLien());
                    $this->em->persist($videopassee);
                    $this->em->flush();
                }
            }
            $i += 1;
        }

        /* Transfert des photos , provisoire, pour la transition d'olymphys vers odpf */
        /* $listePhotos = $this->doctrine->getRepository(Photos::class)->findBy(['edition' => $edition]);

         foreach ($listePhotos as $photo) {
             $equipe = $photo->getEquipe();
             $equipepassee = $repositoryEquipesPassees->findOneBy(['titreProjet' => $equipe->getTitreProjet()]);
             $photo->setEditionspassees($editionPassee);
             $photo->setEquipepassee($equipepassee);
             $this->em->persist($photo);
             //$editionPassee->addPhoto($photo);
             $this->em->persist($photo);
             //dd($this->getParameter('app.path.photos') . '/'. $photo->getPhoto());
             if (file_exists($this->getParameter('app.path.photos') . '/' . $photo->getPhoto())) {

                 $filesystem->copy($this->getParameter('app.path.photos') . '/' . $photo->getPhoto(),
                     $this->getParameter('app.path.odpf_archives') . '/' . $editionPassee->getEdition() . '/photoseq/' . $photo->getPhoto());
             }
             if (file_exists($this->getParameter('app.path.photos') . '/thumbs/' . $photo->getPhoto())) {
                 try {
                     $filesystem->copy($this->getParameter('app.path.photos') . '/thumbs/' . $photo->getPhoto(),
                         $this->getParameter('app.path.odpf_archives') . '/' . $editionPassee->getEdition() . '/photoseq/thumbs/' . $photo->getPhoto());
                 } catch (Exception $e) {


                 }
             }


         }*/
        $article = $this->doctrine->getRepository(OdpfArticle::class)->findOneBy(['choix' => 'edition' . $editionPassee->getEdition()]);


        $createArticle = new CreatePageEdPassee($this->em);
        $article = $createArticle->create($editionPassee);


        $this->em->persist($article);
        $this->em->flush();


        return $this->redirectToRoute('odpfadmin');
    }

    /**
     * @throws Exception
     */
    public function indexation($fichier): void
    {

        $repertoire='search/textes/';
        $pathFichier='odpf/odpf-archives/'.$fichier->getEditionspassees()->getEdition().'/fichiers/memoires/publie/'.$fichier->getNomfichier();
        $nomFichier=explode('.pdf', $fichier->getNomfichier())[0];
        $parser = new \Smalot\PdfParser\Parser();
        $pdf=$parser->parseFile($pathFichier);

        $metaData = $pdf->getDetails();
        $texte=$pdf->getText();
        $cleanedText = mb_convert_encoding($texte, 'UTF-8', 'ISO-8859-1');
        $fichiertexte=fopen($repertoire.$nomFichier.'.txt', 'w');
        fwrite($fichiertexte, $cleanedText);
        fclose($fichiertexte);

    }

}