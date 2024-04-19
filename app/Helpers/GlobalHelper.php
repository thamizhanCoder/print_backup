<?php

namespace App\Helpers;

use Carbon\Carbon;
use App\Models\ActivityLog;
use App\Models\CmsVideo;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Notify;
use App\Models\Orders;
use App\Models\Product;
use App\Models\Rating;
use App\Models\UserModel;
use paytm\checksum\PaytmChecksumLibrary;
use paytm\paytmchecksum\PaytmChecksum;

class GlobalHelper
{

  public function sendSMS_old($mobile, $msg)
	{

		$api_url = 'http://retailsms.nettyfish.com/api/mt/SendSMS?user=technogenesis&password=techno@012345&senderid=PRNTPP&channel=Trans&DCS=0&flashsms=0&number=91' . $mobile . '&text=' . $msg . '&route=4';

		$api_url = str_replace(" ", "%20", $api_url);
		// create curl resource
		$ch = curl_init();
		// set url
		curl_setopt($ch, CURLOPT_URL, $api_url);
		//return the transfer as a string
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// $output contains the output string
		$output = curl_exec($ch);
		// close curl resource to free up system resources
		curl_close($ch);
		return $output;
	}

    public function sendSMS($mobile, $msg) 
    {
        $apiKey = 'b1dc88ec-7ab0-11ed-9158-0200cd936042';

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://2factor.in/API/R1/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'module=TRANS_SMS&apikey=' . $apiKey . '&to=91' . $mobile . '&from=PRNTPP&msg=' . $msg . '',
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
    
    public static function customerNames($cus_id) 
   {    
     $data = Customer::where('customer_id',$cus_id)->first(); 
      return $data;
   }

   public static function getProduct($pro_id) 
   {    
     $data = Product::where('product_id',$pro_id)->leftjoin('acl_user','acl_user.acl_user_id','=','product.created_by')->select('acl_user.acl_user_id','acl_user.name','product.*')->first(); 
      return $data;
   }

   public static function getVideo($video_id) 
   {    
     $data = CmsVideo::where('cms_video_id',$video_id)->leftjoin('acl_user','acl_user.acl_user_id','=','cms_video.created_by')->select('acl_user.acl_user_id','acl_user.name','cms_video.*')->first(); 
      return $data;
   }

   public static function getOrder($ord_id) 
   {    
     $data = Orders::where('order_id',$ord_id)->first(); 
      return $data;
   }

   public static function getRating($rat_id) 
   {    
     $data = Rating::where('rating_review_id',$rat_id)->leftjoin('customer','customer.customer_id','=','rating_review.customer_id')->leftjoin('product','product.product_id','=','rating_review.product_id')->select('customer.*','rating_review.*','product.*')->first(); 
      return $data;
   }

   public static function getNotify($notify_id) 
   {    
     $data = Notify::where('notifyme_id',$notify_id)->leftjoin('customer','customer.customer_id','=','notifyme.customer_id')->leftjoin('product','product.product_id','=','notifyme.product_id')->select('notifyme.*','customer.*','product.*')->first(); 
      return $data;
   }



    public static function getOTP($digits)
    {

        return rand(pow(10, $digits - 1), pow(10, $digits) - 1);
    }

    public static function getDate($data = null)
    {
        return Carbon::createFromTimestamp(strtotime($data))
            ->timezone(Config('app.timezone'))
            ->toDateString();
    }

    public static function mergeFields($message, $merge_fields = [])
    {
        foreach ($merge_fields as $key => $val) {
            if (stripos($message, $key) !== false) {
                $message = str_ireplace($key, $val, $message);
            }
        }
        return $message;
    }

    public static function sendPush($key, $title, $body, $page, $data, $portal, $dbtitle, $dbbody, $user_type, $sender, $receiver, $module,$random_id)
    {
        $cur_date = date('Y-m-d');
        $message = [
            'title' => $title,
            'body' => $body,
            'page' => $page,
            'data' => $data,
            'portal' => $portal
        ];
        $push = Firebase::sendMultiple($key, $message);

        // if($module == 'events' || $module == 'package'){      
        //   $exist = NotificationModel::where('user_type',$user_type)->where('body',$body)->where('receiver',$receiver)->whereDate('created_on',$cur_date)->first();
        //   if(empty($exist)){      
        //       GlobalHelper::notification_create($dbtitle,$dbbody,$user_type,$sender,$receiver,$module,$page,$portal,$data);  
        //   }
        // }

        // if($module == 'vehicle maintenance' || $module == 'holiday' || $module == 'temp id card'){  

        $dec_data = json_encode($data, true);
        $exist = Notification::where('user_type', $user_type)->where('data', $dec_data)->where('receiver', $receiver)->whereDate('created_on', $cur_date)->first();
        if (empty($exist)) {
            // GlobalHelper::notification_create($dbtitle, $dbbody, $user_type, $sender, $receiver, $module, $page, $portal, $data,$random_id);
            GlobalHelper::notification_create($dbtitle, $dbbody, $user_type, $sender, $receiver, $module, $page, $portal, $data, $random_id);
        }

        // }


    }

    public static function notification_create($title, $body, $user_type, $sender, $receiver, $name, $page, $portal, $data, $random_id)
    {
        $notification = new Notification();
        $notification->title = $title;
        $notification->body = $body;
        $notification->user_type = $user_type;
        $notification->sender = $sender;
        $notification->receiver = $receiver;
        $notification->module_name = $name;
        $notification->page = $page;
        $notification->portal = $portal;
        $notification->random_id = $random_id;
        $notification->data = json_encode($data, true);
        $notification->created_on = Server::getDateTime();
        $notification->save();
        return true;
    }

    public static function logActivity($description, $activity_type, $created_by, $portal)
    {
        $insert = new ActivityLog();
        $insert->description = $description;
        $insert->created_on = Server::getDateTime();
        $insert->created_by = $created_by;
        $insert->activity_type = $activity_type;
        $insert->activity_portal = $portal;
        $insert->save();
        return true;
    }
    
    public static function paytmCheck($orderId)
    {

        $merchantId = 'DINPtl25324674360172';
        $merchantKey = '1rXbtjQo89Dvg1Qc';
        
            /**
            * import checksum generation utility
            * You can get this utility from https://developer.paytm.com/docs/checksum/
            */
            require_once("/home/u796996937/public_html/nriapi/vendor/paytm/paytmchecksum/PaytmChecksum.php");
            // require_once('vendor/autoload.php');

            /* initialize an array */
            $paytmParams = array();
            
            /* body parameters */
            $paytmParams["body"] = array(
            
                /* Find your MID in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys */
                "mid" => $merchantId,
            
                /* Enter your order id which needs to be check status for */
                "orderId" => $orderId,
            );
            
            /**
            * Generate checksum by parameters we have in body
            * Find your Merchant Key in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys 
            */
            $checksum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES), $merchantKey);
            
            /* head parameters */
            $paytmParams["head"] = array(
            
                /* put generated checksum value here */
                "signature"	=> $checksum
            );
            
            /* prepare JSON string for request */
            $post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);
            
            /* for Staging */
            //$url = "https://securegw-stage.paytm.in/v3/order/status";
            //$url = "https://securegw-stage.paytm.in/theia/api/v1/initiateTransaction";
            
            /* for Production */
             $url = "https://securegw.paytm.in/theia/api/v1/initiateTransaction";
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));  
            return curl_exec($ch);
    }
}
