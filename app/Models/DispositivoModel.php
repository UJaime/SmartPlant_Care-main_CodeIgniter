<?php

namespace App\Models;

use CodeIgniter\Model;

class DispositivoModel extends Model
{
    protected $table = 'dispositivos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'usuario_id',
        'planta_id',
        'codigo',
        'api_key',
        'nombre',
        'tipo_dispositivo',
        'corresponde_a',
        'ubicacion',
        'firmware',
        'activo',
        'ultima_conexion',
    ];

    /**
     * Obtiene los dispositivos asociados a un usuario
     */
    public function obtenerPorUsuario(int $usuarioId): array
    {
        return $this->where('usuario_id', $usuarioId)
            ->where('activo', 1)
            ->orderBy('nombre', 'ASC')
            ->findAll();
    }
}
