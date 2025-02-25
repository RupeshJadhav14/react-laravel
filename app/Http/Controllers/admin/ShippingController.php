<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\ShippingCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShippingController extends Controller
{
    public function create(){
        $countries = Country::get();
        $data['countries'] = $countries;
        $shippingCharges = ShippingCharge::select('shipping_charges.*','countries.name')->leftJoin('countries','countries.id','shipping_charges.country_id')->get();
        $data['shippingCharges'] = $shippingCharges;
        return view('admin.shipping.create',$data);
    }

    public function store(Request $request){
        
        $validator = Validator::make($request->all(),[
            'country' => 'required',
            'amount' => 'required|numeric'
        ]);
        if($validator->passes()){

            $count = ShippingCharge::where('country_id',$request->country)->count();
            if($count > 0){
                session()->flash('error','Shipping already added.!');
                return response([
                    'status' => true,
                    'message' => 'Shipping already added.!'
                ]);
            }

            $shipping = new ShippingCharge();
            $shipping->country_id = $request->country;
            $shipping->amount = $request->amount;
            $shipping->save();

            session()->flash('success','Shipping added successfully');

            return response()->json([
                'status' => true,
                'message' => 'Shipping added successfully'
            ]);

        }else{
            return response([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function edit($id){
        $shippingCharge = ShippingCharge::find($id);
        $data['shippingCharge'] = $shippingCharge;
        $countries = Country::get();
        $data['countries'] = $countries;
        return view('admin.shipping.edit',$data);
    }

    public function update($id,Request $request){

        $shipping = ShippingCharge::find($id);

        $validator = Validator::make($request->all(),[
            'country' => 'required',
            'amount' => 'required|numeric'
        ]);
        if($validator->passes()){

            if($shipping == null){
                session()->flash('error','Shipping not found');
                return response([
                    'status' => true,
                    'message' => 'Shipping not found'
                ]);
            }

            
            $shipping->country_id = $request->country;
            $shipping->amount = $request->amount;
            $shipping->save();

            session()->flash('success','Shipping updated successfully');

            return response()->json([
                'status' => true,
                'message' => 'Shipping updated successfully'
            ]);

        }else{
            return response([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function destroy($id){
        
        $shippingCharge = ShippingCharge::find($id);

        if($shippingCharge == null){
            session()->flash('error','Shipping not found');
            return response([
                'status' => true,
                'message' => 'Shipping not found'
            ]);
        }

        $shippingCharge->delete();
        
        session()->flash('success','Shipping deleted successfully');

        return response([
            'status' => true,
            'message' => 'Shipping deleted successfully'
        ]);
    }
}
