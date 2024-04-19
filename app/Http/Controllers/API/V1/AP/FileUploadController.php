<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Helpers\JwtHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CommunicationInbox;
use App\Models\TaskManager;
use Illuminate\Support\Str;


class FileUploadController extends Controller
{
    public function upload(Request $request)
    {


        // doest not contain white space. must be underscore seperated.  for eg. employee, vendor, payment_transaction
        $module = $request->input('module');
        if ($module == "customtask") {
            $aclUserId = JwtHelper::getSesUserId();
            $count = TaskManager::where('task_code', '!=', '')->count();
            $module = $count + 1 . "-" . strtotime(date('Y-m-d')) . "-customtask-files";
            if ($request->hasfile('file')) {
                $file = $request->file('file');
                $extension = (!empty($file->getClientOriginalExtension())) ? $file->getClientOriginalExtension() : "png";
                $random = Str::random(10);
                $uploadPath = 'public/customtask/' . $module;
                $filename = 'customtask-file' . '-' . strtotime(date('Y-m-d H:i:s')) . '-' . $random . '.' . $extension;
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
        } else if ($module == "management") {

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
        }else 
         if ($request->hasfile('file') && $module != "customtask" && $module != "management") {
            $file = $request->file('file');
            $extension = (!empty($file->getClientOriginalExtension())) ? $file->getClientOriginalExtension() : "png";
            $random = Str::random(10);
            $uploadPath = ($module != '') ? 'public/' . $module : 'public';
            $filename_url = env('APP_URL') . $uploadPath . '/' . 'file' . '-' . strtotime(date('Y-m-d H:i:s')) . '-' . $random . '.' . strtolower($extension);
            $filename = 'file' . '-' . strtotime(date('Y-m-d H:i:s')) . '-' . $random . '.' . strtolower($extension);
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
}
