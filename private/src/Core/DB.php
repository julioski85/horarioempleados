<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class DB
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $host = Env::get('DB_HOST', 'localhost');
        $db = Env::get('DB_NAME', 'u801126150_equipo');
        $user = Env::get('DB_USER', 'root');
        $pass = Env::get('DB_PASS', '');

        try {
            self::$pdo = new PDO(
                "mysql:host={$host};dbname={$db};charset=utf8mb4",
                $user,
                $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        } catch (PDOException) {
            self::$pdo = new PDO('sqlite::memory:');
            self::seedMock(self::$pdo);
        }

        return self::$pdo;
    }

    private static function seedMock(PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE employees(id INTEGER PRIMARY KEY, full_name TEXT, email TEXT, role TEXT, pin TEXT, status TEXT, photo_path TEXT)');
        $pdo->exec('CREATE TABLE attendance(id INTEGER PRIMARY KEY, employee_id INT, event_type TEXT, created_at TEXT)');
        $pdo->exec('CREATE TABLE requests(id INTEGER PRIMARY KEY, employee_id INT, type TEXT, status TEXT, created_at TEXT)');
        $pdo->exec("INSERT INTO employees(full_name,email,role,pin,status,photo_path) VALUES
          ('Ana Gómez','ana@gym.local','empleado','1234','Activo','/assets/uploads/base/avatar-base.svg'),
          ('Luis Pérez','luis@gym.local','empleado','5678','Inactivo','/assets/uploads/base/avatar-base.svg')");
        $pdo->exec("INSERT INTO attendance(employee_id,event_type,created_at) VALUES
          (1,'entrada',datetime('now','-2 hour')),(1,'salida',datetime('now','-1 hour')),(2,'entrada',datetime('now'))");
        $pdo->exec("INSERT INTO requests(employee_id,type,status,created_at) VALUES
          (1,'Vacaciones','Pendiente',datetime('now','-1 day')),(2,'Permiso','Aprobada',datetime('now'))");
    }
}
