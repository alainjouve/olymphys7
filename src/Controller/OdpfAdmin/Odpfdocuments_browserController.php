<?php

declare(strict_types=1);

namespace App\Controller\OdpfAdmin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class Odpfdocuments_browserController extends AbstractController
{
    #[Route('/odpfdocuments_browser', name: 'odpfdocuments_browser')]
    #[Isgranted('ROLE_ADMIN')]
    public function index(Request $request): Response
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

        $listFilesbrut = scandir($path);

        $listFiles = [];
        foreach ($listFilesbrut as $file) {
            if ($file !== '.tmb' && $file !== '.' && $file !== '..') {
                $type = is_dir($path . '/' . $file) ? 'folder' : (str_contains(mime_content_type($path . '/' . $file), 'image') ? 'image' : 'file');
                $listFiles[] = [$file, date('d/m/Y à H:i', filemtime($path . '/' . $file)), date('d/m/Y à H:i', filectime($path . '/' . $file)), $type, filesize($path . '/' . $file)];
            }
        }
        usort($listFiles, function ($a, $b) use ($sort) {
            return $sort === 'date' ? ($a[3] <=> $b[3]) : strcasecmp($a[0], $b[0]);
        });
        if ($order === 'desc') {
            $listFiles = array_reverse($listFiles);
        }
        return $this->render('bundles/EasyAdminBundle/odpf/browser_index.html.twig', [
            'path' => $path,
            'subfolder' => $subfolder,
            'listeFiles' => $listFiles,
            'sort' => $sort,
            'order' => $order,
        ]);
    }
}
