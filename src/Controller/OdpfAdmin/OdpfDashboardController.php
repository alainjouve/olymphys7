<?php

namespace App\Controller\OdpfAdmin;


use AllowDynamicProperties;
use App\Controller\Admin\DashboardController;
use App\Entity\Odpf\OdpfArticle;
use App\Entity\Odpf\OdpfCarousels;
use App\Entity\Odpf\OdpfCategorie;
use App\Entity\Odpf\OdpfDocuments;
use App\Entity\Odpf\OdpfEditionsPassees;
use App\Entity\Odpf\OdpfEquipesPassees;
use App\Entity\Odpf\OdpfFichierspasses;
use App\Entity\Odpf\OdpfLogos;

use App\Entity\Odpf\OdpfPartenaires;
use App\Entity\Odpf\OdpfVideosequipes;
use App\Entity\Photos;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\File;

#[AllowDynamicProperties]
class OdpfDashboardController extends AbstractDashboardController
{
    private AdminContextProvider $adminContextProvider;
    private AdminUrlGenerator $adminUrlGenerator;
    private EntityManagerInterface $doctrine;

    public function __construct(RequestStack $requestStack, EntityManagerInterface $doctrine, AdminContextProvider $adminContextProvider, AdminUrlGenerator $adminUrlGenerator, private CsrfTokenManagerInterface $csrfTokenManager)
    {

        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->adminContextProvider = $adminContextProvider;
        $this->requestStack = $requestStack;
        $this->doctrine = $doctrine;

    }

