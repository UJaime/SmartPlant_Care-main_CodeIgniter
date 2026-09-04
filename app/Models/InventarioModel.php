<?php

namespace App\Models;

use CodeIgniter\Model;

class InventarioModel extends Model
{
    protected $table = 'inventario_plantas';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'planta_id',
        'usuario_id',
        'foto_path',
        'diagnostico',
        'fecha',
    ];

    /**
     * Obtiene el inventario fotográfico de una planta
     */
    public function obtenerPorPlanta(int $plantaId, int $usuarioId, int $limite = 20): array
    {
        return $this->where('planta_id', $plantaId)
            ->where('usuario_id', $usuarioId)
            ->orderBy('fecha', 'DESC')
            ->findAll($limite);
    }
}
