<?php

declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Session;
use Core\Validator;
use Services\AuthService;
use Models\User;

class UserController extends Controller
{
    public function showProfile(): void
    {
        $this->requireAuth();

        $user = AuthService::getCurrentUser();

        $this->render('user/profile', [
            'title' => 'Profile',
            'user' => $user
        ]);
    }

    public function updateProfile(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $data = $this->getRequestBody();
        $currentUser = AuthService::getCurrentUser();

        $validator = new Validator($data);
        $validator
            ->required('username')
            ->minLength('username', 3)
            ->maxLength('username', 50)
            ->username('username')
            ->required('email')
            ->email('email');

        if ($validator->fails()) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            Session::flash('errors', $validator->errors());
            Session::flash('old', $data);
            $this->redirect('/profile');
        }

        // Check username uniqueness if changed
        if ($data['username'] !== $currentUser['username'] && User::usernameExists($data['username'], $currentUser['id'])) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Username already taken'], 400);
            }
            Session::flash('error', 'Username already taken');
            Session::flash('old', $data);
            $this->redirect('/profile');
        }

        // Check email uniqueness if changed
        if ($data['email'] !== $currentUser['email'] && User::emailExists($data['email'], $currentUser['id'])) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Email already in use'], 400);
            }
            Session::flash('error', 'Email already in use');
            Session::flash('old', $data);
            $this->redirect('/profile');
        }

        $updateData = [
            'username' => $data['username'],
            'email' => $data['email'],
            'notify_comments' => isset($data['notify_comments']) ? 1 : 0
        ];

        $result = AuthService::updateProfile($updateData);

        if ($this->isAjax()) {
            if ($result['success']) {
                $this->json(['success' => true, 'message' => 'Profile updated successfully']);
            } else {
                $this->json(['success' => false, 'error' => $result['error']], 400);
            }
        }

        if ($result['success']) {
            Session::flash('success', 'Profile updated successfully');
        } else {
            Session::flash('error', $result['error']);
        }

        $this->redirect('/profile');
    }

    public function changePassword(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $data = $this->getRequestBody();

        $validator = new Validator($data);
        $validator
            ->required('current_password', 'Current password is required')
            ->required('new_password', 'New password is required')
            ->minLength('new_password', 8)
            ->password('new_password')
            ->confirmed('new_password', 'new_password_confirm', 'Passwords do not match');

        if ($validator->fails()) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            Session::flash('errors', $validator->errors());
            $this->redirect('/profile');
        }

        $result = AuthService::changePassword($data['current_password'], $data['new_password']);

        if ($this->isAjax()) {
            if ($result['success']) {
                $this->json(['success' => true, 'message' => $result['message']]);
            } else {
                $this->json(['success' => false, 'error' => $result['error']], 400);
            }
        }

        if ($result['success']) {
            Session::flash('success', $result['message']);
        } else {
            Session::flash('error', $result['error']);
        }

        $this->redirect('/profile');
    }
}
