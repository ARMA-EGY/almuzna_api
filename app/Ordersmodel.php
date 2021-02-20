<?php

namespace App;
use Illuminate\Database\Eloquent\Model;
use App\OrderItemsmodel;

class Ordersmodel  extends Model
{
      protected $table = 'orders';

      protected $fillable = [
        'user_id','status' , 'step','payment_method','delivery_date','delivery_address','street_no_name','bulding_no','floor','apartment','notes','total','driver_id'
    ];
    

    public function OrderItemsmodel(){
        return $this->hasMany('App\OrderItemsmodel','order_id');
    }
}
