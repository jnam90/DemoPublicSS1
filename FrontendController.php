<?php

namespace App\Http\Controllers;
use Validator;
use Illuminate\Http\Request;
use App\Doctor;
use App\Patient;
use App\BeforeAfterImage;
use App\Submission;
use App\Common;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationCode;

class FrontendController extends Controller
{

    public function register(Request $request)
    {
        if ($request->isMethod('post')) {
            $validator = Validator::make($request->all(), ['firstname' => 'required', 'familyname' => 'required', 'email' => 'required|email',]);
            if ($validator->fails()) {
                    return redirect()->route('register')
                                ->withErrors($validator)
                                ->withInput();
            }

            /*$checkEmail = Doctor::checkEmailExist($request->email); 
            if($checkEmail) { 
                $validator->errors()->add('error', 'Email registed!');
                return redirect()->route('register')
                            ->withErrors($validator)
                            ->withInput(); 
            }

            $verification_code = Doctor::generateCode();
            $data = [
                'firstname' => $request->firstname,
                'familyname' => $request->familyname,
                'email' => $request->email,
                'status' => 0,
                'verification_code' => $verification_code

            ];
            $create = Doctor::create($data); 
            if($create) {
                Mail::to($request->email)->send(new VerificationCode(['code' => $verification_code ])); 
                session()->put('email', $request->email);   
                return redirect()->route('verify');  
                     
            } 
            else {
                $validator->errors()->add('error', 'Register failed!');
                return redirect()->route('register')
                            ->withErrors($validator)
                            ->withInput();
            }*/
 
        } 
        return view('register'); 
    }
    
    public function beforeandafterPhotocontest(Request $request) {
        return view('beforeandafter-photo-contest');
    }    
}  
