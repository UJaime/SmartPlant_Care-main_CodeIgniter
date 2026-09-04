<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $table = 'usuarios';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'nombre',
        'apellido',
        'email',
        'password',
        'rol',
        'plan',
        'telefono',
        'foto_perfil',
        'creado_en',
        'reset_token',
        'reset_expira',
    ];

    /**
     * Busca un usuario por su dirección de correo
     */
    public function buscarPorEmail(string $email): ?array
    {
        return $this->where('LOWER(email)', strtolower(trim($email)))->first();
    }

    /**
     * Verifica las credenciales de inicio de sesión
     */
    public function verificarLogin(string $email, string $password): ?array
    {
        $usuario = $this->buscarPorEmail($email);
        if ($usuario && password_verify($password, $usuario['password'])) {
            return $usuario;
        }
        return null;
    }

    /**
     * Actualiza la información del perfil del usuario
     */
    public function actualizarPerfil(int $id, array $datos): bool
    {
        return (bool) $this->update($id, $datos);
    }

    /**
     * Crea un nuevo usuario con la contraseña hasheada
     */
    public function registrarUsuario(array $datos): int|string|false
    {
        if (isset($datos['password']) && !empty($datos['password'])) {
            // Asegurar hash si aún no está hasheada
            $info = password_get_info($datos['password']);
            if ($info['algo'] === 0) {
                $datos['password'] = password_hash($datos['password'], PASSWORD_DEFAULT);
            }
        }
        if (!isset($datos['rol']))  $datos['rol'] = 'cliente';
        if (!isset($datos['plan'])) $datos['plan'] = 'free';

        return $this->insert($datos, true);
    }
}
