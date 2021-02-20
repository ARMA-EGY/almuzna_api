<?php

namespace App\Http\Controllers\Api\Settings;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Productsmodel;
use App\OrderItemsmodel;
use App\Ordersmodel;
use App\settings;
use App\Traits\GeneralTrait;
use Validator;

// array functions array_Walk, array_reduce, array_*
// $list_of_uids = [];
// foreach($results as $res){
//   $list_of_uids[] = $res["uid"];
// }
// $list_of_uids = array_reduce($results, function($item, $acc){
//   return $acc + $item["uid"]
// });

class SettingsController extends Controller
{

    use GeneralTrait;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    
    public function __construct()
    {
		  //$this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */


      public function order_min_price()
      {
        $order_min_price = settings::select('decimal_value')->where('name','order_min_price')->first();
        return $this->returnData('order_min_price', $order_min_price->decimal_value);

      }

      public function today_max_price()
      {
        $today_max_price = settings::select('decimal_value')->where('name','today_max_price')->first();
        return $this->returnData('today_max_price', $today_max_price->decimal_value);

      }

      public function sales_tax()
      {
        $sales_tax = settings::select('decimal_value')->where('name','sales_tax')->first();
        return $this->returnData('sales_tax', $sales_tax->decimal_value);

      }




}
