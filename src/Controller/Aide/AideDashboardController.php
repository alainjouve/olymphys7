<?php

namespace App\Controller\Aide;


use App\Entity\AideEnLigne;
use App\Entity\CategorieAide;
use App\Entity\SousCategorieAide;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AideDashboardController extends AbstractDashboardController
{
    private AdminContextProvider $adminContextProvider;
    private AdminUrlGenerator $adminUrlGenerator;

    public function __construct(AdminContextProvider $adminContextProvider, AdminUrlGenerator $adminUrlGenerator)
    {

        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->adminContextProvider = $adminContextProvider;
    }

    #[Route("/aide", name:"aide")]
    public function index(): Response
    {
        if ($this->adminContextProvider->getContext()->getRequest()->query->get('routeName') != null) {

            return $this->redirectToRoute('admin');
        };

        return $this->render('bundles/EasyAdminBundle/page_accueil_aide.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('<img src="https://upload.wikimedia.org/wikipedia/commons/3/36/Logo_odpf_long.png" alt="logo des OdpF"  width="160"/>')
            ->setDefaultColorScheme('light');
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addJsFile('js/easyadmin-color-scheme-migration.js');
    }

    public function configureMenuItems(): iterable
    {
        return [
            MenuItem::linkToCrud('Categories', 'fa-solid fa-ellipsis-vertical', CategorieAide::class)
                ->setController(CategorieAideCrudController::class)
            ->setPermission('ROLE_SUPER_ADMIN'),
            MenuItem::linkToCrud('Sous-categories', 'fa-solid fa-bars-staggered', SousCategorieAide::class)
            ->setController(SousCategorieAideCrudController::class)
            ->setPermission('ROLE_SUPER_ADMIN'),
            MenuItem::linkToCrud('Articles', 'fa-solid fa-list', AideEnLigne::class)
                ->setController(AideEnLigneCrudController::class)
                ->setPermission('ROLE_SUPER_ADMIN'),
            MenuItem::linkToRoute('Retour vers la page d\'accueil','fa fa-door-open', 'core_home')
        ];
    }


}
