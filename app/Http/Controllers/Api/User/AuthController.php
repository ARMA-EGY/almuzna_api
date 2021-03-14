<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\customers;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Validator;
use Auth;
use App\Ordersmodel;

class AuthController extends Controller
{

    use GeneralTrait;

    public function login(Request $request)
    {

  
            $rules = [
                "countryCode" => "required",
                "phone" => "required",
                "password" => "required"
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                  $code = $this->returnCodeAccordingToInput($validator);
                  $error = $this->returnValidationError($code, $validator);
                  return response()->json($error, 422);
            }

            //login
            $credentials = $request->only(['phone', 'password']);

            $token = Auth::guard('customers-api')->attempt($credentials);

            if (!$token)
            {
                $error = $this->returnError('E001','The login information is incorrect');
                return response()->json($error, 404);  
            }

            $admin = Auth::guard('customers-api')->user();
            $admin->api_token = $token;

            $currentorders = Ordersmodel::with('OrderItemsmodel.Productsmodel')->where([
                    ['user_id', '=', $admin->id],
                    ['status', '!=', 'delivered'],
            ])->Where([
              ['user_id', '=', $admin->id],
              ['status', '!=', 'cancelled'],])
            ->get();


            $currentordersCnt = count($currentorders);
            $admin->currentordersCnt = $currentordersCnt;


            $ordershistory = Ordersmodel::with('OrderItemsmodel.Productsmodel')->where([
                    ['user_id', '=', $admin->id],
                    ['status', '=', 'delivered'],
            ])->orWhere([
              ['user_id', '=', $admin->id],
              ['status', '=', 'cancelled'],])
            ->get();

            $ordersHistoryCnt = count($ordershistory);
            $admin->ordersHistoryCnt = $ordersHistoryCnt;          
            //return token
            $rsData = $this->returnData('user', $admin);
            return response()->json($rsData, 200); 

       


    }

    public function logout(Request $request)
    {
        $token = $request -> header('auth-token');
        if($token){
            try {

                JWTAuth::setToken($token)->invalidate(); 

            }catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e){ 
                $error = $this -> returnError('','some thing went wrong');
                return response()->json($error, 500);
            }

            $succesMsg = $this->returnSuccessMessage('Logged out successfully');
            return response()->json($succesMsg, 200);  
        }else{

                $error = $this -> returnError('','some thing went wrongs');
                return response()->json($error, 404);  
            
        }

    }
}
