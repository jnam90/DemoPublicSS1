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
            return view('register'); 
 
        } 
        $validator = Validator::make($request->all(), [
            'firstname' => 'required',
            'familyname' => 'required',
            'email' => 'required|email',
            ]);
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
            return redirect()->route('verify')
                        ->withErrors($validator) 
                        ->withInput();
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
    
    public function doctorInfo(Request $request) {
        $id = session()->get('id');
        if(!$id){
            return redirect()->route('register');
        }  
        $data = Doctor::checkDoctorExist($id);
        if(!$data) { 
            return redirect()->route('register');
        } 
        $listSpecialty = Doctor::$specialty;
        $listCountry = Doctor::$country;  
        if (!$request->isMethod('post')) { 
            return view('doctor-information', ['listCountry' => $listCountry, 'listSpecialty' => $listSpecialty]); 
 
        }  
        $validator = Validator::make($request->all(), [
            'specialty' => 'required',
            'country' => 'required',
            'clinic_name' => 'required',
            'mobile_number' => 'required'
            
            ]);   
        if ($validator->fails()) { 
            if ($validator->fails()) {
                return redirect()->route('doctorinfo')
                            ->withErrors($validator) 
                            ->withInput();
            }  
        }  
        $arrData = [
            'specialty' => $request->specialty,
            'country' => $request->country,
            'clinic_name' => $request->clinic_name,
            'mobile_number' => $request->mobile_number,

        ];
        $update = $data->update($arrData);
        if($update) { 
            session()->forget('id'); 
            session()->put('doctor_id', $id);  
            return redirect()->route('patientinfo');
        }
        else {
            $validator->errors()->add('error', 'Add info failed!');
            return redirect()->route('verify')
                        ->withErrors($validator)
                        ->withInput();  
        }

    }
    
    public function patientInfo(Request $request) {
        $doctor_id = session()->get('doctor_id');
        if(!$doctor_id){ 
            return redirect()->route('register');  
        }  
       
        if (!$request->isMethod('post')) { 
             $arrAge = Doctor::generateListAge();
            $arrGender = Doctor::$gender;
            $arrDrinker = Doctor::$drinker;
            $arrSmoker = Doctor::$smoker;
            $arrRace = Doctor::$race; 
            return view('patient-information', ['arrAge' => $arrAge, 'arrGender' => $arrGender, 'arrDrinker'=>$arrDrinker,
        'arrSmoker'=>$arrSmoker, 'arrRace' => $arrRace ]);  

        }  
        $validator = Validator::make($request->all(), [
            'firstname' => 'required',
            'familyname' => 'required',
            'age' => 'required',
            'gender' => 'required',
            'smoker' => 'required',
            'race' => 'required',
            'drinker'  => 'required',
            
            ]);   
        if ($validator->fails()) {
            return redirect()->route('patientinfo')
                        ->withErrors($validator) 
                        ->withInput();
        }   
        $arrData = [ 
            'firstname' => $request->firstname,
            'familyname' => $request->familyname,
            'age' => $request->age,
            'gender' => $request->gender,
            'smoker' => $request->smoker,
            'race' => $request->race,
            'drinker' => $request->drinker,

        ]; 
        if(isset($request->consent_form) && $request->consent_form != "") {
            $file = $request->consent_form;
            $path = Common::uploadFile($file);
            $arrData['consent_form']  = $path;  
        } 
        $createId = Patient::create($arrData)->id; 
        if($createId) {  
            session()->put('patientid', $createId);  
            return redirect()->route('beforeandafterphoto');
        } 
        else {
            $validator->errors()->add('error', 'Add info failed!');
            return redirect()->route('patientinfo')
                        ->withErrors($validator)
                        ->withInput(); 
        }  
  
    }
 
    public function beforeandafterphoto(Request $request) {
        $patientid = session()->get('patientid');
        if(!$patientid){ 
            return redirect()->route('register'); 
        }  
        if (!$request->isMethod('post')) { 
            return view('beforeandafter-photo');   
        } 
        $validator = Validator::make($request->all(), [
            'before_left_profile' => 'required',
            'before_frontal' => 'required',
            'before_right_oblique' => 'required',
            'after_left_profile' => 'required',
            'after_frontal' => 'required', 
            'after_right_oblique' => 'required', 

        ]);   
        if ($validator->fails()) {
            return redirect()->route('beforeandafterphoto')
                        ->withErrors($validator) 
                        ->withInput(); 
        }  
        $path_before_left_profile = Common::uploadFile($request->before_left_profile,'images');
        $path_before_frontal = Common::uploadFile($request->before_frontal,'images');
        $path_before_right_oblique = Common::uploadFile($request->before_right_oblique,'images');
        $path_after_left_profile = Common::uploadFile($request->after_left_profile,'images');
        $path_after_frontal = Common::uploadFile($request->after_frontal,'images');
        $path_after_right_oblique = Common::uploadFile($request->after_right_oblique,'images');
        
        $arrData = [
            'before_left_profile' => $path_before_left_profile,
            'before_frontal' => $path_before_frontal,
            'before_right_oblique' => $path_before_right_oblique,
            'after_left_profile' =>  $path_after_left_profile,
            'after_frontal' => $path_after_frontal,
            'after_right_oblique' => $path_after_right_oblique,

        ];
        $createId = BeforeAfterImage::create($arrData)->id;
        if($createId) { 
            session()->put('image_id', $createId);     
            session()->put('patient_id', $patientid);  
            session()->forget('patientid');  
            return redirect()->route('proceduredetail');
        }
        else { 
            $validator->errors()->add('error', 'Upload failed!');
            return redirect()->route('beforeandafterphoto')
                        ->withErrors($validator)
                        ->withInput(); 
        }  

        
    } 

    public function procedureDetail(Request $request) { 
        $image_id =  session()->get('image_id');
        if(!$image_id){ 
            return redirect()->route('register'); 
        }  
        if (!$request->isMethod('post')) { 
            $list_image = BeforeAfterImage::where("id", $image_id)->first(); 
            $location = Submission::$location;
            $treatmentarea = Submission::$treatmentarea;
            $product = Submission::$product;
            return view('procedure-details', ['list_image' => $list_image, 'location' => $location,
        'treatmentarea' => $treatmentarea, 'product' => $product]); 
        } 
        $doctor_id = session()->get('doctor_id');
        $patient_id = session()->get('patient_id');
        $validator = Validator::make($request->all(), [
            'location' => 'required',
            'treatment_area' => 'required',
            'product' => 'required',
            'qty' => 'required',
         
            ]);   
        if ($validator->fails()) {
            return redirect()->route('proceduredetail')
                        ->withErrors($validator) 
                        ->withInput();
        }  
        $treatment_used = [];
        $request_location = $request->location;
        $request_treatment_area = $request->treatment_area;
        $request_product = $request->product;
        $request_qty = $request->qty;
        foreach( $request_location as $key => $value) {
            $arr = ["location" => $request_location[$key],
                    'treatment_area' => $request_treatment_area[$key],
                    'product' => $request_product[$key],
                    'qty' => $request_qty[$key],
            ];
            $treatment_used[] = $arr;

        }
        $treatment_used = $treatment_used;
        $arrData = [ 
            'addition_infomation' => $request->addition_infomation,
            'doctor_id' => $doctor_id,
            'patient_id' => $patient_id,
            'image_id' => $image_id,
            'treatment_used' => $treatment_used,  
            
        ]; 
        
        $createId = Submission::create($arrData)->id;  
        if($createId) {  
            session()->put('submissionId', $createId);  
            session()->forget('doctor_id');
            session()->forget('patient_id'); 
            session()->forget('image_id');
            return redirect()->route('reviewsubmission'); 
        } 
        else {
            $validator->errors()->add('error', 'Add info failed!');
            return redirect()->route('proceduredetail')
                        ->withErrors($validator)
                        ->withInput();   
        }  
        
    }  
 
    public function reviewSubmission(Request $request) {
        $submissionId =  session()->get('submissionId');
        if(!$submissionId){ 
            return redirect()->route('register'); 
        }   
        $detailSubmision = Submission::getDetailSubmission($submissionId);
        return view('review-submission', ['detailSubmision' => $detailSubmision]);  
    } 

    public function beforeandafterPhotocontest(Request $request) {
        return view('beforeandafter-photo-contest');
    }

    
}
  
