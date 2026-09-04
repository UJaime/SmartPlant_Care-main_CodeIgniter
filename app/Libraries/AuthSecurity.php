<?php

namespace App\Libraries;

use mysqli;

class AuthSecurity
{
    private const MIN_PASSWORD_LENGTH = 8;
    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_LOCK_SECONDS = 600;
    private const RESET_TOKEN_SECONDS = 3600;
    private const ROLES = ['admin', 'cliente', 'tecnico'];

    public static function startSession(): void
    {
        service('session');
    }

    public static function isLoggedIn(): bool
    {
        self::startSession();

        return ! empty($_SESSION['usuario_id']);
    }

    public static function redirectIfLoggedIn(string $target = '/dashboard'): void
    {
        if (self::isLoggedIn()) {
            header('Location: ' . $target);
            exit;
        }
    }

    public static function requireLogin(string $target = '/login'): void
    {
        if (! self::isLoggedIn()) {
            header('Location: ' . $target);
            exit;
        }
    }

    public static function loginUser(array $user, ?string $email = null): void
    {
        self::startSession();
        session_regenerate_id(true);

        $_SESSION['usuario_id']   = (int) $user['id'];
        $_SESSION['usuario']      = $email ?? (string) ($user['email'] ?? '');
        $_SESSION['nombre']       = (string) ($user['nombre'] ?? '');
        $_SESSION['plan']         = (string) ($user['plan'] ?? 'free');
        $_SESSION['rol']          = self::normalizeRole((string) ($user['rol'] ?? 'cliente'));
        $_SESSION['foto_perfil']  = (string) ($user['foto_perfil'] ?? '');
        $_SESSION['auth_started'] = time();
    }

    public static function logout(string $target = '/login'): void
    {
        self::startSession();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
        header('Location: ' . $target);
        exit;
    }

    public static function allowedRoles(): array
    {
        return self::ROLES;
    }

    public static function normalizeRole(string $role): string
    {
        return in_array($role, self::ROLES, true) ? $role : 'cliente';
    }

    public static function canEditRoles(): bool
    {
        self::startSession();

        return ($_SESSION['rol'] ?? 'cliente') === 'admin';
    }

    public static function passwordErrorMessage(string $password): ?string
    {
        $errors = self::passwordErrors($password);

        return $errors === []
            ? null
            : 'La contrasena debe tener al menos 8 caracteres, una mayuscula, una minuscula, un numero y un simbolo.';
    }

    public static function passwordErrors(string $password): array
    {
        $errors = [];

        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            $errors[] = 'length';
        }

        if (! preg_match('/[a-z]/', $password)) {
            $errors[] = 'lowercase';
        }

        if (! preg_match('/[A-Z]/', $password)) {
            $errors[] = 'uppercase';
        }

        if (! preg_match('/[0-9]/', $password)) {
            $errors[] = 'number';
        }

        if (! preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'symbol';
        }

        return $errors;
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function ensureUsuarioSecurityColumns(mysqli $db): void
    {
        $columns = [
            'apellido'     => "ALTER TABLE usuarios ADD COLUMN apellido VARCHAR(100) DEFAULT NULL AFTER nombre",
            'telefono'     => "ALTER TABLE usuarios ADD COLUMN telefono VARCHAR(20) DEFAULT NULL",
            'foto_perfil'  => "ALTER TABLE usuarios ADD COLUMN foto_perfil VARCHAR(255) DEFAULT NULL",
            'rol'          => "ALTER TABLE usuarios ADD COLUMN rol ENUM('admin','cliente','tecnico') NOT NULL DEFAULT 'cliente' AFTER password",
            'reset_token'  => "ALTER TABLE usuarios ADD COLUMN reset_token VARCHAR(64) DEFAULT NULL",
            'reset_expira' => "ALTER TABLE usuarios ADD COLUMN reset_expira DATETIME DEFAULT NULL",
        ];

        foreach ($columns as $column => $sql) {
            if (! self::columnExists($db, 'usuarios', $column)) {
                $db->query($sql);
            }
        }
    }

    public static function createPasswordReset(mysqli $db, int $userId): array
    {
        self::ensureUsuarioSecurityColumns($db);

        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + self::RESET_TOKEN_SECONDS);

        $stmt = $db->prepare('UPDATE usuarios SET reset_token = ?, reset_expira = ? WHERE id = ?');
        $stmt->bind_param('ssi', $hash, $expiresAt, $userId);
        $stmt->execute();

        return ['token' => $token, 'expires_at' => $expiresAt];
    }

    public static function resetTokenIsValid(?string $storedToken, ?string $expiresAt, string $plainToken): bool
    {
        if (! $storedToken || ! $expiresAt || $plainToken === '') {
            return false;
        }

        if (strtotime($expiresAt) <= time()) {
            return false;
        }

        $expectedHash = hash('sha256', $plainToken);

        return hash_equals($storedToken, $expectedHash) || hash_equals($storedToken, $plainToken);
    }

    public static function siteUrl(string $path = ''): string
    {
        $base = env('app.baseURL') ?: (config('App')->baseURL ?? '');

        if ($base === '') {
            $scheme = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
            $base = $scheme . '://' . $host . '/';
        }

        return rtrim((string) $base, '/') . '/' . ltrim($path, '/');
    }

    public static function loginBlockedMessage(string $email): ?string
    {
        self::startSession();
        $key = self::throttleKey($email);
        $state = $_SESSION['login_throttle'][$key] ?? null;

        if (! is_array($state)) {
            return null;
        }

        $lockedUntil = (int) ($state['locked_until'] ?? 0);
        if ($lockedUntil > time()) {
            $minutes = (int) ceil(($lockedUntil - time()) / 60);

            return 'Demasiados intentos fallidos. Proba de nuevo en ' . $minutes . ' min.';
        }

        return null;
    }

    public static function recordLoginFailure(string $email): void
    {
        self::startSession();
        $key = self::throttleKey($email);
        $state = $_SESSION['login_throttle'][$key] ?? ['attempts' => 0, 'locked_until' => 0];

        $state['attempts'] = (int) ($state['attempts'] ?? 0) + 1;
        if ($state['attempts'] >= self::LOGIN_MAX_ATTEMPTS) {
            $state['locked_until'] = time() + self::LOGIN_LOCK_SECONDS;
        }

        $_SESSION['login_throttle'][$key] = $state;
    }

    public static function clearLoginFailures(string $email): void
    {
        self::startSession();
        unset($_SESSION['login_throttle'][self::throttleKey($email)]);
    }

    private static function throttleKey(string $email): string
    {
        return hash('sha256', strtolower(trim($email)));
    }

    private static function columnExists(mysqli $db, string $table, string $column): bool
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $table . $column)) {
            return false;
        }

        $result = $db->query("SHOW COLUMNS FROM {$table} LIKE '{$db->real_escape_string($column)}'");

        return $result && $result->num_rows > 0;
    }
}