    #[Route("/odpfadmin", name: "odpfadmin")]
    public function index(): Response
    {
        if ($this->adminContextProvider->getContext()->getRequest()->query->get('routeName') != null) {

            return $this->redirectToRoute('odpfadmin');
        };
        return $this->render('bundles/EasyAdminBundle/odpf/message_accueil.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('<img src="https://upload.wikimedia.org/wikipedia/commons/3/36/Logo_odpf_long.png" alt="logo des OdpF"  width="160"/>');
    }

    public function configureAssets(): Assets
    {

        return Assets::new()->addCssFile('css/admin.css')
            ->addJsFile("https://code.jquery.com/jquery-3.6.0.min.js")
            ->addJsFile('js/admin.js');
    }

    public function configureCrud(): Crud
    {
        return Crud::new()
            ->setDateFormat('dd/MM/yyyy')
            ->setDateTimeFormat('dd/MM/yyyy HH:mm:ss')
            ->setTimeFormat('HH:mm');
    }

    public function configureMenuItems(): iterable
    {
        return [
            MenuItem::linktoDashboard('Tableau de bord', 'fa fa-home'),
            MenuItem::linkToCrud('Articles', 'fas fa-list', OdpfArticle::class),
            MenuItem::linkToCrud('Categories', 'fas fa-list', OdpfCategorie::class)->setPermission('ROLE_SUPER_ADMIN'),
            MenuItem::linkToCrud('Documents du site', 'fas fa-book', OdpfDocuments::class)
                ->setDefaultSort(['updatedAt' => 'DESC']),
            MenuItem::linkToCrud('Logos du site', 'fa-solid fa-icons', OdpfLogos::class),
            MenuItem::linkToCrud('Carrousels', 'fa-solid fa-clapperboard', OdpfCarousels::class),
            MenuItem::linkToCrud('Partenaires', 'fa-solid fa-list', OdpfPartenaires::class),
            MenuItem::subMenu('Les éditions passées', 'fa-solid fa-book-bookmark')->setSubItems([
                MenuItem::linkToCrud('Les éditions passées', 'fas fa-list', OdpfEditionsPassees::class),
                MenuItem::linkToCrud('Les équipes passées', 'fa-solid fa-user-group', OdpfEquipesPassees::class),

                MenuItem::linkToCrud('Les mémoires', 'fas fa-book', OdpfFichierspasses::class)
                    ->setController(OdpfFichiersPassesCrudController::class)
                    ->setQueryParameter('typefichier', 0),

                MenuItem::linkToCrud('Les résumés', 'fas fa-book', OdpfFichierspasses::class)
                    ->setController(OdpfFichiersPassesCrudController::class)
                    ->setQueryParameter('typefichier', 2),
                MenuItem::linkToCrud('Les présentations', 'fas fa-book', OdpfFichierspasses::class)
                    ->setController(OdpfFichiersPassesCrudController::class)
                    ->setQueryParameter('typefichier', 3),
                MenuItem::linkToCrud('Les autorisations photos', 'fas fa-book', OdpfFichierspasses::class)
                    ->setController(OdpfFichiersPassesCrudController::class)
                    ->setQueryParameter('typefichier', 6),

                MenuItem::linkToCrud('Les  photos', 'fas fa-images', Photos::class)
                    ->setController(OdpfPhotosCrudController::class),
                MenuItem::linkToCrud('Types sujets des photos', 'fas fa-list', OdpfSujetsPhotos::class)
                    ->setController(OdpfSujetsPhotosCrudController::class),
                MenuItem::linkToCrud('Les  vidéos', 'fas fa-images', OdpfVideosequipes::class)
                    ->setController(OdpfVideosEquipesCrudController::class)]),


            //MenuItem::subMenu('Les éditions passées', 'fa-solid fa-book-bookmark')->setSubItems($submenu1)->setCssClass('text-bold'),
            MenuItem::linktoRoute('Aller à l\'admin du concours', 'fa-solid fa-marker', 'admin'),
            MenuItem::linktoRoute('Retour à la page d\'accueil', 'fas fa-home', 'core_home'),
            MenuItem::linkToLogout('Déconnexion', 'fas fa-door-open')
        ];
    }

    #[Route('/odpfadmin/documentsbrowser', name: 'app_odpfadmin_odpfdocumentsbrowser')]
    #[isGranted('ROLE_SUPER_ADMIN')]
    public function les_documents(Request $request): Response
    {
        $basePath = $this->getParameter('kernel.project_dir') . '/public/odpf/';
        //if (str_contains($_SERVER['SERVER_NAME'], 'localhost')) $basePath = 'odpf/';
        $sort = $request->query->get('sort', 'nom');
        $order = $request->query->get('order', 'asc');
        $subfolder = $request->query->get('subfolder') ?? '';
        $subfolder = str_replace(['..', './'], '', $subfolder);
        $subpath = '/';//pour les assets du twig
        $subfolder = trim($subfolder, '/');

        $path = $subfolder !== '/' ? $basePath . $subfolder : $basePath;

        $path = rtrim($path, '/');
        $listFilesbrut = null;
        if (str_contains($path, 'photoseq') === true) {

            $edition = $this->doctrine->getRepository(OdpfEditionsPassees::class)->findOneBy(['edition' => explode('/', $path)[count(explode('/', $path)) - 2]]);

            $images = $this->doctrine->getRepository(Photos::class)->findBy(['editionspassees' => $edition]);
            $i = 0;
            foreach ($images as $image) {
                if (file_exists($path . '/thumbs/' . $image->getPhoto())) {
                    $listFilesbrut[$i] = $image->getPhoto();
                    $i++;
                }
            }

        } else {
            $listFilesbrut = scandir($path);
        }

        $listFiles = [];
        foreach ($listFilesbrut as $file) {
            if ($file !== '.tmb' && $file !== '.' && $file !== '..') {
                if (file_exists($path . '/' . $file)) {
                    $type = is_dir($path . '/' . $file) ? 'folder' : (str_contains(mime_content_type($path . '/' . $file), 'image') ? 'image' : 'file');
                    $listFiles[] = [$file, date('d/m/Y à H:i', filemtime($path . '/' . $file)), date('d/m/Y à H:i', filectime($path . '/' . $file)), $type, filesize($path . '/' . $file)];
                }
            }
        }
        usort($listFiles, function ($a, $b) use ($sort) {
            return $sort === 'date' ? ($a[3] <=> $b[3]) : strcasecmp($a[0], $b[0]);
        });
        if ($order === 'desc') {
            $listFiles = array_reverse($listFiles);
        }
        return $this->render('bundles/EasyAdminBundle/odpf/indexDocuments.html.twig', [
            'path' => $path,
            'subpath' => $subpath,
            'subfolder' => $subfolder,
            'listeFiles' => $listFiles,
            'sort' => $sort,
            'order' => $order,
            'csrf_token' => $this->csrfTokenManager->getToken('upload_documents')->getValue(),
        ]);


    }

    #[Route('/deocuments/supprimer', name: 'supprimer_doc')]
    #[isGranted('ROLE_SUPER_ADMIN')]
    public function supprimer_doc(Request $request, AdminUrlGenerator $adminUrlGenerator): Response
    {

        $doc = $request->request->get('filename');
        $subfolder = $request->query->get('subfolder');
        $filePath = 'odpf' . $subfolder . '/' . $doc;
        dd($filePath);
        if (str_contains($_SERVER['SERVER_NAME'], 'olymphys.fr')) $filePath = 'public/odpf' . $subfolder . '/' . $doc;
        if (file_exists($filePath)) {
            unlink($filePath);
            $this->addFlash('success', 'Le document ' . $doc . ' a été supprimée avec succès.');
        } else {
            $this->addFlash('danger', 'Le document ' . $doc . ' n\'existe pas.');
        }

        return $this->redirectToRoute('app_odpfadmin_odpfdocumentsbrowser');
    }

    #[Route('/documents/upload-ajax', name: 'upload_documents_ajax', options: ['methods' => ['POST']])]
    #[isGranted('ROLE_SUPER_ADMIN')]
    public function upload_doc_ajax(Request $request): JsonResponse
    {
        $token = $request->headers->get('X-CSRF-Token') ?? $request->request->get('_token');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('upload_documents', $token))) {
            {
                return new JsonResponse(['error' => 'Token CSRF invalide.'], 403);
            }
        }

