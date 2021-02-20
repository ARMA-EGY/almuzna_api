<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/



Route::group(['middleware' => ['api','CheckPassword','ChangeLanguage'], 'namespace' => 'Api'], function () {

	//--- Product Endpoints ---
    Route::group(['prefix' => 'product','namespace'=>'Product'],function (){
    	Route::get('/', 'ProductsController@index');
    });

    //--- User Endpoints ---
    Route::group(['prefix' => 'user','namespace'=>'User'],function (){
    	//Route::post('login', 'AuthController@login');
        Route::get('currentorders', 'UsersController@currentorders');
        Route::get('ordershistory', 'UsersController@ordershistory');
        Route::get('order', 'UsersController@order');
        Route::post('order', 'UsersController@orderplace');
        Route::post('reorder', 'UsersController@reorder');
        Route::post('cancelorder', 'UsersController@cancelOrder');
       
 
    });

    //--- Driver Endpoints ---
    Route::group(['prefix' => 'driver','namespace'=>'Driver'],function (){
    	//Route::post('login', 'AuthController@login');
        Route::post('startorder', 'DriverController@startOrder');
        //Route::get('finishorder', 'DriverController@finishOrder');
        //Route::get('recievedcash', 'DriverController@recievedCash');
        Route::get('assignedorders', 'DriverController@assignedOrders');
        Route::get('ordershistory', 'DriverController@ordersHistory');
        //Route::get('ongoingorder', 'DriverController@ongoingOrder');
        Route::get('order', 'DriverController@order');
    });

    //--- Offers Endpoints ---
    Route::group(['prefix' => 'offers','namespace'=>'Offers'],function (){
    
    });

	//--- Pages Endpoints ---
    Route::group(['prefix' => 'pages','namespace'=>'Pages'],function (){
    
    });


    //--- Settings Endpoints ---
    Route::group(['prefix' => 'settings','namespace'=>'Settings'],function (){
        Route::get('order_min_price', 'SettingsController@order_min_price');
        Route::get('today_max_price', 'SettingsController@today_max_price');
        Route::get('sales_tax', 'SettingsController@sales_tax');
    });    

});

