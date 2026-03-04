<?php

declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Session;
use Core\Validator;
use Core\CSRF;
use Services\AuthService;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->requireGuest();
        $this->render('auth/login', ['title' => 'Login']);
    }

    public function login(): void
    {
        $this->requireGuest();
        $this->validateCsrf();

        $data = $this->getRequestBody();

        $validator = new Validator($data);
        $validator
            ->required('login', 'Email or username is required')
            ->required('password', 'Password is required');

        if ($validator->fails()) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            Session::flash('errors', $validator->errors());
            Session::flash('old', $data);
            $this->redirect('/login');
        }

        $result = AuthService::login($data['login'], $data['password']);

        if ($this->isAjax()) {
            if ($result['success']) {
                $this->json(['success' => true, 'redirect' => '/gallery']);
            } else {
                $this->json(['success' => false, 'error' => $result['error']], 401);
            }
        }

        if ($result['success']) {
            $this->redirect('/gallery');
        }

        Session::flash('error', $result['error']);
        Session::flash('old', $data);
        $this->redirect('/login');
    }

    public function showRegister(): void
    {
        $this->requireGuest();
        $this->render('auth/register', ['title' => 'Register']);
    }

    public function register(): void
    {
        $this->requireGuest();
        $this->validateCsrf();

        $data = $this->getRequestBody();

        $validator = new Validator($data);
        $validator
            ->required('username')
            ->minLength('username', 3)
            ->maxLength('username', 50)
            ->username('username')
            ->required('email')
            ->email('email')
            ->required('password')
            ->minLength('password', 8)
            ->password('password')
            ->confirmed('password', 'password_confirm', 'Passwords do not match');

        if ($validator->fails()) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            Session::flash('errors', $validator->errors());
            Session::flash('old', $data);
            $this->redirect('/register');
        }

        $result = AuthService::register($data['username'], $data['email'], $data['password']);

        if ($this->isAjax()) {
            if ($result['success']) {
                $this->json(['success' => true, 'message' => $result['message']]);
            } else {
                $this->json(['success' => false, 'error' => $result['error']], 400);
            }
        }

        if ($result['success']) {
            Session::flash('success', $result['message']);
            $this->redirect('/login');
        }

        Session::flash('error', $result['error']);
        Session::flash('old', $data);
        $this->redirect('/register');
    }

    public function logout(): void
    {
        $this->validateCsrf();
        AuthService::logout();

        if ($this->isAjax()) {
            $this->json(['success' => true, 'redirect' => '/']);
        }

        $this->redirect('/');
    }

    public function verify(): void
    {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            Session::flash('error', 'Invalid verification link');
            $this->redirect('/login');
        }

        $result = AuthService::verifyEmail($token);

        if ($result['success']) {
            Session::flash('success', $result['message']);
        } else {
            Session::flash('error', $result['error']);
        }

        $this->redirect('/login');
    }

    public function showForgotPassword(): void
    {
        $this->requireGuest();
        $this->render('auth/forgot-password', ['title' => 'Forgot Password']);
    }

    public function forgotPassword(): void
    {
        $this->requireGuest();
        $this->validateCsrf();

        $data = $this->getRequestBody();

        $validator = new Validator($data);
        $validator
            ->required('email')
            ->email('email');

        if ($validator->fails()) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            Session::flash('errors', $validator->errors());
            $this->redirect('/forgot-password');
        }

        $result = AuthService::requestPasswordReset($data['email']);

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => $result['message']]);
        }

        Session::flash('success', $result['message']);
        $this->redirect('/forgot-password');
    }

    public function showResetPassword(): void
    {
        $this->requireGuest();
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            Session::flash('error', 'Invalid reset link');
            $this->redirect('/forgot-password');
        }

        $this->render('auth/reset-password', [
            'title' => 'Reset Password',
            'token' => $token
        ]);
    }

    public function resetPassword(): void
    {
        $this->requireGuest();
        $this->validateCsrf();

        $data = $this->getRequestBody();

        $validator = new Validator($data);
        $validator
            ->required('token')
            ->required('password')
            ->minLength('password', 8)
            ->password('password')
            ->confirmed('password', 'password_confirm', 'Passwords do not match');

        if ($validator->fails()) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            Session::flash('errors', $validator->errors());
            $this->redirect('/reset-password?token=' . ($data['token'] ?? ''));
        }

        $result = AuthService::resetPassword($data['token'], $data['password']);

        if ($this->isAjax()) {
            if ($result['success']) {
                $this->json(['success' => true, 'message' => $result['message'], 'redirect' => '/login']);
            } else {
                $this->json(['success' => false, 'error' => $result['error']], 400);
            }
        }

        if ($result['success']) {
            Session::flash('success', $result['message']);
            $this->redirect('/login');
        }

        Session::flash('error', $result['error']);
        $this->redirect('/forgot-password');
    }
}
