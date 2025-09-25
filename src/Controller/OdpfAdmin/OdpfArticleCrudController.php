<?php

namespace App\Controller\OdpfAdmin;

use App\Entity\Odpf\OdpfArticle;

use App\Entity\Odpf\OdpfCarousels;
use App\Entity\Odpf\OdpfCategorie;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OdpfArticleCrudController extends AbstractCrudController
{

    private ManagerRegistry $doctrine;
    private AdminContextProvider $adminContextProvider;
    private RequestStack $requestStack;
    private AdminUrlGenerator $adminUrlGenerator;

    public function __construct(ManagerRegistry $doctrine, AdminContextProvider $adminContextProvider, RequestStack $requestStack, AdminUrlGenerator $adminUrlGenerator)
    {

        $this->doctrine = $doctrine;
        $this->adminContextProvider = $adminContextProvider;
        $this->requestStack = $requestStack;
        $this->adminUrlGenerator = $adminUrlGenerator;
    }


    public static function getEntityFqcn(): string
    {
        return OdpfArticle::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setPageTitle(Crud::PAGE_INDEX, 'Articles et pages du site')
            ->overrideTemplates(['crud/index'=> 'bundles/EasyAdminBundle/indexArticles.html.twig', ])
            ->addFormTheme('@FOSCKEditor/Form/ckeditor_widget.html.twig')
            ->setPaginatorPageSize(100);
    }

    public function configureFields(string $pageName): iterable
    {
        $listCarousels = $this->doctrine->getRepository(OdpfCarousels::class)->findAll();
        $pathpluginsAutogrow = '../public/bundles/fosckeditor/plugins/autogrow/'; // with trailing slash sur le site
        if ($_SERVER['SERVER_NAME'] == '127.0.0.1' or $_SERVER['SERVER_NAME'] == 'localhost') {
            $pathpluginsAutogrow = 'bundles/fosckeditor/plugins/autogrow/';// with trailing slash en local
        }
        yield IdField::new('id')->hideOnForm()->hideOnDetail();

        // Add a tab
        yield FormField::addTab('Article ');

        // You can use a Form Panel inside a Form Tab
        yield FormField::addPanel('Données');

        yield TextField::new('titre')->setSortable(true);
        yield AssociationField::new('categorie')->setSortable(true);
        yield TextField::new('choix','Choix : Ecrire <b>"actus"</b> pour une actualité')->setSortable(true)->hideOnIndex();
        yield AdminCKEditorField::new('texte')->setFormTypeOptions([
            'config' => array(
                'extraPlugins' => 'autogrow',

            ),
            'plugins' => array(
                'autogrow' => array(
                    'path' => $pathpluginsAutogrow,
                    'filename' => 'plugin.js',
                    'autoGrowEnabled' => true,
                    'autoGrow_minHeight' => 200,
                    'autoGrow_maxHeight' => 600,
                    'autoGrow_bottomSpace' => 50
                ))])->onlyOnForms();
        yield TextField::new('texte')->setTemplatePath('bundles/EasyAdminBundle/ckeditorField.html.twig')->hideOnForm();
        yield TextField::new('alt_image')->hideOnIndex();
        yield AdminCKEditorField::new('descr_image')->hideOnIndex();
        yield FormField::addPanel('Autre');
        yield TextField::new('titre_objectifs');
        yield AdminCKEditorField::new('texte_objectifs');
        yield AssociationField::new('carousel')->setFormTypeOptions(['choices' => $listCarousels]);
        yield DateTimeField::new('createdAt', 'Créé  le ')->setSortable(true);
        yield DateTimeField::new('updatedAt')->setSortable(true);
        //$updatedat = DateTimeField::new('updatedat', 'Mis à jour  le ')->setSortable(true);
        yield BooleanField::new('publie', 'publié');

        /*  if (Crud::PAGE_INDEX === $pageName) {
              return [$titre, $choix, $categorie, $texte, $titre_objectifs, $texte_objectifs, $carousel, $publie, $createdAt, $updatedAt];
          } elseif (Crud::PAGE_DETAIL === $pageName) {
              return [$titre, $choix, $categorie, $texte, $titre_objectifs, $texte_objectifs, $carousel, $createdAt, $updatedAt];
          } elseif (Crud::PAGE_NEW === $pageName) {
              return [$titre, $choix, $categorie, $texte, $publie, $titre_objectifs, $texte_objectifs, $carousel];
          } elseif (Crud::PAGE_EDIT === $pageName) {
              return [$tab1, $titre, $publie, $panel1, $choix, $categorie, $texte, $panel2, $titre_objectifs, $texte_objectifs, $carousel];
          }*/


    }

   /* public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('categorie'));

    }*/

    public function configureActions(Actions $actions): Actions
    {
        $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->update('index',Action::NEW, function  (Action $action) {
                return $action->setLabel('Créer un nouvel article');}
            )
            ->update('index', Action::DELETE,function  (Action $action) {
                return $action->setIcon('fa fa-trash-alt')->setLabel(false);}
            )
            ->update('index', Action::EDIT,function  (Action $action) {
                return $action->setIcon('fa fa-pencil-alt')->setLabel(false);}
            ) ->update('index', Action::DETAIL,function  (Action $action) {
                return $action->setIcon('fa fa-eye')->setLabel(false);}
            );
        //->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN');
        return $actions;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $response = $this->container->get(EntityRepository::class)->createQueryBuilder($searchDto, $entityDto, $fields, $filters) //le tri selon les éditions ne fonctionne pas bien
        ->leftJoin('entity.categorie', 'eq');
        //->resetDQLPart('orderBy');
        if (isset($_REQUEST['sort'])) {
            $sort = $_REQUEST['sort'];
            if (key($sort) == 'titre') {
                $response->addOrderBy('entity.titre', $sort['titre']);
            }
            if (key($sort) == 'choix') {
                $response->addOrderBy('entity.choix', $sort['choix']);
            }
            if (key($sort) == 'texte') {
                $response->addOrderBy('entity.texte', $sort['texte']);
            }
            if (key($sort) == 'categorie') {
                $response->addOrderBy('entity.categorie', $sort['categorie']);

                if (key($sort) == 'createdAt') {
                    $response->addOrderBy('entity.createdAt', $sort['createdAt']);
                }
                if (key($sort) == 'updatedAt') {
                    $response->addOrderBy('entity.updatedAt', $sort['updatedAt']);
                }
            }

        } else {

            $response->OrderBy('entity.updatedAt', 'DESC');

        }
        if($this->requestStack->getSession()->get('categorieChoisie')!=null){

            $response->andWhere('entity.categorie =:categorie')
            ->setParameter('categorie',$this->requestStack->getSession()->get('categorieChoisie'));
        }

        return $response;
    }
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $entityInstance->setUpdatedAt(new \DateTime());
        parent::updateEntity($entityManager, $entityInstance); // TODO: Change the autogenerated stub
    }
    public function index(AdminContext $context)
    {
        $categories=$this->doctrine->getRepository(OdpfCategorie::class)->findBy([],['categorie'=>'ASC']);
        if($this->requestStack->getSession()->get('categorieChoisie')==null)
        {
            $this->requestStack->getSession()->set('categorieChoisie', null);//lors de la première connexion à cette page, la variable de session categorieChoisie n'est pas définie
        }
        $this->requestStack->getSession()->set('liste_categories',$categories);
        return parent::index($context); // TODO: Change the autogenerated stub
    }
    #[Route("/articles/choix_categorie_article", name: "choix_categorie_article")]//Permet de contourner la création d'une url admin dans la fonction js
    public function choixCategorieArticle(Request $request) :Response
    {
        $idCategorie=$request->get('idCategorie');
        $categorie=$this->doctrine->getRepository(OdpfCategorie::class)->find($idCategorie);
        $this->requestStack->getSession()->set('categorieChoisie',$categorie);
        $url=$this->adminUrlGenerator->setAction('index')
            ->setDashboard(OdpfDashboardController::class)
            ->generateUrl();
        return $this->redirect($url);
    }
}
