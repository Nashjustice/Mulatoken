<?php

namespace App\Http\Controllers;
use Auth;
use Mail;
use App\Forex;
use App\User;
use App\Withdraw;
use App\InvPackages;
use App\Investments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\mobilepayments;
use App\Mail\ForexSub;
use App\Blogs;
use App\Mail\Withdrawal;
use Hackdelta\Mpesa\Mpesa;
use Hackdelta\Mpesa\Main\MpesaConfig;
use Hackdelta\Mpesa\Extras\MpesaConstants;
use Hackdelta\Mpesa\Exceptions\MpesaInternalException;
use Hackdelta\Mpesa\Exceptions\MpesaClientExceptions;
use Hackdelta\Mpesa\Exceptions\MpesaServerException;
use Illuminate\Support\Facades\Session;
use App\Wallet;
use App\Charge;
use App\MpesaTransaction;
use Illuminate\Support\Str;

class AffiliatesController extends Controller
{
    
    //ban user
    
    public function deleteuser(Request $request){
        $id=$request->id;
        $res=User::find($id)->delete();
        
        if($res){
            return redirect()->back()->with('success', 'User deleted!');
        }else{
            return redirect()->back()->with('error', 'User not deleted!');
        }
    }
    
    public function sendsms(){
        return view('user.sms');
    }
    
    public function sendsmspost(Request $request){
        $usertype=$request->type;
        $message=$request->message;
        $error_msg=[];
        
        $client = new \GuzzleHttp\Client();
        
        
        
        // $response = Http::get("https://sms.lifegeegs.com/sms/api?action=send-sms&api_key=RUxTSEFEQUk6TmFzaGJpbGwwNzAz&to=254704800563&from=23107&sms=hello");
        
        
        
        
        if($usertype=='active'){
            $members=User::where('status',2)
            ->where('country','KENYA')
            ->get('phone');
 
            foreach($members as $member){
                    $tell=$member->phone;
                    try{
                        $response = $client->get("https://sms.lifegeegs.com/sms/api?action=send-sms&api_key=RUxTSEFEQUk6TmFzaGJpbGwwNzAz&to=$tell&from=23107&sms=$message");
                    }catch (GuzzleHttp\Exception\ClientException $e) {
                        $response = $e->getResponse();
                        $responseBodyAsString = $response->getBody()->getContents();
                    }
            }
            
            if ($response->code=='ok') {
                return redirect()->back()->with('success', 'Message sent');
             
            }else{
                 return redirect()->back()->with('error', "Message not sent");
            }
           
        }elseif($usertype=='inactive'){
            //get the inactive members
            
            $members=User::where('status',1)
            ->where('country','KENYA')
            ->get('phone');
            
            foreach($members as $member){
                
                    $tell=$member->phone;
                    
                    try{
                        $response = $client->get("https://sms.lifegeegs.com/sms/api?action=send-sms&api_key=RUxTSEFEQUk6TmFzaGJpbGwwNzAz&to=$tell&from=23107&sms=$message");
                    }catch (GuzzleHttp\Exception\ClientException $e) {
                        $response = $e->getResponse();
                        $responseBodyAsString = $response->getBody()->getContents();
                    }
                       
                    
            }
            
            if ($response->code=='ok') {
                return redirect()->back()->with('success', 'Message sent');
             
            }else{
                 return redirect()->back()->with('error', "Message not sent");
            }
            
          
            
            
        }
        else{
           return redirect()->back()->with('error', 'error occured');
        }
    }
    public function banuser(Request $request){
        
        $user=User::where('id',$request->id)->update(['status'=>3]);
        
        if($user){
            return redirect()->back()->with('success', 'User banned!');
        }else{
            return redirect()->back()->with('error', 'User unbanned!');
        }
    }
    
      public function unbanuser(Request $request){
        
        $user=User::where('id',$request->id)->update(['status'=>2]);
        
        if($user){
            return redirect()->back()->with('success', 'User unbanned!');
        }else{
            return redirect()->back()->with('error', 'User banned!');
        }
    }
    
