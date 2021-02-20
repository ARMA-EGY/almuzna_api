<?php

namespace App;
use Illuminate\Database\Eloquent\Model;
use App\Productsmodel;

class OrderItemsmodel  extends Model
{
      protected $table = 'order_items';

      protected $fillable = [
        'product_id','order_id' , 'quantity', 'total'
    ];
    
	public function Productsmodel()
	{
	    return $this->belongsTo('App\Productsmodel','product_id');
	}


}
