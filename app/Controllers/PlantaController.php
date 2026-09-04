<?php

namespace App\Controllers;

use App\Models\PlantaModel;

class PlantaController extends BaseController
{
    protected PlantaModel $plantaModel;

    public function __construct()
    {
        $this->plantaModel = new PlantaModel();
    }

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