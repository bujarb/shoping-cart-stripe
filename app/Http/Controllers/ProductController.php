<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use Session;
use App\Cart;
use Auth;
use Stripe\Stripe;
use Stripe\Charge;
use App\Order;
use DB;

class ProductController extends Controller
{
    public function getIndex(){
    	$products = Product::paginate(12);
    	return view('shop.index',['products'=>$products]);
    }

    public function getAddToCart(Request $request,$id){
    	$product = Product::find($id);
    	$oldCart = Session::has('cart') ? Session::get('cart') : null;
    	$cart = new Cart($oldCart);
    	$cart->add($product,$product->id);

    	$request->session()->put('cart',$cart);
    	return redirect()->route('product.index');
    }

    public function getCart(){
        if(!Session::has('cart')){
            return view('shop.shoping-cart');
        }

        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);
        return view('shop.shoping-cart',['products'=>$cart->items,'totalPrice'=>$cart->totalPrice]);
    }

    public function getCheckout(){
        if(!Session::has('cart')){
            return view('shop.shoping-cart');
        }

        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);
        $total = $cart->totalPrice;
        return view('shop.checkout',['total'=>$total]);
    }

    public function postCheckout(Request $request){

        //dd($request);
        //dd($request->input('stripeToken'));

        if(!Session::has('cart')){
            return redirect()->route('product.shoping-cart');
        }

        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);

        \Stripe\Stripe::setApiKey("sk_test_hr7lM15dC9jkvungcKhjbuLn");
        try{
            $charge = Charge::create(array(
              "amount" => $cart->totalPrice * 100,
              "currency" => "usd",
              "source" => $request->input('stripeToken'), // obtained with Stripe.js
              "description" => "Test Charge"
            ));

            $order = new Order();
            $order->cart = serialize($cart);
            $order->address = $request->input('address');
            $order->name = $request->input('name');
            $order->payment_id = $charge->id;

            Auth::user()->orders()->save($order);
        }catch(\Exception $e){
            return redirect()->route('checkout')->with('error',$e->getMessage());
        }

        Session::forget('cart');
        return redirect()->route('product.index')->with('success','Successfuly purchased products');
    }

    public function getSingle($id){
        $product = Product::find($id);

        return view('shop.single')->with('product',$product);
    }
}
