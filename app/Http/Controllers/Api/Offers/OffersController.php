<?php

namespace App\Http\Controllers\Api\Offers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Productsmodel;
use App\Traits\GeneralTrait;


class OffersController extends Controller
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


      public function index()
      {
        //is it better to return the name based on the lang sent in the request or return both as negm
        $products = Productsmodel::where('type','offer')->get();
        //should we return error if emtpy or not
        if($products->isEmpty())
            return $this->returnError('O404','no offers found');
        return $this->returnData('products', $products);
      }



}
