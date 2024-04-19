<?php

namespace App\Http\Controllers\API\V1\EP;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CommunicationInbox;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class FileUploadController extends Controller
{
 public function upload(Request $request)
 {


    // doest not contain white space. must be underscore seperated.  for eg. employee, vendor, payment_transaction
        $module = $request->input('module');


        if ($module == "management") {

            $count = CommunicationInbox::select('*')->count();

            $module = $count + 1 . "-" . strtotime(date('Y-m-d')) . "-communicationinbox-files";
            if ($request->hasfile('file')) {
                $file = $request->file('file');
                $extension = (!empty($file->getClientOriginalExtension())) ? $file->getClientOriginalExtension() : "png";
                $random = Str::random(10);
                $uploadPath = 'public/management/' . $module;
                $filename = 'communicationinbox-file' . '-' . strtotime(date('Y-m-d H:i:s')) . '-' . $random . '.' . $extension;
                $filename_url = env('APP_URL') . $uploadPath . '/' . $filename;
                $file->move($uploadPath, $filename);
                return response()->json([
                    'keyword' => 'success',
                    'message' => 'File has uploaded.',
                    'data'   => $filename,
                    'show_url' => $filename_url,
                    'folder' => $module
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'data'        => [],
                    'message'      => 'file upload failed'
                ]);
            }
        }
        else if($request->hasfile('file') && $module != "management")
         {
             $file=$request->file('file');
             $extension=(!empty($file->getClientOriginalExtension())) ? $file->getClientOriginalExtension() : "png";
             $random = Str::random(10);            
             $uploadPath = ($module != '') ? 'public/'.$module : 'public';
             $filename_url = env('APP_URL').$uploadPath.'/'.'file'.'-'.strtotime(date('Y-m-d H:i:s')) . '-' . $random . '.' . strtolower($extension);
             $filename = 'file'.'-'.strtotime(date('Y-m-d H:i:s')) . '-' . $random . '.' . strtolower($extension);
             $file->move($uploadPath, $filename);    
                 return response()->json([
                     'keyword' => 'success',
                     'message' => 'File has uploaded.',
                     'data'   => $filename,
                     'show_url' => $filename_url
                 ]);
             } else {
                 return response()->json([
                     'keyword' => 'failed',
                     'data'        => [],
                     'message'      => 'file upload failed'
                 ]);
             }          
 }

 public function removeFile(Request $request)
    {
        $module = $request->module;
        $file = $request->file;

        $image_path = public_path() . '/' . 'public' . '/' . $module . '/' . $file;

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
                'message'      => 'File deleted failed'
            ]);
        }
    }

    public function removeFileManagement(Request $request)
    {
        $module = $request->module;
        $file = $request->file;
        $folder = $request->folder;

        $image_path = public_path() . '/' . 'public' . '/' . $module .'/'. $folder. '/' . $file;

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
                'message'      => 'File deleted failed'
            ]);
        }
    }

}