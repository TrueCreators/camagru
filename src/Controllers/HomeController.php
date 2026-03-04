<?php

declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Session;

class HomeController extends Controller
{
    public function index(): void
    {
        if (Session::isLoggedIn()) {
            $this->redirect('/gallery');
        }
        $this->render('home/index', ['title' => 'Welcome']);
    }
}
