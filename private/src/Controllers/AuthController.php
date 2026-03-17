<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\View;

final class AuthController
{
    public function showLogin(): void
    {
        View::render('auth/login', ['csrf' => Csrf::token(), 'error' => $_SESSION['error'] ?? null], 'layouts/blank');
        unset($_SESSION['error']);
    }

    public function loginAdmin(): void
    {
        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            $_SESSION['error'] = 'Sesión expirada.';
            Response::redirect('/login');
        }
        Auth::login(['name' => 'Administrador', 'email' => $_POST['email'] ?? '', 'role' => 'admin']);
        Response::redirect('/admin/dashboard');
    }

    public function loginEmployee(): void
    {
        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            $_SESSION['error'] = 'Sesión expirada.';
            Response::redirect('/login');
        }
        Auth::login(['name' => 'Empleado', 'email' => $_POST['email'] ?? '', 'role' => 'employee']);
        Response::redirect('/employee/dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        session_regenerate_id(true);
        Response::redirect('/login');
    }
}
