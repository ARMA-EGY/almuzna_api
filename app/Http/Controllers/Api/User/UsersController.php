<?php

namespace App\Http\Controllers\Api\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Productsmodel;
use App\OrderItemsmodel;
use App\Ordersmodel;
use App\settings;
use App\Message;
use App\ReceiverEmail;
use App\Traits\GeneralTrait;
use Validator;
use App\Mail\ContactUs;
use Mail; 

// array functions array_Walk, array_reduce, array_*
// $list_of_uids = [];
// foreach($results as $res){
//   $list_of_uids[] = $res["uid"];
// }
// $list_of_uids = array_reduce($results, function($item, $acc){
//   return $acc + $item["uid"]
// });

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

//---------- TRANSACTIONS TABLE NEEDS TO BE ADDED WITH IT'S BACKEND -----------
      public function currentorders()
      {
        $user = auth()->user();
       // dd($user->id);
        $currentorders = Ordersmodel::with('OrderItemsmodel.Productsmodel')->where([
                ['user_id', '=', $user->id],
                ['status', '!=', 'delivered'],
        ])->Where([
          ['user_id', '=', $user->id],
          ['status', '!=', 'cancelled'],])
->get();

        if($currentorders->isEmpty())
        {
          $error = $this->returnError('O404','no order found');
          return response()->json($error, 404);          
        }


        $rsData = $this->returnData('currentorders', $currentorders);
        return response()->json($rsData, 200); 

      }


      public function ordershistory()
      {
        $user = auth()->user();
        $ordershistory = Ordersmodel::with('OrderItemsmodel.Productsmodel')->where([
                ['user_id', '=', $user->id],
                ['status', '=', 'delivered'],
        ])->orWhere([
          ['user_id', '=', $user->id],
          ['status', '=', 'cancelled'],])
->get();

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
        $id       = $orderId;
        $user = auth()->user();
        $order = Ordersmodel::with('OrderItemsmodel.Productsmodel')->where([
                ['user_id', '=',  $user->id],
                ['id', '=', $id],
        ])->get();

        if($order->isEmpty())
        {
          $error = $this->returnError('O404','no order found');
          return response()->json($error, 404);          
        }

        $rsData = $this->returnData('order', $order);
        return response()->json($rsData, 200);  
      }

      //add the check on wallet if the wallet the payment
      public function orderplace(Request $request)
      {
        try {
              $user = auth()->user();
              //validation on the request
                  //Add validation for products array learn from laravel docs.
              $rules = [
                  "payment_method" => "required|string",
                  "delivery_date" => "required|date",
                  "delivery_address" => "required|string",
                  "orderlat" => "required|string",
                  "orderlong" => "required|string",
                  "street_no_name" => "nullable|string",
                  "bulding_no" => "nullable|string",
                  "floor" => "nullable|string",
                  "apartment" => "nullable|string",
                  "notes" => "nullable|string",
                  "sales_tax" => "required",
                  "delivery_fees" => "required",
                  "subtotal" => "required",
                  "total" => "required",
                  "products" => "required",                
              ];
              $validator = Validator::make($request->all(), $rules);
              if ($validator->fails()) {
                  $code = $this->returnCodeAccordingToInput($validator);
                  $error = $this->returnValidationError($code, $validator);
                  return response()->json($error, 422);
              }


              //calculate order total price
              $products = $request->post('products');
              $orderTotal = 0;
              $itemTotal = 0;
              foreach ($products as $product) {
                $dbProduct = Productsmodel::find($product['id']);
                $itemTotal = $dbProduct->price * $product['quantity'];
                $orderTotal = $orderTotal + $itemTotal;
              }


              //check that the order meet the delivery date requirments
              $today_max_price = settings::where('name','today_max_price')
              ->get('decimal_value');
              if(  date('Ymd', strtotime($request->post('delivery_date'))) < date('Ymd') )
              {
                $error = $this->returnError('O001','please enter a valid date');
                return response()->json($error, 422);          
              }  
              if($orderTotal > $today_max_price[0]->decimal_value && date('Ymd', strtotime($request->post('delivery_date'))) == date('Ymd'))
              {
                $error = $this->returnError('O002','order can not be delivered today');
                return response()->json($error, 422);          
              }   


              //validate that the order is greater than the minimum order price
              $order_min_price = settings::where('name','order_min_price')
              ->get('decimal_value');
              if($orderTotal <= $order_min_price[0]->decimal_value)
              {
                $error = $this->returnError('O003','order do not exceed minimum amount');
                return response()->json($error, 422);          
              }                  


              //insert order in db           
              $order = Ordersmodel::create([ 
                'user_id' =>  $user->id,
                'status' => 'pending',
                'step' => 1,
                'payment_method' => $request->post('payment_method'), 
                'delivery_date' => $request->post('delivery_date'), 
                'delivery_address' => $request->post('delivery_address'),     
                'orderlat' => $request->post('orderlat'),
                'orderlong' => $request->post('orderlong'),
                'street_no_name' => $request->post('street_no_name'),
                'bulding_no' => $request->post('bulding_no'),
                'floor' => $request->post('floor'),
                'apartment' => $request->post('apartment'),
                'notes' => $request->post('notes'),
                'sales_tax' => $request->post('sales_tax'),
                'delivery_fees' => $request->post('delivery_fees'),
                'subtotal' => $request->post('subtotal'),
                'total' => $request->post('total')
              ]);


              //add order items to db
              $itemTotal = 0;
              foreach ($products as $product) {
                $dbProduct = Productsmodel::find($product['id']);
                $itemTotal = $dbProduct->price * $product['quantity'];
                OrderItemsmodel::create([ 
                'product_id' => $dbProduct->id,
                'order_id' => $order->id,
                'quantity' => $product['quantity'],
                'total' => $itemTotal
                ]);
              }




        } catch (\Exception $ex) {
                $error = $this->returnError($ex->getCode(),$ex->getMessage());
                return response()->json($error, 500);
        }

        $rsData = $this->returnData('order', $order,'order is placed successfully');
        return response()->json($rsData, 200);    

      }

      //check that the order exist
      public function reorder(Request $request)
      {
        $user = auth()->user();
        try {
              
              $rules = [
                  "lastOrder_id" => "required|numeric",
                  "payment_method" => "required|string",
                  "delivery_date" => "required|date",
                  "delivery_address" => "required|string",
                  "orderlat" => "required|string",
                  "orderlong" => "required|string",
                  "street_no_name" => "nullable|string",
                  "bulding_no" => "nullable|string",
                  "floor" => "nullable|string",
                  "apartment" => "nullable|string",
                  "sales_tax" => "required",
                  "delivery_fees" => "required",
                  "subtotal" => "required",
                  "total" => "required",
                  "notes" => "nullable|string",               
              ];

              $validator = Validator::make($request->all(), $rules);

              if ($validator->fails()) {
                  $code = $this->returnCodeAccordingToInput($validator);
                  $error = $this->returnValidationError($code, $validator);
                  return response()->json($error, 422);
              }

             

              //calculate the total
              $order = Ordersmodel::create([ 
                'user_id' =>  $user->id,
                'status' => 'pending',
                'step' => 1,
                'payment_method' => $request->post('payment_method'), 
                'delivery_date' => $request->post('delivery_date'),
                'delivery_address' => $request->post('delivery_address'),
                'orderlat' => $request->post('orderlat'),
                'orderlong' => $request->post('orderlong'),                
                'street_no_name' => $request->post('street_no_name'),
                'bulding_no' => $request->post('bulding_no'),
                'floor' => $request->post('floor'),
                'apartment' => $request->post('apartment'),
                'notes' => $request->post('notes'),
                'sales_tax' => $request->post('sales_tax'),
                'delivery_fees' => $request->post('delivery_fees'),
                'subtotal' => $request->post('subtotal'),
                'total' => $request->post('total')
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
          $error = $this->returnError($ex->getCode(),$ex->getMessage());
          return response()->json($error, 500);                  

        }
        $rsData = $this->returnData('order', $order,'order is placed successfully');
        return response()->json($rsData, 200);                  
      }      


      //this function is return te money to the user's wallet only
      //check that the order exist
      public function cancelOrder(Request $request){
        try {
              //validation on the request
              $rules = [
                  "order_id" => "required|numeric",
              ];
              $validator = Validator::make($request->all(), $rules);
              if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                $error = $this->returnValidationError($code, $validator);
                return response()->json($error, 422);
              }


                //place the total in the user wallet
                $order = Ordersmodel::find($request->post('order_id'));
                //****CODE**** (insert total in the user wallet)
             

              //change order status to cancelled
              Ordersmodel::where('id',$request->post('order_id'))->update([
                       'status' => 'cancelled'
              ]);

              //success message
              $succesMsg = $this->returnSuccessMessage('order is cancelled successfully' , 'S002');
              return response()->json($succesMsg, 200);   

            } catch (\Exception $ex) {
                $error = $this->returnError($ex->getCode(),$ex->getMessage());
                return response()->json($error, 500);                  
        }




      }


      public function editorder($orderId)
      {
        $id       = $orderId;
        $user = auth()->user();
        $order = Ordersmodel::with('OrderItemsmodel.Productsmodel')->where([
                ['user_id', '=', $user->id],
                ['id', '=', $id],
        ])->first();


        if(!$order)
        {
          $error = $this->returnError('O404','no order found');
          return response()->json($error, 404);          
        }                   
        if($order->status != "pending" )
        {
          $error = $this->returnError('O406','no edits can be made on this order');
          return response()->json($error, 422);          
        }                        
                
        $rsData = $this->returnData('order', $order);
        return response()->json($rsData, 200);     

      }


      public function updateorder(Request $request)
      {
        $user = auth()->user();
        try {

              //validation on the request
                  //Add validation for products array learn from laravel docs.
              $rules = [
                  "order_id" => "required|numeric",
                  "delivery_date" => "required|date",
                  "delivery_address" => "required|string",
                  "orderlat" => "required|string",
                  "orderlong" => "required|string",
                  "street_no_name" => "nullable|string",
                  "bulding_no" => "nullable|string",
                  "floor" => "nullable|string",
                  "apartment" => "nullable|string",
                  "notes" => "nullable|string",
                  "products" => "required",                
              ];
              $validator = Validator::make($request->all(), $rules);
              if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                $error = $this->returnValidationError($code, $validator);
                return response()->json($error, 422);
              }


              //calculate order total price
              $products = $request->post('products');
              $orderTotal = 0;
              $itemTotal = 0;
              foreach ($products as $product) {
                $dbProduct = Productsmodel::find($product['id']);
                $itemTotal = $dbProduct->price * $product['quantity'];
                $orderTotal = $orderTotal + $itemTotal;
              }


              //check that the order meet the delivery date requirments
              $today_max_price = settings::where('name','today_max_price')
              ->get('decimal_value');
              if(  date('Ymd', strtotime($request->post('delivery_date'))) < date('Ymd') )
              {
                $error = $this->returnError('O001','please enter a valid date');
                return response()->json($error, 422);          
              }   
              if($orderTotal > $today_max_price[0]->decimal_value && date('Ymd', strtotime($request->post('delivery_date'))) == date('Ymd'))
              {
                $error = $this->returnError('O002','order can not be delivered today');
                return response()->json($error, 422);          
              }                   


              //validate that the order is greater than the minimum order price
              $order_min_price = settings::where('name','order_min_price')
              ->get('decimal_value');
              if($orderTotal <= $order_min_price[0]->decimal_value)
              {
                $error = $this->returnError('O003','order do not exceed minimum amount');
                return response()->json($error, 422);          
              }  


              //get the last payment method and total
              $order = Ordersmodel::find($request->post('order_id'));
              if(!$order)
              {
                $error = $this->returnError('O404','no order found to be updated');
                return response()->json($error, 404);          
              }                  
              if($order->status != "pending" )  
              {
                $error = $this->returnError('O406','no edits can be made on this order');
                return response()->json($error, 422);          
              }                    
              $lpayment_method = $order->payment_method;
              $ltotal = $order->total;
              $lstatus = $order->status;


              //insert order in db           
              $order = Ordersmodel::where('id',$request->post('order_id'))->update([ 
                'user_id' => $user->id,
                'status' => $lstatus,
                'step' => 1,
                'payment_method' => $lpayment_method, 
                'delivery_date' => $request->post('delivery_date'),
                'delivery_address' => $request->post('delivery_address'),
                'orderlat' => $request->post('orderlat'),
                'orderlong' => $request->post('orderlong'),  
                'street_no_name' => $request->post('street_no_name'),
                'bulding_no' => $request->post('bulding_no'),
                'floor' => $request->post('floor'),
                'apartment' => $request->post('apartment'),
                'notes' => $request->post('notes'),
                'total' => $orderTotal
              ]);

              //delete all items to be insereted again
              OrderItemsmodel::where('order_id', $request->post('order_id'))->delete();

              //add order items to db
              $itemTotal = 0;
              foreach ($products as $product) {
                $dbProduct = Productsmodel::find($product['id']);
                $itemTotal = $dbProduct->price * $product['quantity'];
                OrderItemsmodel::create([ 
                'product_id' => $dbProduct->id,
                'order_id' => $request->post('order_id'),
                'quantity' => $product['quantity'],
                'total' => $itemTotal
                ]);
              }




        } catch (\Exception $ex) {
          $error = $this->returnError($ex->getCode(),$ex->getMessage());
          return response()->json($error, 500);
        }

        if($orderTotal > $ltotal)
        {
          if($lpayment_method == "cash")
          {
            $msg = "total is large, your order is updated successfully, your payment is cash";
            $code = "oub001";    
          }
          elseif ($lpayment_method == "visa") {
            $msg = "total is large, Please pay using your card, your payment is using card";
            $code = "oub002";     
          }elseif ($lpayment_method == "wallet") {
            //check the user wallet then return success (if sufficient) or failure (if not sufficient) 
            $msg = "total is large, the amount is deducted from your wallet";
            $code = "oub003";     
          }

        }

        if($orderTotal < $ltotal)
        {
          $msg = "total is less, your order is updated successfully, cach is added to your wallet";
          $code = "ous001";
        } 


        if($orderTotal == $ltotal)
        {
          $msg = "total is the same, your order is updated successfully";
          $code = "ous001";
        }       

        $succesMsg = $this->returnSuccessMessage($msg , $code);
        return response()->json($succesMsg, 200);  
      }



      public function editProfile()
      {
        $user = auth()->user();

        $currentorders = Ordersmodel::with('OrderItemsmodel.Productsmodel')->where([
        ['user_id', '=', $user->id],
        ['status', '!=', 'delivered'],
        ])->Where([
          ['user_id', '=', $user->id],
          ['status', '!=', 'cancelled'],])
        ->get();


        $currentordersCnt = count($currentorders);
        $user->currentordersCnt = $currentordersCnt;


        $ordershistory = Ordersmodel::with('OrderItemsmodel.Productsmodel')->where([
                ['user_id', '=', $user->id],
                ['status', '=', 'delivered'],
        ])->orWhere([
          ['user_id', '=', $user->id],
          ['status', '=', 'cancelled'],])
        ->get();

        $ordersHistoryCnt = count($ordershistory);
        $user->ordersHistoryCnt = $ordersHistoryCnt;

        $rsData = $this->returnData('user', $user);
        return response()->json($rsData, 200);         
      }

      public function updateProfile(Request $request)
      {
        $user = auth()->user();
        try {

              //validation on the request
                  //Add validation for products array learn from laravel docs.
              $rules = [
                  "phone" => "required|string",
                  "ctyCode" => "required|string",
                  "name" => "required|string",
                  "email" => "required|email",
                  "dateOfBirth" => "required|date",
                  "gender" => "nullable|string",              
              ];
              $validator = Validator::make($request->all(), $rules);
              if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                $error = $this->returnValidationError($code, $validator);
                return response()->json($error, 422);
              }
        } catch (\Exception $ex) {
          $error = $this->returnError($ex->getCode(),$ex->getMessage());
          return response()->json($error, 500);
        }


        $user->update([
            'name' => $request->post('name'),
            'countryCode' =>$request->post('ctyCode'),
            'phone' => $request->post('phone'),
            'email' =>$request->post('email'),
            'dateofbirth' => $request->post('dateOfBirth'),
            'gender' => $request->post('gender'),      
        ]);

        $succesMsg = $this->returnSuccessMessage('Profile is updated successfully' , 'P001');
        return response()->json($succesMsg, 200);          
      }

      public function message(Request $request)
      {
        try {
              $rules = [
              "name" => "required|string",
              "email" => "required|email",
              "subject" => "required|string",
              "message" => "required|string",
              "phone" => "required|string",                
              ];

              $validator = Validator::make($request->all(), $rules);
              if ($validator->fails()) {
                  $code = $this->returnCodeAccordingToInput($validator);
                  $error = $this->returnValidationError($code, $validator);
                  return response()->json($error, 422);
              }

              $message =  Message::create([
                  'name' => $request->name,
                  'email' => $request->email,
                  'phone' => $request->phone,
                  'subject' => $request->subject,
                  'message' => $request->message,
              ]);

              $receiver_email     = ReceiverEmail::first();

              $data = [
              'name' => $request->name,
              'email' => $request->email,
              'subject' => $request->subject,
              'message' => $request->message,
              'phone' => $request->phone,
              ];

              Mail::to($receiver_email->email)->send(new ContactUs($data));

              

        }catch (\Exception $ex) {
         $error = $this->returnError($ex->getCode(),$ex->getMessage());
         return response()->json($error, 500);
        }

        $succesMsg = $this->returnSuccessMessage('Message is sent successfully' , 'M001');
        return response()->json($succesMsg, 200);  
      }
}
