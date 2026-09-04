<?php

namespace App\Models;

use CodeIgniter\Model;

class EventoModel extends Model
{
    protected $table = 'eventos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'planta_id',
        'dispositivo_id',
        'tipo',
        'mensaje',
        'creado_en',
    ];

    /**
     * Obtiene los eventos recientes vinculados a una planta
     */
    public function obtenerRecientes(int $plantaId, int $limite = 5): array
    {
        return $this->where('planta_id', $plantaId)
            ->orderBy('creado_en', 'DESC')
            ->findAll($limite);
    }
}
