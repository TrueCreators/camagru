<?php

declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Session;
use Models\Image;
use Services\ImageService;

class EditorController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();

        $overlays = ImageService::getOverlays();
        $userId = Session::getUserId();
        $userImages = Image::getUserImagesWithDetails($userId);

        $this->render('editor/index', [
            'title' => 'Editor',
            'overlays' => $overlays,
            'userImages' => $userImages
        ]);
    }
}
