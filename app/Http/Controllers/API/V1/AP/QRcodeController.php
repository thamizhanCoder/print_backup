<?php

namespace App\Http\Controllers\API\V1\AP;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;


class QRcodeController extends Controller
{
 public function getQrCode(Request $request)
 {

        $qrCode['QR_URL'] =  env('APP_URL') .'public/QrCode/qrcode.png';
        
        return response()->json([
            'keyword'      => 'success',
            'message'      => 'Qr Code viewed successfully',
            'data'        => $qrCode
        ]);
 }

}