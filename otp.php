<?php
  // Start the session
  session_start();
  //give your token here
  $token = "xxxxxxxxxxxxxxxxxxxxxxxxxx";
  
  
  
function GetRealUserIp($default = NULL, $filter_options = 12582912) {
	$HTTP_CLIENT_IP = "";
    $HTTP_X_FORWARDED_FOR =  $_SERVER["HTTP_X_FORWARDED_FOR"];
	if (!empty($_SERVER['HTTP_CLIENT_IP'])) { $HTTP_CLIENT_IP = $_SERVER['HTTP_CLIENT_IP']; }
    $HTTP_CF_CONNECTING_IP = $_SERVER["HTTP_CF_CONNECTING_IP"];
    $REMOTE_ADDR = $_SERVER["REMOTE_ADDR"];

    $all_ips = explode(",", "$HTTP_X_FORWARDED_FOR,$HTTP_CLIENT_IP,$HTTP_CF_CONNECTING_IP,$REMOTE_ADDR");
    foreach ($all_ips as $ip) {
        if ($ip = filter_var($ip, FILTER_VALIDATE_IP, $filter_options))
            break;
    }
    return $ip?$ip:$default;
}

$ip = GetRealUserIp();  
  
  
  
echo "<center>";
if (isset($_POST['code'])) { 

//csrf protection
if (($_SESSION["csrftoken"]) != ($_POST['csrftoken'])) {
echo "OTP Abuse detected ! Please refresh the page and try again !";	
	exit();
}

  //check for matching
  if ($_SESSION["otp"] == $_POST['code']) {
  echo "You have verified your mobile number successfully</br>";
  $_SESSION['verified'] = "1";
  //ওটিপি রিকোয়েস্ট লিমিট রিসেট করে দেওয়া হলো ।
  $_SESSION[$ip] = "0";
 
} else {
  echo "You have entered wrong otp code, try again later.";
  }
}



if ((isset($_SESSION['verified'])) AND ($_SESSION['verified'] == '1')) {

//you can post your page content here  or below the page.


} else {

if(isset($_POST['otp'])) {


//check csrf for protection 

if (($_SESSION["csrftoken"]) != ($_POST['csrftoken'])) {
echo "OTP Abuse detected ! Please refresh the page and try again !";	
	exit();
} else {
$csrftoken = $_POST['csrftoken'];	
}

// দেখুন কতবার একটি আইপি থেকে ওটিপি রিকোয়েস্ট করা হয়েছে কিন্তু ভেরিফাই করা হয়নি । এখানে প্রতি session এ 5 বার maximum ওটিপি রিকোয়েস্ট করা যাবে । 5 বার ওটিপি প্রেরন করে একবারও যদি ভেরিফাই না করে তবে পূনরায় ওটিপি রিকোয়েস্ট করা যাবে না । অনেকে বার বার unknown নাম্বারে ওটিপি রিকোয়েস্টে দিয়ে মানুষকে বিরক্ত করতে পারে /আপনার SMS ব্যালেন্স শেষ করতে পারে, এটি তা prevent করবে ।  চাইলে লিমিট বাড়াতে কিংবা কমাতে কিংবা এটি বাদ দিতে পারেন । তবে নিরাপত্ত্বার জন্য লিমিট রাখা ভালো । চাইলে ডেটাবেজ ব্যবহার করে লিমিট করতে পারেন, টাইম দিয়ে লিমিট করতে পারেন ।
if ($_SESSION[$ip] > "5") {
	echo "You're allowed to request otp for 5 times per session ! Your request is blocked.";
	exit();
}

  // Generate Random 5 digits otp
  $code = substr(md5(mt_rand()), 0, 5);
//send otp to mobile via api
  $to = preg_replace("|[^0-9 \+\/]|", '', $_REQUEST['number']);
//message text
   $message = "আপনার ওটিপি কোড: $code
XYZ কোম্পানী";
$url = "https://api.bdbulksms.net/api.php";
  $data= array(
  'to'=>"$to",
  'message'=>"$message",
  'token'=>"$token"
  );
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL,$url);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_ENCODING, '');
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  
  $smsresult = curl_exec($ch);
  $result = mb_substr($smsresult, 0, 2);
  if ($result == 'Ok') {

  
   //ওটিপি রিকোয়েস্টের  Per IP  লিমিট সেট করা হলো
	 if (isset($_SESSION[$ip])) { 
	  $_SESSION[$ip] = ($_SESSION[$ip] + 1);
	 } else {
		$_SESSION[$ip] = "1"; 
		
	 }
	 
  echo "Otp code is successfully sent to your mobile, you may have to wait upto 5 min to receive your code";

// save otp code on the session
  $_SESSION["otp"] = $code;

//show code input form
  echo "
  Enter the verification code below </br>
  <form action='' method='POST'>
  <input type='text' name='code'>
  <input type='hidden' name='csrftoken' value='$csrftoken' >
  <button type='submit' value='code' name='otp'>Verify</button>
  </form>";
  exit();
  } else {
  echo "Failed to send Otp. Please try again after sometime"; 
  exit();
  }
 
  } else {
	 //generate csrf token it's require to protect your otp sms form from the abusers 
	 
	 $csrftoken = substr(md5(mt_rand()), 0, 15);
	  $_SESSION["csrftoken"] = $csrftoken;
echo "
  Enter your mobile number to receive OTP code </br>
  <form action=''  method='POST'>
  <input type='text' name='number'>
  <input type='hidden' name='csrftoken' value='$csrftoken' >
  <button type='submit' value='otp' name='otp'>Get Otp</button>
  </form>";
  exit();
  }

//it's not required but for extra safety
  exit();
  }
  
?>
