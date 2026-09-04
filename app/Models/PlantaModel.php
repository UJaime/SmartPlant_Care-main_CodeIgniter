<?php

namespace App\Models;

use CodeIgniter\Model;

class PlantaModel extends Model
{
    protected $table = 'plantas';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'usuario_id',
        'nombre',
        'especie',
        'descripcion',
        'humedad_min',
        'humedad_max',
        'temp_min',
        'temp_max',
        'activa',
        'bateria',
    ];

    /**
     * Obtiene todas las plantas activas de un usuario
     */
    public function activasPorUsuario(int $usuarioId): array
    {
        return $this->where('usuario_id', $usuarioId)
            ->where('activa', 1)
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Obtiene una planta específica que pertenezca al usuario
     */
    public function obtenerPorId(int $id, int $usuarioId): ?array
    {
        return $this->where('id', $id)
            ->where('usuario_id', $usuarioId)
            ->where('activa', 1)
            ->first();
    }

    /**
     * Registra una nueva planta para el usuario
     */
    public function registrarPlanta(array $datos): int|string|false
    {
        $datos['activa'] = 1;
        return $this->insert($datos, true);
    }

    /**
     * Actualiza los límites ambientales de la planta
     */
    public function actualizarLimites(int $id, int $usuarioId, array $limites): bool
    {
        return (bool) $this->where('id', $id)
            ->where('usuario_id', $usuarioId)
            ->set([
                'humedad_min' => $limites['humedad_min'],
                'humedad_max' => $limites['humedad_max'],
                'temp_min'    => $limites['temp_min'],
                'temp_max'    => $limites['temp_max'],
            ])
            ->update();
    }

    /**
     * Marca una planta como inactiva (eliminación lógica)
     */
    public function eliminarPlanta(int $id, int $usuarioId): bool
    {
        return (bool) $this->where('id', $id)
            ->where('usuario_id', $usuarioId)
            ->set(['activa' => 0])
            ->update();
    }
}
