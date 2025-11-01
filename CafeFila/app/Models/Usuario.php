<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $database = "usuarios";

    protected $primaryKey = "id";

    public $timestamps = false;
}
