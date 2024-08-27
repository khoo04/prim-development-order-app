<?php

namespace App\Http\Controllers\MobileAPI;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;
use App\Models\DonationStreak;

use App\Http\Controllers\PointController;
use Exception;
use Illuminate\Support\Carbon;

class DermaController extends Controller
{
    public function validateToken(Request $request)
    {  
        $token = $request->token;

        $user = DB::table('user_token')->where('application_id',1)->where('remember_token',$token)->exists();
        //$user = DB::table('users')->where('remember_token',$token)->exists();

        if($user){
            return response()->json(['result' => 'Validated'], 200);
        }
        else{
            return response()->json(['result' => 'Unauthorized'], 401);
        }
    }

    public function getUserByToken($token){
        $user_token =  DB::table('user_token')->where('application_id',1)->where('remember_token',$token)->select('user_id')->first();
        if($user_token ==null)
            return null;

        $user = User::where('id',$user_token->user_id)->first();
        return $user;

    }

    public function updateToken($user_id,$token){
        $update =  DB::table('user_token')
        ->where('application_id',1)
        ->where('user_id',$user_id)
        ->update([
            'remember_token' => $token,
            'updated_at' => Carbon::now(),
            'expired_at' =>Carbon::now()->addDays(7),
        ]);

        if($update)
        {
            return;
        }

        $exist = DB::table('user_token')
        ->where('application_id',1)
        ->where('user_id',$user_id)
       ->exists();

       if($exist)
       {
        return;
       }
          
       DB::table('user_token')->insert([
        'application_id'=>1,
        'user_id'=>$user_id,
        'remember_token'=> $token,
        'updated_at' => Carbon::now(),
        'expired_at' =>Carbon::now()->addDays(7),
       ]);
    }

    public function login(Request $request)
    {  
        Auth::logout();
       $credentials = $request->only('email', 'password');
       $phone = $request->get('email');
       //return response()->json(['user',$credentials],200);
       if(is_numeric($request->get('email'))){
           $user = User::where('icno', $phone)->first();
          
           if ($user) {
               //dd($user);
               //return ['icno' => $phone, 'password' => $request->get('password')];
               $credentials = ['icno'=>$phone, 'password' => $request->get('password')];
           }
           else{
               if(!$this->startsWith((string)$request->get('email'),"+60") && !$this->startsWith((string)$request->get('email'),"60")){
                   if(strlen((string)$request->get('email')) == 10)
                   {
                       $phone = str_pad($request->get('email'), 12, "+60", STR_PAD_LEFT);
                   } 
                   elseif(strlen((string)$request->get('email')) == 11)
                   {
                       $phone = str_pad($request->get('email'), 13, "+60", STR_PAD_LEFT);
                   }   
               } else if($this->startsWith((string)$request->get('email'),"60")){
                   if(strlen((string)$request->get('email')) == 11)
                   {
                       $phone = str_pad($request->get('email'), 12, "+", STR_PAD_LEFT);
                   } 
                   elseif(strlen((string)$request->get('email')) == 12)
                   {
                       $phone = str_pad($request->get('email'), 13, "+", STR_PAD_LEFT);
                   }   
               }
               $credentials = ['telno'=>$phone,'password'=>$request->get('password')];
           }
       }
       else if(strpos($request->get('email'), "@") !== false){
           $credentials = ['email'=>$phone,'password'=>$request->get('password')];
       }
       else{
           $credentials =['telno' => $phone, 'password'=>$request->get('password')];

       }


       if (Auth::attempt($credentials)) {
           $user = Auth::User();
           $randomString = Str::random(25);
           $newToken =  Str::random(10) .$user->id . $randomString;
            $this->updateToken($user->id ,$newToken);
           // Update the user's device_token with the new token
           
            //dd($user);
           return response()->json([
               'token' => $newToken,
               'name' => $user->name,
               //'referral_code'=>$user->device_token
           ], 200);
       }
       return response()->json(['error' => 'Unauthorized'], 401);
        
    }

    public function getDerma(){
        $dermas = DB::table('donations as d')
        ->join('donation_type as dt', 'dt.id', '=', 'd.donation_type')
        ->select('dt.nama as donation_type_name', 'd.id as donation_id', 'd.nama as donation_name', 'd.url')
        ->where('d.status', 1)
        ->get();

    // Organize the results into a structure where donations are grouped by their type
        $groupedDonations = $dermas->groupBy('donation_type_name')->map(function ($group) {
            return [
                'donation_type' => $group->first()->donation_type_name,
                'donations' => $group->map(function ($item) {
                    return [
                        'donation_id' => $item->donation_id,
                        'donation_name' => $item->donation_name,
                        'donation_url' => $item->url
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        $relogin = true;
        $token = request()->header('token');
        if(isset($token)){
           
            $relogin = $this->getUserByToken($token)==null;

        }
        return response()->json(['data'=>$groupedDonations,'relogin'=>$relogin]);
        //dd($groupedDonations);
    }

    public function returnDermaView(Request $request){
        try{
            $token = $request->token;
            $donation_id = $request->donation_id;
            $tanpaNama = $request->desc == "Derma Tanpa Nama";

            $user =$this->getUserByToken($token);
            if (!$user) {
                throw new Exception('User not found');
            }
            Auth::logout();
            Auth::loginUsingId($user->id);
        // dd(Auth::id());
            $donation = DB::table('donations')->where('id',$donation_id)->first();
        // dd($request->donation_id,$request->token,$user,$donation,$donation_id);

            if($donation->status == 0)
            {
                return view('errors.404');
            }
            //$referral_code = request()->input('referral_code');

            $referral_code = "";
            $message = "";
          
            if($tanpaNama){
                return view('paydonate.anonymous.index', compact('donation','referral_code','message'));

            }else{
                $specialSrabRequest=0;
                if($donation->id==161){
                    $specialSrabRequest=1;
                }
                return view('paydonate.pay', compact('donation', 'user','specialSrabRequest','referral_code','message'));

            }

            }catch(Exception $e){
                return view('errors.404');
            }
        

    }

    public function getDermaInfo(Request $request){
        try{
            $token = $request->token;
            //dd('here');
            $user =$this->getUserByToken($token);
            if (!$user) {
                throw new Exception('User not found');
            }

            $data = DonationStreak::getStreakData($user->id);
           // dd($jsondata);
           // $data = json_decode($jsondata);
            $data['prim_medal_days'] = 40;
            $data['sedekah_subuh_days'] = 40;

            $controller = new PointController();
           // dd('here');
            $codeOfUser = $controller->getReferralCode(false,$user->id);

            $code = $codeOfUser ==null?null:$codeOfUser->code;

            

            return response()->json(['data'=>$data, 'code'=>$code]);
        }catch(Exception $e){
           //dd($e);
            return response()->json(['error' => 'Unauthorized'], 401);
        }
       
    }
}