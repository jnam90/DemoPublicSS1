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
        if (!$request->isMethod('post')) {
             return view('register')
        } 
        $validator = Validator::make($request->all(), ['firstname' => 'required', 'familyname' => 'required', 'email' => 'required|email',]);
        if ($validator->fails()) {
                return redirect()->route('register')
                            ->withErrors($validator)
                            ->withInput();
        }

        $checkEmail = Doctor::checkEmailExist($request->email); 
        if($checkEmail) { 
            $validator->errors()->add('error', 'Email registed!');
            return redirect()->route('register')
                        ->withErrors($validator)
                        ->withInput(); 
        }

        $verification_code = Doctor::generateCode();
        $data = ['firstname' => $request->firstname,'familyname' => $request->familyname,'email' => $request->email,       'status' => 0,           'verification_code' => $verification_code ];
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
        }
    }
    
    public function verify(Request $request) {
        $email = session()->get('email');
        if(!$email) {
            return redirect()->route('register');   
        }  
        if (!$request->isMethod('post')) { 
             return view('verify');   
        }        
        
        $validator = Validator::make($request->all(), [
                'verification_code' => 'required',
                ]);   
            if ($validator->fails()) {
                if ($validator->fails()) {
                    return redirect()->route('verify')
                                ->withErrors($validator) 
                                ->withInput();
                }  
            }  
            $verification_code = $request->verification_code;
            $data = Doctor::checkVerificationCode(['email' => $email, 'code' => $verification_code]);
            if($data) { 
                session()->forget('email');
                session()->put('id', $data->id); 
                $data->update(['status' => 1]);  
                return redirect()->route('doctorinfo');  
            } 
            else {  
                $validator->errors()->add('error', 'Verification code invalid!');
                return redirect()->route('verify')
                            ->withErrors($validator)
                            ->withInput(); 
            }  
    } 
    
    public function beforeandafterPhotocontest(Request $request) {
        return view('beforeandafter-photo-contest');
    }    
}  
