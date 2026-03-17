<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Audit;
use App\Core\Csrf;
use App\Core\Response;

final class AuthController
{
    public function showLogin(): void
    {
        Response::view('auth/login');
    }

    public function loginAdmin(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            flash('error', 'Token CSRF inválido');
            Response::redirect('/login');
        }

        $admin = Auth::adminByEmail(trim($_POST['email'] ?? ''));
        if (!$admin || !(int)$admin['is_active'] || !password_verify($_POST['password'] ?? '', $admin['password_hash'])) {
            flash('error', 'Credenciales inválidas');
            Response::redirect('/login');
        }

        Auth::loginAdmin($admin);
        Audit::log('login', 'admin', (int)$admin['id'], null, ['email' => $admin['email']]);
        Response::redirect('/admin/dashboard');
    }

    public function loginEmployee(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            flash('error', 'Token CSRF inválido');
            Response::redirect('/login');
        }

        $employee = Auth::employeeByEmail(trim($_POST['email'] ?? ''));
        if (!$employee || !(int)$employee['is_active'] || !password_verify($_POST['password'] ?? '', $employee['password_hash'])) {
            flash('error', 'Credenciales inválidas');
            Response::redirect('/login');
        }

        Auth::loginEmployee($employee);
        Audit::log('login', 'employee', (int)$employee['id'], null, ['email' => $employee['email']]);
        Response::redirect('/employee/dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        Response::redirect('/login');
    }
}
