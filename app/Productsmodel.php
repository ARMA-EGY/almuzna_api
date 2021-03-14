<?php

namespace App;
use Illuminate\Database\Eloquent\Model;
use App\OrderItemsmodel;

class Productsmodel extends Model
{
      protected $table = 'products';

      protected $fillable = [
        'name_en','name_ar' , 'price','photo','type'
    ];
    
    public function OrderItemsmodel(){
        return $this->hasMany('App\OrderItemsmodel','product_id');
    }

}
