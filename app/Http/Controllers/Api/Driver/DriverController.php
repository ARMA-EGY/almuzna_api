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
        {
          $error = $this->returnError('O404','no order found');
          return response()->json($error, 404);          
        } 

        $rsData = $this->returnData('assignedOrders', $assignedOrders);
        return response()->json($rsData, 200);   

      }


      public function ordershistory()
      {
        $ordershistory = Ordersmodel::with('OrderItemsmodel.Productsmodel')->where([
                ['driver_id', '=', 1],
                ['status', '=', 'delivered'],
        ])->get();

        if($ordershistory->isEmpty())
        {
          $error = $this->returnError('O404','no order found');
          return response()->json($error, 404);          
        } 

        $rsData = $this->returnData('ordershistory', $ordershistory);
        return response()->json($rsData, 200);  


      }

      public function order($orderId)
      {
        $id = $orderId;
        $order = Ordersmodel::with('OrderItemsmodel.Productsmodel')->where([
                ['driver_id', '=', 1],
                ['id', '=', $id],
        ])->get();

        if($order->isEmpty())
        {
          $error = $this->returnError('O404','no order found');
          return response()->json($error, 404);          
        }   

        $rsData = $this->returnData('order', $order);
        return response()->json($rsData, 200);       }


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
            $error = $this->returnValidationError($code, $validator);
            return response()->json($error, 422);

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

            $succesMsg = $this->returnSuccessMessage('order started successfully' , 'S003');
            return response()->json($succesMsg, 200);                
          }


          //error message
           $error = $this->returnError('O408','There is an ongoing order');
            return response()->json($error, 422);   

          
        } catch (\Exception $ex) {
            $error = $this->returnError($ex->getCode(),$ex->getMessage());
            return response()->json($error, 500);  
                }
      }      



}
