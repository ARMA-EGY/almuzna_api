<?php

namespace App\Http\Controllers\Api\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Productsmodel;
use App\OrderItemsmodel;
use App\Ordersmodel;
use App\settings;
use App\Traits\GeneralTrait;
use Validator;


class UsersController extends Controller
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


      public function currentorders()
      {
        $currentorders = Ordersmodel::with('OrderItemsmodel.Productsmodel')->where([
                ['user_id', '=', 1],
                ['status', '!=', 'delivered'],
        ])->get();

        if($currentorders->isEmpty())
                return $this->returnError('O404','no order found');

        return $this->returnData('currentorders', $currentorders);

      }


      public function ordershistory()
      {
        $ordershistory = Ordersmodel::with('OrderItemsmodel.Productsmodel')->where([
                ['user_id', '=', 1],
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
                ['user_id', '=', 1],
                ['id', '=', $id],
        ])->get();

        if($order->isEmpty())
                return $this->returnError('O404','no order found');

        return $this->returnData('order', $order);
      }

      public function orderplace(Request $request)
      {
        try {

              //validation on the request
                  //Add validation for products array learn from laravel docs.
              $rules = [
                  "payment_method" => "required|alpha_dash",
                  "delivery_date" => "required|date",
                  "delivery_address" => "required|alpha_dash",
                  "street_no_name" => "nullable|alpha_dash",
                  "bulding_no" => "nullable|alpha_dash",
                  "floor" => "nullable|alpha_dash",
                  "apartment" => "nullable|alpha_dash",
                  "notes" => "nullable|alpha_dash",
                  "products" => "required",                
              ];
              $validator = Validator::make($request->all(), $rules);
              if ($validator->fails()) {
                  $code = $this->returnCodeAccordingToInput($validator);
                  return $this->returnValidationError($code, $validator);
              }


              //calculate order total price
              $products = $request->post('products');
              $orderTotal = 0;
              $itemTotal = 0;
              foreach ($products as $product) {
                $dbProduct = Productsmodel::where('name_en', $product['name'])->orWhere('name_ar', $product['name'])->get();
                $itemTotal = $dbProduct[0]->price * $product['quantity'];
                $orderTotal = $orderTotal + $itemTotal;
              }


              //check that the order meet the delivery date requirments
              $today_max_price = settings::where('name','today_max_price')
              ->get('decimal_value');
              if(  date('Ymd', strtotime($request->post('delivery_date'))) < date('Ymd') )
                return $this->returnError('O001','please enter a valid date');
              if($orderTotal > $today_max_price[0]->decimal_value && date('Ymd', strtotime($request->post('delivery_date'))) == date('Ymd'))
                return $this->returnError('O001','order can not be delivered today');


              //validate that the order is greater than the minimum order price
              $order_min_price = settings::where('name','order_min_price')
              ->get('decimal_value');
              if($orderTotal <= $order_min_price[0]->decimal_value)
                return $this->returnError('O002','order do not exceed minimum amount');


              //insert order in db           
              $order = Ordersmodel::create([ 
                'user_id' => 1,
                'status' => 'pending',
                'step' => 1,
                'payment_method' => $request->post('payment_method'), 
                'delivery_date' => $request->post('delivery_date'),
                'delivery_address' => $request->post('delivery_address'),
                'street_no_name' => $request->post('street_no_name'),
                'bulding_no' => $request->post('bulding_no'),
                'floor' => $request->post('floor'),
                'apartment' => $request->post('apartment'),
                'notes' => $request->post('notes'),
                'total' => $orderTotal
              ]);


              //add order items to db
              $itemTotal = 0;
              foreach ($products as $product) {
                $dbProduct = Productsmodel::where('name_en', $product['name'])
                ->orWhere('name_ar', $product['name'])
                ->get();
                $itemTotal = $dbProduct[0]->price * $product['quantity'];
                OrderItemsmodel::create([ 
                'product_id' => $dbProduct[0]->id,
                'order_id' => $order->id,
                'quantity' => $product['quantity'],
                'total' => $itemTotal
                ]);
              }




        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
        return $this->returnSuccessMessage('order is placed successfully');
      }

      public function reorder(Request $request)
      {
        try {
              
              $rules = [
                  "lastOrder_id" => "required|numeric",
                  "payment_method" => "required|alpha_dash",
                  "delivery_date" => "required|date",
                  "delivery_address" => "required|alpha_dash",
                  "street_no_name" => "nullable|alpha_dash",
                  "bulding_no" => "nullable|alpha_dash",
                  "floor" => "nullable|alpha_dash",
                  "apartment" => "nullable|alpha_dash",
                  "notes" => "nullable|alpha_dash",               
              ];

              $validator = Validator::make($request->all(), $rules);

              if ($validator->fails()) {
                  $code = $this->returnCodeAccordingToInput($validator);
                  return $this->returnValidationError($code, $validator);
              }

             

              //calculate the total
              $order = Ordersmodel::create([ 
                'user_id' => 1,
                'status' => 'pending',
                'step' => 1,
                'payment_method' => $request->post('payment_method'), 
                'delivery_date' => $request->post('delivery_date'),
                'delivery_address' => $request->post('delivery_address'),
                'street_no_name' => $request->post('street_no_name'),
                'bulding_no' => $request->post('bulding_no'),
                'floor' => $request->post('floor'),
                'apartment' => $request->post('apartment'),
                'notes' => $request->post('notes'),
                'total' => 0
              ]);

              $items = OrderItemsmodel::where('order_id',$request->post('lastOrder_id'))->get();

              //validation on delivery date compared to minimum price
              $orderTotal = 0;
              foreach ($items as $item) {


                $orderTotal = $orderTotal + $item->total;

                OrderItemsmodel::create([ 
                'product_id' => $item->product_id,
                'order_id' => $order->id,
                'quantity' => $item->quantity,
                'total' => $item->total
                ]);


              }

              Ordersmodel::where('id',$order->id)->update([
                  'total' => $orderTotal
              ]);


        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
        return $this->returnSuccessMessage('order is placed successfully');        
      }      



}
