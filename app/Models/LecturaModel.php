<?php

namespace App\Models;

use CodeIgniter\Model;

class LecturaModel extends Model
{
    protected $table = 'lecturas_sensores';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'dispositivo_id',
        'planta_id',
        'humedad_suelo',
        'temperatura',
        'humedad_ambiente',
        'luz_ambiental',
        'ph',
        'nivel_tanque',
        'bateria',
        'riego_activo',
        'fuente_5v',
        'creada_en',
    ];

    /**
     * Obtiene la lectura más reciente para una planta
     */
    public function obtenerUltimaLectura(int $plantaId): ?array
    {
        return $this->where('planta_id', $plantaId)
            ->orderBy('creada_en', 'DESC')
            ->first();
    }

    /**
     * Obtiene el historial de humedad de las últimas N horas
     */
    public function obtenerHistorialHumedad(int $plantaId, int $horas = 12): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table($this->table);
        $builder->select("humedad_suelo, DATE_FORMAT(creada_en, '%H:%i') AS hora");
        $builder->where('planta_id', $plantaId);
        $builder->where("creada_en >= NOW() - INTERVAL {$horas} HOUR");
        $builder->orderBy('creada_en', 'ASC');
        $builder->limit(24);
        
        return $builder->get()->getResultArray();
    }
}
