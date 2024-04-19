<?php

namespace App\Http\Controllers\API\V1\WP;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\Server;
use App\Models\Cmsgreetings;

class CmsgreetingsController extends Controller
{

    public function list(Request $request)
    {
            //$currentDateTime = new DateTime('now');
            $date = date('Y-m-d H:i:s');
            $getGreetings = Cmsgreetings::select('cms_greeting.*')->where('cms_greeting.status',1)
            //->where('cms_greeting.from_date','<=', DB::raw('NOW()'))
            //->where('cms_greeting.to_date','>=',DB::raw('NOW()'))
            ->where('cms_greeting.from_date', '<=', $date)
            ->where('cms_greeting.to_date', '>=', $date)
             ->get();

            if ($getGreetings->count() > 0) {

                $final = [];
             foreach ($getGreetings as $value) {
                 $ary = [];
                 $ary['cms_greeting_id'] = $value['cms_greeting_id'];
                 $ary['image'] = $value['greeting_image'];
                 $ary['greeting_image'] = ($ary['image'] != '') ? env('APP_URL') . env('GREETINGS_URL') . $ary['image'] : env('APP_URL') . "avatar.jpg";
                 $ary['from_date'] = date("d-m-Y H:i:s", strtotime($value['from_date']));
                 $ary['to_date'] = date("d-m-Y H:i:s", strtotime($value['to_date']));
 
                 $final[] = $ary;
             }
        
                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Greetings listed succcessfully',
                    'data' => $final,
                    'currentDateTime' => $date,
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failure',
                    'message' => 'No data found',
                    'data' => []
                ]);
            }
    }

}