        $path = $this->getParameter('kernel.project_dir') . '/public/odpf/' . $request->files->get('subfolder');

        $file = $request->files->get('doc');
        if (!$file) {
            return new JsonResponse(['error' => 'Aucun fichier reçu.'], 400);
        }


        $originalFilename = $file->getClientOriginalName();
        $file->move($path, $originalFilename);

        return new JsonResponse(['success' => true, 'filename' => $originalFilename]);
    }

    #[Route('/documents/odpf_depose_documentsr', name: 'odpf_deposer_documents')]
    #[isGranted('ROLE_SUPER_ADMIN')]
    public function deposer_documents(Request $request, AdminUrlGenerator $adminUrlGenerator): Response
    {
        $path = '/';


        $listeDocumentsbrut = scandir($path);
        $listeDocuments = null;
        foreach ($listeDocumentsbrut as $doc) {
            if ($doc !== '.' and $doc !== '..' and $doc !== '.tmb') {
                $listeDocuments[] = $doc;
            }
        }
        $this->requestStack->getSession()->set('listeDocuments', $listeDocuments);
        $form = $this->createFormBuilder()
            ->add('document', FileType::class, [
                'constraints' => [
                    new File(mimeTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp',], mimeTypesMessage: 'Veuillez télécharger une image au format JPEG, PNG, GIF ou WEBP')
                ],
                'label' => 'Sélectionnez un document à déposer',
                'required' => true,
                'attr' => ['onchange' => 'chargedocument(this)']
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Déposer le document',
                'attr' => ['class' => 'btn btn-primary mt-3']])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $docFile = $form->get('document')->getData();
            if ($docFile) {
                $originalFilename = $docFile->getClientOriginalName();
                $docFile->move($path, $originalFilename);
                $this->addFlash('success', 'Le document' . $originalFilename . ' a été enregistré avec succès.');
            }
            return $this->redirectToRoute('app_odpfadmin_odpfdocumentsbrowser');


        }


        return $this->render('bundles/EasyAdminBundle/odpf/deposerDocument.html.twig', [
            'path' => $path,
            'form' => $form,
        ]);


    }

}
