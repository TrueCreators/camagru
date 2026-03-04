<?php

declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Session;
use Models\Image;
use Services\ImageService;

class GalleryController extends Controller
{
    public function index(): void
    {
        $page = (int)($_GET['page'] ?? 1);
        if ($page < 1) $page = 1;

        $userId = Session::getUserId();
        $images = Image::getAllWithDetails($page, 5, $userId);
        $overlays = ImageService::getOverlays();

        $this->render('gallery/index', [
            'title' => 'Gallery',
            'images' => $images,
            'overlays' => $overlays,
            'currentPage' => $page
        ]);
    }
}
