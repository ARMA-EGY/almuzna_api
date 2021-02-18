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
        

    });

    //--- Driver Endpoints ---
    Route::group(['prefix' => 'driver','namespace'=>'Driver'],function (){
    	//Route::post('login', 'AuthController@login');
    });

    //--- Offers Endpoints ---
    Route::group(['prefix' => 'offers','namespace'=>'Offers'],function (){
    
    });

	//--- Pages Endpoints ---
    Route::group(['prefix' => 'pages','namespace'=>'Pages'],function (){
    
    });

});

