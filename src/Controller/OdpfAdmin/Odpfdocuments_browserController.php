<?php

declare(strict_types=1);

namespace App\Controller\OdpfAdmin;

use AllowDynamicProperties;
use App\Entity\Odpf\OdpfEditionsPassees;
use App\Entity\Photos;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AllowDynamicProperties]
class Odpfdocuments_browserController extends AbstractController
{
    public function __construct(EntityManagerInterface $doctrine)
    {
        $this->doctrine = $doctrine;


    }

    #[Route('/odpfdocuments_browser,{page}', name: 'odpfdocuments_browser')]
    #[Isgranted('ROLE_ADMIN')]
    public function index(Request $request, $page): Response
    {
        $basePath = 'odpf/';

        if (str_contains($_SERVER['SERVER_NAME'], 'localhost')) $basePath = 'odpf/';
        $sort = $request->query->get('sort', 'nom');
        $order = $request->query->get('order', 'asc');
        $subfolder = $request->query->get('subfolder') ?? '';
        $subfolder = str_replace(['..', './'], '', $subfolder);
        $subfolder = trim($subfolder, '/');

        $path = $subfolder !== '' ? $basePath . $subfolder : $basePath;
        $path = rtrim($path, '/');

        $listFilesbrut = null;
        if (str_contains($path, 'photoseq') === true) {
            $edition = $this->doctrine->getRepository(OdpfEditionsPassees::class)->findOneBy(['edition' => explode('/', $path)[count(explode('/', $path)) - 2]]);

            $listFilesbrut = $this->doctrine->getRepository(Photos::class)->findBy(['editionspassees' => $edition]);
            $i = 0;

        } else {
            $listFilesbrut = scandir($path);
        }
        $nbFiles = count($listFilesbrut);
        $nbPages = (int)($nbFiles / 50) + 1;

        $offset = 0;
        if (str_contains($path, 'photoseq') === true) {
            if ($page == 0) {

                $page = $nbPages;
                $offset = ($page - 1) * 50;
            }
            if ($page > 1) $offset = ($page - 1) * 50;

            if ($offset >= count($listFilesbrut)) {
                $page = 1;
                $offset = 0;

            };
            $listFilesPage = array_slice($listFilesbrut, $offset, 50);
        } else {
            $listFilesPage = null;

            $i = 0;
            foreach ($listFilesbrut as $file) {
                if ($file !== '.tmb' && $file !== '.' && $file !== '..' && $file !== 'thumbs') {
                    if (file_exists($path . '/' . $file)) {
                        $type = is_dir($path . '/' . $file) ? 'folder' : (str_contains(mime_content_type($path . '/' . $file), 'image') ? 'image' : 'file');
                        $listFilesPage[$i] = [$file, $type];
                        $i++;
                    }
                }
            }
        }

        if ($order === 'desc') {
            $listFilesPage = array_reverse($listFilesPage);
        }

        return $this->render('bundles/EasyAdminBundle/odpf/browser_index.html.twig', [
            'path' => $path,
            'subfolder' => $subfolder,
            'listeFiles' => $listFilesPage,
            'sort' => $sort,
            'order' => $order,
            'page' => $page,
            'nbPages' => $nbPages,
        ]);
    }


}
