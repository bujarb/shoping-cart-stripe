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
use App\Category;
use DB;

class ProductController extends Controller
{
    public function getIndex(){
    	$products = Product::all();
        $categories = Category::all();
    	return view('shop.index',['products'=>$products,'categories'=>$categories]);
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
        if(!Session::has('cart')){
            return redirect()->route('product.shoping-cart');
        }

        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);

        Stripe::setApiKey('sk_test_PZ8Z7UsknXiqZUFRzgidicgS');
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


    public function getAllByCategory($id){
        $books = Product::where('category_id',$id)->get();

        return view('shop.category',['books'=>$books]);
    }

    public function getAddProduct(){
        $categories = Category::all();
        return view('shop.addproduct',['categories'=>$categories]);
    }

    public function postAddProduct(Request $request){
        $this->validate($request,[
            'name' => 'required',
            'price' => 'required'
        ]);

        $product = new Product();
        $product->name = $request->input('name');
        $product->price = $request->input('price');
        $product->user_id = Auth::user()->id;
        $product->category_id = $request->input('category');

        $product->save();

        return redirect()->route('product.index');
    }
}
