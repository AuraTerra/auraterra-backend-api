<?php
namespace Models;

use Illuminate\Database\Eloquent\Model;

//Herencia
class Clima extends Model {
    protected $table = 'historial_clima';
    protected $fillable = ['ciudad', 'temperatura', 'descripcion', 'humedad', 'viento', 'usuario_id'];
}