    public function activateuser(Request $request){
        
        $id=$request->id;
        
        $code="act#".$id;
        $amount=0;
        //check the country of this user
        
        $check=User::where('id',$id)->first();
        
        // return $check->country;
        
        if($check->country=='KENYA'){
            $amount="450";
        }else{
            
            $amount=env('actu');
        }
        
        $ch = curl_init();
      
        $fields = array(
            "TransactionType"=>"Pay Bill",
              "TransID"=>"PBD7MAJUGF",
              "TransTime"=>"20210213003607",
              "TransAmount"=>$amount,
              "BusinessShortCode"=>"4070303",
              "BillRefNumber"=>$code,
              "InvoiceNumber"=>"0",
              "ThirdPartyTransID"=>"0",
              "MSISDN"=>"254720895177",
              "FirstName"=>"John",
              "MiddleName"=>"Doe",
              "LastName"=>"Mike"
          );
      
      $url = "https://deploy.elshadaigtinvestment.org/api/v1/response";
      curl_setopt($ch,CURLOPT_URL,$url);
      curl_setopt($ch,CURLOPT_POST, 1);                //0 for a get request
      curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($fields));
      curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch,CURLOPT_CONNECTTIMEOUT ,3);
      curl_setopt($ch,CURLOPT_TIMEOUT, 20);
      $response = curl_exec($ch);
    //   print "curl response is:" . $response;
      curl_close ($ch);
      
      if($ch){
           return redirect()->back()->with('success', 'User activated');
      }else{
           return redirect()->back()->with('error', 'An error occured');
      }
        
        
    }
    public function updateuser(Request $request){
        
        
        // $validatedData = $request->validate([
        // 'phone' => 'required|unique:users|',
        // 'email' => 'required|unique:users|',
        // 'username'=>'required|unique:users|'
        
        // ]);
        $username=$request->username;
        $email=$request->email;
        $phone=$request->phone;
        $id=$request->id;
        $ref_bal=$request->ref_bal;
        $country=$request->country;
        $wallet=$request->wallet;
        $currency=$request->currency;
        $status=$request->status;
        
        $user=User::where('id',$id)->update(['username'=>$username,'email'=>$email,'phone'=>$phone,'country'=>$country,'currency'=>$currency,'ref_bal'=>$ref_bal,'wallet'=>$wallet,'status'=>$status]);
        
        if($user){
            return redirect()->back()->with('success', 'User updated!');
        }else{
            return redirect()->back()->with('error', 'An error occured!');
        }
        
        
    }
    public function edituser(){
        
        $users=User::all();
        
        return view('user.edit')->with(compact('users'));
    }
    
    public function withs(){
        
        $withs=MpesaTransaction::where('type','Withdraw')->orWhere('type','ReferalToWallet')->orWhere('type','TokenToWallet')->get();
        
        return view('user.withs')->with(compact('withs'));
    }
    
    public function depos(){
        
        $deposits = MpesaTransaction::where('type','Deposit')->get();
        
        return view('user.depos')->with(compact('deposits'));
    }
    public function active(){
        
        $users=User::where('status',2)->orWhere('status', 3)->get();
        
        return view('user.users')->with(compact('users'));
    }
    
     public function inactive(){
        
        $users=User::where('status',1)->get();
        
        return view('user.inusers')->with(compact('users'));
    }
      public function blogpost(Request $request){
          
         
         
         
         
         $post=$request->post;
         
         $blogpost=new Blogs();
         
         $blogpost->user_id=Auth::user()->id;
         
         $blogpost->title=$request->title;
         
         $blogpost->post=$request->post;
         
         $blogpost->status=0;
         
         if($blogpost->save()){
             return redirect()->back()->with('success', 'Your Blog was submited!');
         }else{
             return redirect()->back()->with('error', 'Blog was not submited!');
         }
         
        
    }
    
    public function blog(Request $request){
        
        $blogs=Blogs::all();
        
        return view('user.blog')->with(compact('blogs'));
        
    }
    
    public function approveblog(Request $request){
        
        //get the blog id
        
        $blog=Blogs::where('id',$request->id)->first();
        
        $user_id=$blog->user_id;
        
        //get this user id and add blog earnings
        
        $user=User::where('id',$user_id)->first();
        
        $wallet=$user->wallet+env('blog_ek');
        
        
        
      
        
        if((User::where('id',$user_id)->update(['wallet'=>$wallet])) && ($blog->update(['status'=>1]))){
            
            return redirect()->back()->with('success', 'Blog was updated successfully');
            
        }else{
            return redirect()->back()->with('error', 'An error occured in updating blog');
        }
        
        
    }
     public function purchase(Request $request){
        
        return redirect()->back()->with('error', 'Please contact admin for further assistance on WhatsApp +254101553721');
        
    }
    public function airtime(){
        return view('user.airtime');
    }
    public function applyloan(Request $request){
        
        return redirect()->back()->with('error', 'You do not qualify for a loan!');
        
    }
    
    public function softloan(){
        return view('user.loan');
    }
    public function payforex(Request $request){
        $package=$request->forexid;
        
        //check if there is that id in the database
        
        $checks=Forex::where('id',$package)->get();
        if($checks){
            foreach($checks as $check){
            
            //check if auth user has balance greater thna amount
            if(Auth::user()->wallet>=$check->amount){
                //deduct this amount from the user
                
               $new_balance=intval((Auth::user()->wallet)-intval($check->amount));
                
                //update user balance
                $p=$check->package_name;
                $a=$check->amount;
                $g=(string)$check->group_link;
                
                if((User::where('id',Auth::user()->id)->update(['wallet'=>$new_balance]))){
                    
                    try{

                        Mail::to(Auth::user()->email)->send(new ForexSub(Auth::user()->username,$p,$a,$g));
                        return redirect()->back()->with('success', 'You have successfully  enrolled to this package');
                        }catch(\Swift_TransportException $transportExp){
                          //$transportExp->getMessage();
                        //   return redirect()->back()->with('error',$transportExp->getMessage());
                            return $transportExp->getMessage();
                       
                        }
                    
                     
                     
                    
                }else{
                    return redirect()->back()->with('error', 'An error occured');
                }
                
                
            }else{
                return redirect()->back()->with('error', 'You have insufficient balance, Deposit to Learn forex');
            }
            
            
        }
        }else{
            return redirect()->back()->with('error', 'Package not found!');
        }
        
        
    }
    
     public function packages(){
    	return view('user.forexpackage');
    }
    
    public function profile(){
    	return view('user.profile');
    }
    private function with($mobile,$amount){
        
            
              
        
                $config = new MpesaConfig(
                                      [
                                         // API Credentials
                                         'consumer_key'    => '0Ci4gw2qrNaMEAAzKIAAckLVSpDdTDNZ',
                                         'consumer_secret' => 'ddvyhxx2mOAKWcwi',
                                
                                         // Environment
                                         'environment' => 'production', 
                                         
                                         // Short code
                                         'short_code'  => '3032249', 
                                
                                         'initiator_name'=>'ElshadaiAPI',
                                         
                                         'initiator_password'=>'Munene37@',
                                         
                                         
                                         'identifier_type' => MpesaConstants::MPESA_IDENTIFIER_TYPE_PAYBILL, 
                                         
                                         'queue_timeout_url' => 'https://deploy.elshadaigtinvestment.org/api/v1/response',
                                         'result_url'        => 'https://deploy.elshadaigtinvestment.org/api/v1/response',
                                
                                      ]
                                        );

                            $mpesa = new Mpesa($config);
                            
                            // Perform B2C transaction
                            
                            // return ($config);
                            
                            try{
                            
                              $response = $mpesa->sendB2C($amount,$mobile,'BusinessPayment'); 
                            
                              echo $response;
                              // or
                              echo $response->getJSONString();
                            
                            } catch(MpesaInternalException $e) {
                              // This exception occurs when a configuration is missing e.g. consumer key
                              echo $e->getMessage();
                            
                            } catch(MpesaClientException $ce){
                              // This occurs when the request has been sent to the mpesa servers but some invalid
                              // data was supplied e.g. invalid consumer key
                               
                              echo $ce->getStatusCode(); // Returns http status code of the error
                              echo $ce->getMessage(); // Returns the error message
                              echo $ce->getErrorBody(); //  Returns the json string of the error body returned from server
                              print_r($ce->getRequestParameters()); // An array of the request parameters sent i.e. the headers,
                                                                      // the request body, the URL hit, and method
                            
                            } catch (MpesaServerException $se) {
                              // This occurs when the request has been sent to the mpesa servers
                              // but the server experienced some issues this is an error from the gateway and
                              // is not the client fault
                            
                              echo $se->getStatusCode(); // Returns http status code of the error
                              echo $se->getMessage(); // Returns the error message
                              echo $se->getErrorBody(); //  Returns the json string of the error body returned from server
                              print_r($se->getRequestParameters()); // An array of the request parameters sent i.e. the headers,
                                                                      // the request body, the URL hit, and method
                            
                            }
        
            
              
             }
        
    

    public function payforclient(){

    	//get the client name
    	return view('user.pay');


    } 
    
 public function deposit(){
     
     return view('user.deposit');
 }
 public function withdraw(){
     
     return view('user.withdraw');
 }
 public function withdrawwal(Request $request){
     $amount=$request->amount;
     //check if user has balance greater than amount
     if((Auth::user()->wallet)>=$amount){
         if($amount>env('with_amount')){
             //call the withdrawal function
             //deduct the amount from the user
             $u=User::where('id',Auth::user()->id)->first();
             $balance=$u->wallet;
             
             $balance=$balance-$amount;
             User::where('id',Auth::user()->id)->update(['wallet'=>$balance]);
             
             
             
             $w=new Withdraw();
             $w->user_id=Auth::user()->id;
             $w->amount=$amount;
             $w->source="wallet";
             $w->destination="mpesa";
             $w->status=0;
             $w->save();
             
             Mail::to(Auth::user()->email)->send(new Withdrawal(Auth::user()->username,$amount,"wallet","mpesa"));
             
             
             if($amount>=500 && $amount <=999){
                 $amount=$amount-23;
             }elseif($amount>=1000 && $amount <=1499){
                 $amount=$amount-34;
             }elseif($amount>=1500 && $amount <=2499){
                 $amount=$amount-34;
             }elseif($amount>=2500 && $amount <=3499){
                 $amount=$amount-56;
             }elseif($amount>=3500 && $amount <=4999){
                 $amount=$amount-56;
             }elseif($amount>=5000 && $amount <=7499){
                 $amount=$amount-85;
             }elseif($amount>=7500 && $amount <=9999){
                 $amount=$amount-84;
             }elseif($amount>=10000 && $amount <=14999){
                 $amount=$amount-112;
             }elseif($amount>=15000 && $amount <=19999){
                 $amount=$amount-112;
             }elseif($amount>=20000 && $amount <=24999){
                 $amount=$amount-112;
             }elseif($amount>=25000 && $amount <=29999){
                 $amount=$amount-112;
             }elseif($amount>=30000 && $amount <=34999){
                 $amount=$amount-112;
             }elseif($amount>=35000 && $amount <=39999){
                 $amount=$amount-202;
             }elseif($amount>=40000 && $amount <=44999){
                 $amount=$amount-202;
             }elseif($amount>=45000 && $amount <=49999){
                 $amount=$amount-202;
             }elseif($amount>=50000 && $amount <=69999){
                 $amount=$amount-210;
             }elseif($amount>=70000 && $amount <=150000){
                 $amount=$amount-210;
             }else{
                 $amount=$amount-0;
             }
             
             $this->with(Auth::user()->phone,$amount);
             
             
             
             
             
             return redirect()->back()->with('success', 'Withdrawal is getting processed');
             
         }else{
             return redirect()->back()->with('error', 'Amount should be greater than '.env('with_amount'));
         }
     }else{
         return redirect()->back()->with('error', 'Insufficient balance in account');
     }
 }

 
 public function withdrawinv(Request $request){
     
     $withdrawalAmount = $request->amount;
     $wallet = Wallet::where('user_id', Auth::user()->id)->get();
     $tokenBalance = $wallet[0]->token;
     $mainWallet = $wallet[0]->main;
     
     if($tokenBalance < 250){
        Session::flash('error','Insufficient token balance! You should have more than Ksh.250');
        return redirect()->back(); 
     }
     
     $charges = 0;
      
     if($withdrawalAmount >= 200 && $withdrawalAmount <= 999){
       $charges = 23;  
     }elseif($withdrawalAmount >= 1000 && $withdrawalAmount <= 2499){
       $charges = 35;  
     }elseif($withdrawalAmount >= 2500 && $withdrawalAmount <= 4999){
       $charges = 56;  
     }elseif($withdrawalAmount >= 5000 && $withdrawalAmount <= 9999){
       $charges = 85;  
     }elseif($withdrawalAmount >= 10000 && $withdrawalAmount <= 29999){
       $charges = 112;  
     }
     elseif($withdrawalAmount >= 30000 && $withdrawalAmount <= 39999){
       $charges = 202;  
     }
     elseif($withdrawalAmount >= 40000 && $withdrawalAmount <= 50000){
       $charges = 250;  
     }
  
     $afterTransactionCharge = $withdrawalAmount - $charges; 
     $newTokenBalance = $tokenBalance - $withdrawalAmount;
     $newMainBalance = $mainWallet +  $afterTransactionCharge;
     
     $transaction = new MpesaTransaction;
     $transaction->user_id = Auth::user()->id;
     $transaction->type = "TokenToWallet";
     $transaction->amount = $request->amount;
     $transaction->receipt_number = 'MT'.Str::random(8);
     $transaction->transaction_date = date('Y-m-d H:i:s');
     $transaction->phone_number = Auth::user()->phone;
     $transaction->status = "done";
     $transaction->save(); 
     
     $charge = new Charge;
     $charge->amount = $charges;
     $charge->user_id = Auth::user()->id;
     $charge->save();
     
     $user = Wallet::find($wallet[0]->id);
     $user->token = $newTokenBalance;
     $user->main = $newMainBalance;
     
     if($user->save()){
         Session::flash('Success','KSH.'.$withdrawalAmount.' moved to main wallet! Transaction cost is Ksh.'.$charges);
         
         return redirect()->back();
     }  
 }
 
 public function withdrawref(Request $request){
     $withdrawalAmount = $request->amount;
     $wallet = Wallet::where('user_id', Auth::user()->id)->get();
     $refBalance = $wallet[0]->referal;
     $mainWallet = $wallet[0]->main;
     
     if($refBalance < 200){
        Session::flash('error','Insufficient referal balance! You should have more than Ksh.200');
        return redirect()->back(); 
     }
     
     $charges = 0;
      
    //  if($withdrawalAmount >= 200 && $withdrawalAmount <= 999){
    //   $charges = 23;  
    //  }elseif($withdrawalAmount >= 1000 && $withdrawalAmount <= 2499){
    //   $charges = 35;  
    //  }elseif($withdrawalAmount >= 2500 && $withdrawalAmount <= 4999){
    //   $charges = 56;  
    //  }elseif($withdrawalAmount >= 5000 && $withdrawalAmount <= 9999){
    //   $charges = 85;  
    //  }elseif($withdrawalAmount >= 10000 && $withdrawalAmount <= 30000){
    //   $charges = 112;  
    //  }
  
     $afterTransactionCharge = $withdrawalAmount - $charges; 
     $newRefBalance = $refBalance - $withdrawalAmount;
     $newMainBalance = $mainWallet +  $afterTransactionCharge;
     
     $transaction = new MpesaTransaction;
     $transaction->user_id = Auth::user()->id;
     $transaction->type = "ReferalToWallet";
     $transaction->amount = $request->amount;
     $transaction->receipt_number = 'MT'.Str::random(8);
     $transaction->transaction_date = date('Y-m-d H:i:s');
     $transaction->phone_number = Auth::user()->phone;
     $transaction->status = "done";
     $transaction->save();
     
     $user = Wallet::find($wallet[0]->id);
     $user->referal = $newRefBalance;
     $user->main = $newMainBalance;
     
    //  $charge = new Charge;
    //  $charge->amount = $charges;
    //  $charge->save();
     
     if($user->save()){
         Session::flash('Success','KSH.'.$withdrawalAmount.' moved to main wallet!. Transaction cost Ksh.'.$charges);
         
         return redirect()->back();
     }
     
 }


  public function showpackages(){


    $packages=InvPackages::all();

    return view('user.packages')->with(compact('packages'));

  }

  public function showpackage(Request $request){

  		$id=$request->id;

  		$packages=InvPackages::where('id',$id)->get();

  		return view('user.package')->with(compact('packages'));

  }

  public function invest(Request $request){
  	//get the package id 

  	$package=$request->package;
  	$amount=$request->amount;

  	//check the min and maximum of this packahe

  	$packageinfo=InvPackages::where('id',$package)->first();

  	$package_min=$packageinfo->min;
  	$package_max=$packageinfo->max;

  	$percent=$packageinfo->percent;
  	//check if users balance is greater than min
  	$user_balance=Auth::user()->wallet;
  	if($amount>$user_balance){
  		//user has less balance
  		return redirect()->back()->with('error', 'You have Insufficient balance in your wallet!');



  	}else{

  		//this user has sufficient balance

  		//check if the amount is between the maximum and minimum
  		if($amount>$package_max){
  			$message="The maximum you can invest for this package is ".$package_max;
  			return redirect()->back()->with('error', $message);
  		}else{

  			//cut the balance from user balance and place the invstment

  			$newbalance=$user_balance-$amount;

  			$user=User::where('id',Auth::user()->id)->update(["wallet"=>$newbalance]);

  			$inv=new Investments();

  			$inv->user_id=Auth::user()->id;

  			$inv->package_id=$package;

  			$inv->capital=$amount;

  			$daily=($percent/100)*($amount);

  			$inv->daily=$daily;

  			$inv->earnings=0;

  			$inv->status=1;

  			$days=Carbon::now()->addDays($packageinfo->duration)->format('Y-m-d H:i:s');

  			// $days=Carbon::now()->format('Y-m-d H:i:s');
  			$inv->running_time=Carbon::now()->format('H:i:s');

  			$inv->end_date=$days;

  			if($inv->save()){
  				$message="Your Investment was placed successfully";
  				return redirect()->back()->with('success', $message);
  			}else{
  				$message="An error occured";
  				return redirect()->back()->with('error', $message);
  			}

  		}

  	}

  }


    public function pay(Request $request){

    	$username=$request->cusername;

    	//check if the username is available in the database

    	$check=User::where('username',$username)->first();

    	if(($check)){
    		$authbalance=Auth::user()->wallet;

    		$reg=env('Activation_amount');

    		$bill="fp#".$check->id;

    		if($authbalance>=$reg){

    			$url=env('APP_URL')."/api/v1/response";

				$data=array(
					 "TransactionType"=>"PayBill",
					  "TransID"=>"PBD7MAJUGF",
					  "TransTime"=>"20210213003607",
					  "TransAmount"=>"450.00",
					  "BusinessShortCode"=>"4070303",
					  "BillRefNumber"=>$bill,
					  "InvoiceNumber"=>"0",
					  "ThirdPartyTransID"=>"0",
					  "MSISDN"=>"254720895177",
					  "FirstName"=>Auth::user()->firstname,
					  "MiddleName"=>"Doe",
					  "LastName"=>Auth::user()->lastname	
    			);

    			$postdata = json_encode($data);
				// $ch = curl_init($url);
				// curl_setopt($ch,CURLOPT_URL, $url);
				// curl_setopt($ch,CURLOPT_POST, 1);
				// curl_setopt($ch,CURLOPT_POSTFIELDS, $postdata);
				// curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
				// $result = curl_exec($ch);
				// curl_close ($ch);

				$result=app('App\Http\Controllers\ApiController')->requesting($postdata);



				// return $url;

				
				if($result){
		        	$newbalance=$authbalance-env('Activation_amount');

    				$update=User::where('id',Auth::user()->id)->update(['wallet'=>$newbalance]);
		        	toastr()->success('User activated successfully!')->with('success', 'Action was successfully triggered');
  					return redirect()->back();
		        }else{
		        	toastr()->error('An error occured!');
  					return redirect()->back()->with('error', $postdata);
		        }
    		}else{
    			//record not found
    		toastr()->error('Insufficinet funds!');
  			return redirect()->back()->with('error', 'Insufficinet funds!!');
    		}
    	}else{
    		//record not found
    		toastr()->error('No record found!');
  			return redirect()->back()->with('error', 'No record found!');

    	}
    }

    public function profileupdate(Request $request){

    	

  /*
  talk the select file and move it public directory and make avatars
  folder if doesn't exsit then give it that unique name.

  */
  		$bio=$request->bio;

  		if ($request->hasFile('user_photo')) {
  		$photoName = time().'.'.$request->user_photo->getClientOriginalExtension();

  		$request->user_photo->move(public_path('img/profile'), $photoName);
  		$update=User::where('id',Auth::user()->id)->update(['bio'=>$bio,'img'=>$photoName]);
  		if($update){
  			toastr()->success('Profile saved successfully!');
  			return redirect()->back();
  		}else{
  			toastr()->error('An error occured!');
  			return redirect()->back();
  		}
  		

  		}else{

  			$update=User::where('id',Auth::user()->id)->update(['bio'=>$bio]);
  			toastr()->success('Profile saved successfully!');
  			return redirect()->back();
  		}

  		


  		



    }

    public function showaffiliates()
    {
    	# get my ref body
    	 $refs=User::where('referal',Auth::user()->username)->get();
    	 //Level two
    	 $level_twos=array();
    	 $level_threes=array();
    	 $level_4s=array();

    	 foreach ($refs as $ref) {
    	 	
            
    	 	$level_twos=User::where('referal',$ref->username)->get();
                # code...
    	 	foreach ($level_twos as $level_two) {
    	 		# code...
    	 			$level_threes=User::where('referal',$level_two->username)->get();

    	 			foreach ($level_threes as $level_three) {
    	 				# code...
    	 				 $level_4s=User::where('referal',$level_three->username)->get();

    	 			}

    	 	}

    	 }








    	  //get my direct donwnline
    	  return view('user.affiliates')->with(compact('refs','level_twos','level_threes','level_4s'));


    }
}
