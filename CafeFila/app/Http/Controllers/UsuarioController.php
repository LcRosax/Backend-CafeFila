<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function listar(){
        $consulta = Usuario::query();

        $usuarios = $consulta->get();

        return [$usuarios->toArray()];
    }
}
