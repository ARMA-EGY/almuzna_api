<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    //
    protected $fillable = [
        'name_ar', 'name_en', 'price', 'photo', 
    ];
}
