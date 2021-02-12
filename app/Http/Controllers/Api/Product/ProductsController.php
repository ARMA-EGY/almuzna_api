<?php

namespace App\Http\Controllers\Api\Product;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Productsmodel;
use App\Traits\GeneralTrait;


class ProductsController extends Controller
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
        $products = Productsmodel::select('name_'.app()->getLocale() .' as name', 'price', 'photo')->get();
        //should we return error if emtpy or not
        if($products->isEmpty())
            return $this->returnError('P404','no products found');
        return $this->returnData('products', $products);
      }



}
