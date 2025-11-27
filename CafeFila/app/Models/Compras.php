<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compras extends Model
{
    protected $table = "compra";

    protected $primaryKey = "id";

    public $timestamps = false;

    protected $fillable = [
    'usuario_id',
    'item',
    'quantidade',
    'data_compra',
    'ultima_alteracao_por',
    'ultima_alteracao_em',
];
public function alteradoPor()
{
    return $this->belongsTo(Usuario::class, 'ultima_alteracao_por');
}


}
