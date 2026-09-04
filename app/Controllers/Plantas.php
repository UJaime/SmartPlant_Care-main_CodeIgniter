<?php

namespace App\Controllers;

use App\Models\PlantaModel;

class Plantas extends BaseController
{
    protected PlantaModel $plantaModel;

    public function __construct()
    {
        $this->plantaModel = new PlantaModel();
    }

    /**
     * Muestra el listado de plantas activas del usuario en sesión
     */
    public function index()
    {
        $usuarioId = session()->get('usuario_id') ?? 1;
        $data = [
            'titulo'  => 'Mis Plantas',
            'plantas' => $this->plantaModel->activasPorUsuario((int) $usuarioId),
        ];

        return view('Dashboard', $data);
    }
}
