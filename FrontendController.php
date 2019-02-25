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

    public function beforeandafterPhotocontest(Request $request) {
        return view('beforeandafter-photo-contest');
    }    
}  
