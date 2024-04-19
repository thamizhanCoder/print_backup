<?php

namespace App\Http\Controllers\API\V1\WP;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;


class FileUploadController extends Controller
{
    public function upload(Request $request)
    {


        // doest not contain white space. must be underscore seperated.  for eg. employee, vendor, payment_transaction
        $module = $request->input('module');

        if ($request->hasfile('file')) {
            $file = $request->file('file');
            $extension = (!empty($file->getClientOriginalExtension())) ? $file->getClientOriginalExtension() : "png";
            $random = Str::random(10);
            $uploadPath = ($module != '') ? 'public/' . $module : 'public';
            $filename_url = env('APP_URL') . $uploadPath . '/' . 'file' . '-' . strtotime(date('Y-m-d H:i:s')) . '-' . $random . '.' . strtolower($extension);
            $filename = 'file' . '-' . strtotime(date('Y-m-d H:i:s')) . '-' . $random . '.' . strtolower($extension);


                // $height = Image::make($file)->height();
                // $width = Image::make($file)->width();
                // $destinationPath = $uploadPath . '/thumbnails';
                // if ($module && !file_exists($destinationPath)) {
                //     // print_r("hi");exit;
                //     mkdir($destinationPath, 0755, true);
                // }
                // // if (!Storage::exists($destinationPath)) {
                // //     Storage::makeDirectory($destinationPath, 0755, true);
                // // }
                // $img = Image::make($file->getRealPath());
                // $img->resize($height,$width,function ($constraint) {
                //     $constraint->aspectRatio();
                // })->save($destinationPath.'/'.$filename);
                // $thumbnailUrl = env('APP_URL') . $destinationPath . '/' . $filename;


            $file->move($uploadPath, $filename);
            
            return response()->json([
                'keyword' => 'success',
                'message' => 'File has uploaded',
                'data'   => $filename,
                'show_url' => $filename_url,
                // 'thumbnailUrl' => $thumbnailUrl
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'data'        => [],
                'message'      => 'File upload failed'
            ]);
        }
    }


    public function removeFile(Request $request)
    {
        $module = $request->module;
        $file = $request->file;

        $image_path = public_path() .'/'. 'public'. '/' . $module . '/' . $file;

        if (File::exists($image_path)) {
            File::delete($image_path);
            return response()->json([
                'keyword' => 'success',
                'data'        => [],
                'message'      => 'File deleted successfully'
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'data'        => [],
                'message'      => 'File delete failed'
            ]);
        }
    }
}
