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
        $rsData = $this->returnData('order_min_price', $order_min_price->decimal_value);
        return response()->json($rsData, 200);


      }

      public function today_max_price()
      {
        $today_max_price = settings::select('decimal_value')->where('name','today_max_price')->first();
        $rsData = $this->returnData('today_max_price', $today_max_price->decimal_value);
        return response()->json($rsData, 200);


      }

      public function sales_tax()
      {
        $sales_tax = settings::select('decimal_value')->where('name','sales_tax')->first();
        $rsData = $this->returnData('sales_tax', $sales_tax->decimal_value);
        return response()->json($rsData, 200);


      }

      public function settings()
      {
        $settings = settings::all();
        $data = array();
        foreach ($settings as $setting ) {
           if($setting->type == "decimal"){
            $arr1 = array($setting->name => $setting->decimal_value);
            $data = $data + $arr1;          
           }elseif($setting->type == "text")
           {
            $arr1 = array($setting->name => $setting->text_value);
            $data = $data + $arr1;             
          }elseif($setting->type == "date")
           {
            $arr1 = array($setting->name => $setting->date_value);
            $data = $data + $arr1;        
           }

        }
        $rsData = $this->returnData('data', $data);
        return response()->json($rsData, 200);

      }




}
