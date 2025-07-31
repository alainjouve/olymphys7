<?php

namespace App\Controller\OdpfAdmin;

use App\Entity\Odpf\OdpfArticle;
use App\Entity\Odpf\OdpfEditionsPassees;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use Imagick;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;


class OdpfEditionsPasseesCrudController extends AbstractCrudController
{
    private ManagerRegistry $doctrine;
    private RequestStack $requestStack;
    private AdminContextProvider $adminContextProvider;

    function __construct(ManagerRegistry $doctrine, RequestStack $requestStack, AdminContextProvider $adminContext)
    {
        $this->doctrine = $doctrine;
        $this->requestStack = $requestStack;
        $this->adminContextProvider = $adminContext;
    }

    public static function getEntityFqcn(): string
    {
        return OdpfEditionsPassees::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->showEntityActionsInlined()
            ->overrideTemplates(['crud/index'=> 'bundles/EasyAdminBundle/indexEntities.html.twig', ])
            ->setDefaultSort(['edition' => 'DESC'])
            ->overrideTemplate('crud/edit','bundles/EasyAdminBundle/crud/edit_edition.html.twig');


        // ->overrideTemplate('crud/field/photoParrain', 'bundles/EasyAdminBundle/odpf/odpf-photoParrain.html.twig');

    }
    public function configureAssets(Assets $assets): Assets //Pour que les noms de fichiers s'affichent dans le champ de choix de fichier lors d'un upload
    {
        $assets->addHtmlContentToHead('<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
                                                    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.js" integrity="sha512-+k1pnlgt4F1H8L7t3z95o3/KO+o78INEcXTbnoJQ/F2VqDVhWoaiVml/OEHv9HsVgxUaVW+IbiZPUJQfF/YxZw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
                                                    <script src="https://cdn.jsdelivr.net/npm/bs-custom-file-input/dist/bs-custom-file-input.min.js"></script>
                                                    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>'
                                        )
            ->addJsFile('/js/admin.js');
        return $assets;
    }
    public function configureFields(string $pageName): iterable
    {
        $articles=$this->doctrine->getRepository(OdpfArticle::class)->findBy(['choix'=>'edition31']);
        $photoParrain = Field::new('photoParrain');
        $affiche = Field::new('affiche');//->setTemplatePath( 'bundles/EasyAdminBundle/odpf/odpf-affiche.html.twig');;

        if (Crud::PAGE_EDIT === $pageName) {
            $idEdition = $_REQUEST['entityId'];
            $editionpassee = $this->doctrine->getRepository(OdpfEditionsPassees::class)->findOneBy(['id' => $idEdition]);
            //$photoParrain = ImageField::new('photoParrain')->setUploadDir('public/odpf-archives/' . $editionpassee->getEdition() . '/parrain');

            //$photoAffiche = ImageField::new('photoParrain')->setUploadDir('public/odpf-archives/' . $editionpassee->getEdition() . '/affiche');
            $photoParrain->setFormType(HiddenType::class);
            $affiche->setFormType(HiddenType::class);
            $photoFile = Field::new('photoParrainFile', 'Photo du parrain')
                ->setFormType(FileType::class)
                ->setLabel('Photo du parrain')
                ->onlyOnForms()
                ->setFormTypeOptions(['data_class' => null, 'mapped'=>false]);// 'upload_dir' => $this->getParameter('app.path.odpf_archives') . '/' . $editionpassee->getEdition() . '/parrain']);

            $afficheFile = Field::new('afficheFile', 'Affiche')
                ->setFormType(FileType::class)
                ->setTemplatePath('bundles/EasyAdminBundle/crud/field/image.html.twig')
                ->setLabel('Affiche')
                ->onlyOnForms()
                ->setFormTypeOptions(['data_class' => null,'mapped'=>false]);// 'upload_dir' => $this->getParameter('app.path.odpf_archives') . '/' . $editionpassee->getEdition() . '/affiche']);

        }

        $id = IntegerField::new('id');
        $edition = IntegerField::new('edition');

        $pseudo = TextField::new('pseudo');
        $lieu = TextField::new('lieu');
        $annee = TextField::new('annee');
        $ville = TextField::new('ville');
        $datecia = TextField::new('dateCia');
        $datecn = TextField::new('dateCn');
        $dateinscription = TextField::new('dateinscription');
        $articleOlymphys= AssociationField::new('article');
       // $articleOlymphysEdit=CollectionField::new('article')
           ;


        $nomParrain = TextField::new('nomParrain');
        $titreParrain = TextField::new('titreParrain');
        $lienParrain = TextField::new('lienparrain');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$edition, $pseudo, $annee, $lieu, $ville, $articleOlymphys, $datecn];
        }
        if (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $edition, $pseudo, $annee, $lieu, $ville, $datecia, $datecn, $dateinscription, $nomParrain, $titreParrain, $photoParrain, $lienParrain, $affiche];
        }
        /*if (Crud::PAGE_NEW === $pageName) {
            return [$edition, $pseudo, $annee, $lieu, $ville, $datecia, $datecn, $dateinscription, $nomParrain, $titreParrain, $photoFiLe, $afficheFile];

        }*/
        if (Crud::PAGE_EDIT === $pageName) {

            return [$edition, $pseudo, $annee, $lieu, $ville, $datecia, $datecn, $dateinscription, $articleOlymphys, $nomParrain, $photoParrain,$affiche, $titreParrain, $lienParrain, $photoFile, $afficheFile];

        }

        return parent::configureFields($pageName); // TODO: Change the autogenerated stub
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->add(Crud::PAGE_EDIT, Action::INDEX, 'Retour à la liste')
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')->update('index', Action::DELETE,function  (Action $action) {
                return $action->setIcon('fa fa-trash-alt')->setLabel(false);}
            )
            ->update('index', Action::EDIT,function  (Action $action) {
                return $action->setIcon('fa fa-pencil-alt')->setLabel(false);}
            )
            ->update('index', Action::DELETE,function  (Action $action) {
                return $action->setIcon('fa fa-trash-alt')->setLabel(false);}
            )
            ->update('index', Action::DETAIL,function  (Action $action) {
                return $action->setIcon('fa fa-eye')->setLabel(false);}
            );
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $editioninitiale=$this->doctrine->getRepository(OdpfEditionsPassees::class)->findOneBy(['id' => $entityInstance->getId()]);
        $parraintag = $this->adminContextProvider->getContext()->getRequest()->files->get('OdpfEditionsPassees')['photoParrainFile'];
        $affichetag = $this->adminContextProvider->getContext()->getRequest()->files->get('OdpfEditionsPassees')['afficheFile'];

        if ($affichetag !== null) {//dépôt de l'affiche et création du  fichiers  afficheEd_HR
            $ext=$affichetag->guessExtension();
            $pathaffiche = $this->getParameter('app.path.odpf_archives') . '/' . $entityInstance->getEdition() . '/affiche/';
            $affichetag->move($pathaffiche,'affiche'.$entityInstance->getEdition().'-HR.' .$ext );


            $afficheBR = new Imagick();
            $afficheBR->readImage($pathaffiche . 'affiche' . $entityInstance->getEdition() . '-HR.' . $ext);
            $width = $afficheBR->getImageWidth();
            $height = $afficheBR->getImageHeight();
            $afficheBR->thumbnailImage(230, 230 * $height / $width);
            $afficheBR->writeImage($pathaffiche . 'affiche' . $entityInstance->getEdition() . '.' . $ext);
            $entityInstance->setAffiche('affiche' . $entityInstance->getEdition() . '.' . $ext);
        }
        else{
            $entityInstance->setAffiche($editioninitiale->getAffiche());
        }
        //dépôt de la photo du parrain
        if ($parraintag !== null) {
            $ext=$parraintag->guessExtension();

            $pathParrain = $this->getParameter('app.path.odpf_archives') . '/' . $entityInstance->getEdition() . '/parrain/';
            $entityInstance->getNomparrain() !== null ? $nomParrain = $entityInstance->getNomparrain() : $nomParrain = '';
            $parraintag->move($pathParrain,$nomParrain.'parrain'.$entityInstance->getEdition() .'.'.$ext);
            $photoParraintmp = new Imagick();
            $photoParraintmp->readImage($pathParrain .$nomParrain. 'parrain' . $entityInstance->getEdition() . '.' . $ext);
            $width = $photoParraintmp->getImageWidth();
            $height = $photoParraintmp->getImageHeight();
            $photoParraintmp->thumbnailImage(230, 230 * $height / $width);
            $photoParraintmp->writeImage($pathParrain . $nomParrain . '-parrain' . $entityInstance->getEdition() . '.' . $ext);
            $entityInstance->setPhotoparrain($nomParrain . '-parrain' . $entityInstance->getEdition() . '.' . $ext);
        }
        else{

            $entityInstance->setPhotoParrain($editioninitiale->getPhotoParrain());
        }


        parent::updateEntity($entityManager, $entityInstance); // TODO: Change the autogenerated stub
    }


}
