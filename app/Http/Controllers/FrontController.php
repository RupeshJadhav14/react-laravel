<?php

namespace App\Http\Controllers;

use App\Mail\ContactEmail;
use App\Models\Contact;
use App\Models\Page;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class FrontController extends Controller
{
    public function index(){
        $featuredProducts = Product::where('is_featured','Yes')->orderBy('id','DESC')->where('status',1)->take(8)->get();
        $data['featuredProducts'] = $featuredProducts;
        $latestProducts = Product::orderBy('id','DESC')->where('status',1)->take(8)->get();
        $data['latestProducts'] = $latestProducts;
        return view('front.home',$data);
    }

    public function addToWishList(Request $request){
        if(Auth::check() == false){
            
            //session()->flash('success','Category added successfully');
            session(['url.intended' => url()->previous()]);

            return response()->json([
                'status' => false,
                'message' => 'User Not Looged in'
            ]);
        }

        $product = Product::where('id',$request->id)->first();

        if($product == null){
            return response()->json([
                'status' => false,
                'message' => '<div class="alert alert-danger">Product not found.</div>'
            ]);
        }

        Wishlist::updateOrCreate(
            [
                'user_id' => Auth::user()->id,
                'product_id' => $request->id
            ],
            [
                'user_id' => Auth::user()->id,
                'product_id' => $request->id
            ]
        );

        // $wishlist = new Wishlist;
        // $wishlist->user_id = Auth::user()->id;
        // $wishlist->product_id = $request->id;
        // $wishlist->save();

        return response()->json([
            'status' => true,
            'message' => '<div class="alert alert-success"><strong>"'. $product->title .'"</strong> added in WishList successfully</div>'
        ]);
    }


    public function page($slug){
        $page = Page::where('slug',$slug)->first();
        if($page == null){
            abort(404);
        }
        //dd($page);
        $data['page'] = $page;
        return view('front.page',$data);
    }

    public function sendContactEmail(Request $request){
        $validator = Validator::make($request->all(),[
            'name' => 'required',
            'email' => 'required|email',
            'subject' => 'required|min:7',
        ]);

        if($validator->passes()){

            //send email here
            $mailData = [
                'name' => $request->name,
                'email' => $request->email,
                'subject' => $request->subject,
                'message' => $request->message,
                'mail_subject' => 'You Have Received a Contact E-mail'
            ];
            
            $contact = new Contact();
            
            $contact->name = $request->name;
            $contact->email = $request->email;
            $contact->subject = $request->subject;
            $contact->message = $request->message;
            $contact->save();

            $admin = User::where('id',1)->first();

            Mail::to($admin->email)->send(new ContactEmail($mailData));

            session()->flash('success','Thanks for contacting us, we will get back to you soon.');

            return response()->json([
                'status' => true,
                'message' => 'Thanks for contacting us, we will get back to you soon.'
            ]);

        }else{
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

}
