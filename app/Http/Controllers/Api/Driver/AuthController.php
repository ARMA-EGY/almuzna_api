<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\drivers;
use App\customers;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Validator;
use Auth;

class AuthController extends Controller
{

    use GeneralTrait;

    public function login(Request $request)
    {
$dv = drivers::all();
//dd($dv);
  
            $rules = [
                "email" => "required",
                "password" => "required"
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
              $code = $this->returnCodeAccordingToInput($validator);
              $error = $this->returnValidationError($code, $validator);
              return response()->json($error, 422);
            }

            //login

            $credentials = $request->only(['email', 'password']);
//dd($credentials);
           // $token = Auth::guard('drivers-api')->attempt($credentials);
            $token = Auth::guard('drivers-api')->attempt($credentials);
            if (!$token)
            {
                $error = $this->returnError('E001','The login information is incorrect');
                return response()->json($error, 404);  
            }
            //$admin = Auth::guard('drivers-api')->user();
            $admin = Auth::guard('drivers-api')->user();

            $admin->api_token = $token;
            //return token
            $rsData = $this->returnData('driver', $admin);
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
