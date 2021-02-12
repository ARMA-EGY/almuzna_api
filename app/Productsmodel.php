<?php

namespace App;
use Illuminate\Database\Eloquent\Model;

class Productsmodel extends Model
{
      protected $table = 'products';

      protected $fillable = [
        'name_en','name_ar' , 'price','photo'


    ];
    


}
