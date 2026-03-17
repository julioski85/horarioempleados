<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Env;
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

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $adminEmail = Env::get('ADMIN_EMAIL', 'admin@gym.local');
        $adminPass = Env::get('ADMIN_PASS', 'Admin123!');
        $adminPassHash = Env::get('ADMIN_PASS_HASH');

        $validPassword = $adminPassHash
            ? password_verify($password, $adminPassHash)
            : hash_equals($adminPass, $password);

        if (!hash_equals((string) $adminEmail, $email) || !$validPassword) {
            $_SESSION['error'] = 'Credenciales de administrador inválidas.';
            Response::redirect('/login');
        }

        Auth::login(['name' => 'Administrador', 'email' => $email, 'role' => 'admin']);
        session_regenerate_id(true);
        Response::redirect('/admin/dashboard');
    }

    public function loginEmployee(): void
    {
        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            $_SESSION['error'] = 'Sesión expirada.';
            Response::redirect('/login');
        }
        Auth::login(['name' => 'Empleado', 'email' => $_POST['email'] ?? '', 'role' => 'employee']);
        session_regenerate_id(true);
        Response::redirect('/employee/dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        session_regenerate_id(true);
        Response::redirect('/login');
    }
}
