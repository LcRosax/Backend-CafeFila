<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fila extends Model
{
    use SoftDeletes;

    protected $table = "fila";
    protected $primaryKey = "id";
    public $timestamps = true;

    protected $dates = ['deleted_at'];
    protected $fillable = ['usuario_id', 'posicao'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
