<?php

namespace Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model {
    // Le indicamos el nombre exacto de la tabla física de MySQL
    protected $table = 'usuarios';

    // Las columnas que permitimos que se carguen de forma automática
    protected $fillable = ['nombre', 'email', 'password', 'rol'];
}