<?php

namespace App\Http\Controllers;

use App\Mail\ResetPasswordEmail;
use App\Models\Country;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(){
        return view('front.account.login');
    }
    
    public function register(){
        return view('front.account.register');
    }

    public function processRegister(Request $request){
        $validator = Validator::make($request->all(),[
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:5|confirmed'
        ]);
        if($validator->passes()){
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->password = Hash::make($request->password);
            $user->save();
            
            $request->session()->flash('success','You have bee Registerd successfully .');

            return response()->json([
                'status' => true,
                'message' => 'Registration successfully done'
            ]);
            
        }else{
            return response([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function authenticate(Request $request){
        $validator = Validator::make($request->all(),[
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if($validator->passes()){
            // OLD Code 07-03-2024 By Rupesh
            if(Auth::attempt(['email' => $request->email, 'password' => $request->password],$request->get('remember'))){
            //if(Auth::attempt(['email' => $request->email, 'password' => $request->password, "status" => '1'],$request->get('remember'))){
                $user = Auth::user();
                //dd($user);
                if($user->status != 1){
                    Auth::logout();
                    //$message = "Your Account is Inactive Please Contact <a href='/page/contact-us'> Admin</a>.";
                    $message = "Your Account is Inactive Please Contact <a href='".route('front.page','contact-us')."'> Admin</a>.";
                    return redirect()->route('account.login')->withInput($request->only('email'))->with('error',$message);    
                }
                // if(session()->has('url.intended')){
                //     return redirect(session()->get('url.intended'));
                // }
                
                return redirect()->route('account.profile');

            }else{
                //session()->flash('error','Either email/password is incorrect.');
                return redirect()->route('account.login')->withInput($request->only('email'))->with('error','Either email/password is incorrect.');
            }
        }else{
            return redirect()->route('account.login')->withErrors($validator)->withInput($request->only('email'));
        }
    }

    public function profile(){
        $userId = Auth::user()->id;
        
        $user = User::where('id',$userId)->first();
        $countries = Country::orderBy('name','ASC')->get();
        $address = CustomerAddress::where('user_id',$userId)->first();
        
        $data['user'] = $user;
        $data['countries'] = $countries;
        $data['address'] = $address;
        
        return view('front.account.profile',$data);
    }

    public function profileUpdate(Request $request){
        $userId = Auth::user()->id;
        $validator = Validator::make($request->all(),[
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$userId.',id',
            'phone' => 'required'
        ]);
        if($validator->passes()){
            
            $user = User::find($userId);
            
            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->save();

            session()->flash('success','Profile Updated Successfully');

            return response()->json([
                'status' => true,
                'message' => 'Profile Updated Successfully'
            ]);

        }else{
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function addressUpdate(Request $request){
        $userId = Auth::user()->id;

        $validator = Validator::make($request->all(),[
            'first_name' => 'required|min:3',
            'last_name' => 'required',
            'email' => 'required|email',
            'country_id' => 'required',
            'address' => 'required|min:10',
            'city' => 'required',
            'state' => 'required',
            'zip' => 'required',
            'mobile' => 'required'
        ]);

        if($validator->passes()){
                        
            CustomerAddress::updateOrCreate(
                ['user_id' => $userId],
                [
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'mobile' => $request->mobile,
                    'country_id' => $request->country_id,
                    'address' => $request->address,
                    'apartment' => $request->apartment,
                    'city' => $request->city,
                    'state' => $request->state,
                    'zip' => $request->zip
                ]
            );
            
            session()->flash('success','Address Updated Successfully');

            return response()->json([
                'status' => true,
                'message' => 'Address Updated Successfully'
            ]);

        }else{
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function logout(){
        Auth::logout();
        return redirect()->route('account.login')->with('success','You successfully logged out.');
    }

    public function orders(){
        $user = Auth::user();
        $orders = Order::where('user_id',$user->id)->orderBy('created_at','DESC')->get();
        $data['orders'] = $orders;
        return view('front.account.order',$data);
    }

    public function orderDetail($id){
        $user = Auth::user();
        $order = Order::where('user_id',$user->id)->where('id',$id)->first();
        $data['order'] = $order;
        $orderItems = OrderItem::where('order_id',$id)->get();
        $data['orderItems'] = $orderItems;
        $orderItemsCount = OrderItem::where('order_id',$id)->count();
        $data['orderItemsCount'] = $orderItemsCount;
        return view('front.account.order-detail',$data);
    }

    public function orderCancel($id){
        //dd($id);
        $order = Order::find($id);
        if(empty($order)){
            session()->flash('error','Order not found');
            return response()->json([
                'status' => true,
                'message' => 'Order not found'
            ]);
        }
        $order->status='cancelled';
        $order->save();

        cancelOrderEmail($id,"customer");
        cancelOrderEmail($id,"admin");

        session()->flash('success','Order Cancelled Successfully');

        return response()->json([
            'status' => true,
            'message' => 'Order Cancelled Successfully'
        ]);
    }

    public function wishlist(){
        
        $wishlists = Wishlist::where('user_id',Auth::user()->id)->with('product')->get();

        $data['wishlists'] = $wishlists;

        return view('front.account.wishlist',$data);
    }

    public function removeProductFromWishList(Request $request){
        
        $wishlist = Wishlist::where('user_id',Auth::user()->id)->where('product_id',$request->id)->first();
        
        if($wishlist == null){
            session()->flash('error','Product already removed.');
            return response()->json([
                'status' => true,
                'message' => 'Product already removed.'
            ]);
        }else{
            Wishlist::where('user_id',Auth::user()->id)->with('product')->delete();
            session()->flash('success','Product removed successfully .');
            return response()->json([
                'status' => true,
                'message' => 'Product removed successfully .'
            ]);
        }
    }

    public function showChangePassword(){
        return view('front.account.change-password');
    }

    public function changePassword(Request $request){
        $validator = Validator::make($request->all(),[
            'old_password' => 'required',
            'new_password' => 'required|min:5',
            'confirm_password' => 'required|same:new_password',
        ]);

        if($validator->passes()){

            $user = User::select('id','password')->where('id',Auth::user()->id)->first();
            if(!Hash::check($request->old_password,$user->password)){
                session()->flash('error','Your old password is incorrect, Please try again');
                return response()->json([
                    'status' => true,
                    'message' => 'Your old password is incorrect, Please try again'
                ]);
            }

            User::where('id',$user->id)->update([
                'password' => Hash::make($request->new_password)
            ]);

            session()->flash('success','You have successfully changed your password');

            return response()->json([
                'status' => true,
                'message' => 'You have successfully changed your password'
            ]);

        }else{
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }

    }

    public function forgotPassword(){
        return view('front.account.forgot-password');
    }

    public function processForgotPassword(Request $request){
        $validator = Validator::make($request->all(),[
            'email' => 'required|email|exists:users,email',
        ]);

        if($validator->fails()){
            return redirect()->route('front.forgotPassword')->withInput()->withErrors($validator);
        }

        $token = Str::random(60);

        \DB::table('password_resets')->where('email',$request->email)->delete();

        \DB::table('password_resets')->insert([
            'email' => $request->email,
            'token' => $token,
            'created_at' => now()
        ]);

        //send mail here
        $user = User::where('email',$request->email)->first();
        $formData = [
            'token' => $token,
            'user' => $user,
            'mailSubject' => 'You have Requested to reset your password'
        ];

        Mail::to($request->email)->send(new ResetPasswordEmail($formData));

        //session()->flash('success','Password ')

        return redirect()->route('front.forgotPassword')->with('success','Please Check your inbox to reset your password');

    }

    public function resetPassword($token){
        
        $tokenExist = \DB::table('password_resets')->where('token',$token)->first();

        if($tokenExist == null){
            return redirect()->route('front.forgotPassword')->with('error','Invalid Request');
        }

        return view('front.account.reset-password',[
            'token' => $token
        ]);
    }

    public function processResetPassword(Request $request){
        
        $token = $request->token;

        $tokenObj = \DB::table('password_resets')->where('token',$token)->first();

        if($tokenObj == null){
            return redirect()->route('front.forgotPassword')->with('error','Invalid Request');
        }

        $user = User::where('email',$tokenObj->email)->first();

        $validator = Validator::make($request->all(),[
            'new_password' => 'required|min:5',
            'confirm_password' => 'required|same:new_password',
        ]);

        if($validator->fails()){
            return redirect()->route('front.resetPassword',$token)->withInput()->withErrors($validator);
        }

        User::where('id',$user->id)->update([
            'password' => Hash::make($request->new_password)
        ]);
        \DB::table('password_resets')->where('email',$user->email)->delete();
        return redirect()->route('account.login')->with('success','You have successfully updated your password');

    }

}
