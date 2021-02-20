<?php

namespace App\Http\Controllers\Api\Driver;

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

class DriverController extends Controller
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


      public function assignedOrders()
      {
        $assignedOrders = Ordersmodel::with('OrderItemsmodel.Productsmodel')->where([
                ['driver_id', '=', 1],
                ['status', '!=', 'delivered'],
        ])->get();

        if($assignedOrders->isEmpty())
                return $this->returnError('O404','no order found');

        return $this->returnData('assignedOrders', $assignedOrders);

      }


      public function ordershistory()
      {
        $ordershistory = Ordersmodel::with('OrderItemsmodel.Productsmodel')->where([
                ['driver_id', '=', 1],
                ['status', '=', 'delivered'],
        ])->get();

        if($ordershistory->isEmpty())
                return $this->returnError('O404','no order found');

        return $this->returnData('ordershistory', $ordershistory);

      }

      public function order(Request $request)
      {
        $id       = $request->get('id');
        $order = Ordersmodel::with('OrderItemsmodel.Productsmodel')->where([
                ['driver_id', '=', 1],
                ['id', '=', $id],
        ])->get();

        if($order->isEmpty())
                return $this->returnError('O404','no order found');

        return $this->returnData('order', $order);
      }


      public function startOrder(Request $request)
      {
        try {
          //validation on the request
          $rules = [
              "order_id" => "required|numeric"
          ];
          $validator = Validator::make($request->all(), $rules);
          if ($validator->fails()) {
              $code = $this->returnCodeAccordingToInput($validator);
              return $this->returnValidationError($code, $validator);
          }

          //select an ongoing order
          $startedOrder = Ordersmodel::with('OrderItemsmodel.Productsmodel')->where([
                  ['driver_id', '=', 1],
                  ['status', '=', 'on the way'],
          ])->get();


          //if there is no ongoing order start the order
          if($startedOrder->isEmpty())
          {
            Ordersmodel::where([
                  ['driver_id', '=', 1],
                  ['id', '=', $request->post('order_id')],
              ])
              ->update([
                     'status' => 'on the way'
                      ]);
            //success message
            return $this->returnSuccessMessage('order started successfully');  
          }


          //error message
          return $this->returnError('O408','There is an ongoing order');

          
        } catch (\Exception $ex) {
                      return $this->returnError($ex->getCode(), $ex->getMessage());
                }
      }      



}
