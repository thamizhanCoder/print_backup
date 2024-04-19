<?php

namespace App\Http\Traits;

use App\Events\SendAvailable;
use App\Helpers\Firebase;
use App\Helpers\GlobalHelper;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Models\Customer;
use App\Models\Notify;
use App\Models\Product;
use App\Models\ProductCatalogue;
use App\Models\ProductVariant;
use App\Models\RelatedProduct;
use App\Models\VariantType;
use Illuminate\Support\Facades\log;
use Illuminate\Support\Facades\Validator;

trait EcommerceProductTrait
{
    //Created By Muthumani 21 Sep 2022 for personlized product module

    public function productVariantInsert($variant_details, $product_id)
    {

        try {

            $i = 0;

            $variant_details_array = json_decode($variant_details, true);

            if (!empty($variant_details_array)) {

                $variant_details_array_count = count($variant_details_array);

                foreach ($variant_details_array as $variant) {

                    $variantInsert = new ProductVariant();
                    $variantInsert->product_id = $product_id;
                    $variantInsert->variant_attributes = json_encode($variant['variant_attributes'], true);
                    $variantInsert->variant_code = $variant['variant_code'];
                    $variantInsert->mrp = $variant['mrp'];
                    $variantInsert->selling_price = $variant['selling_price'];
                    $variantInsert->quantity = $variant['quantity'];
                    $variantInsert->set_as_default = $variant['set_as_default'];
                    $variantInsert->internal_variant_id = $variant['index'];
                    $variantInsert->variant_options = json_encode($variant['variant_options'], true);
                    $variantInsert->weight = $variant['weight'];
                    $variantInsert->updated_on = Server::getDateTime();

                    if ($variantInsert->save()) {

                        $i++;
                    } else {

                        $i--;
                    }
                }
            }

            if ($i == $variant_details_array_count) {

                return true;
            } else {

                return false;
            }
        } catch (\Exception $exception) {
            Log::channel("photoframe")->error($exception);
            Log::channel("photoframe")->error('** error occure while inserting data in product varient table **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function productVariantUpdate($variant_details, $product_id, $pname, $pdes)
    {

        try {

            $i = 0;

            $variant_details_array = json_decode($variant_details, true);

            if (!empty($variant_details_array)) {

                $variant_details_array_count = count($variant_details_array);

                foreach ($variant_details_array as $variant) {

                    $product_variant_id = $variant['product_variant_id'];
                    $variantUpdate = $product_variant_id == 0 ? new ProductVariant() : ProductVariant::find($product_variant_id);
                    $variantUpdate->product_id = $product_id;
                    $variantUpdate->variant_attributes = json_encode($variant['variant_attributes'], true);
                    $variantUpdate->variant_code = $variant['variant_code'];
                    $variantUpdate->mrp = $variant['mrp'];
                    $variantUpdate->selling_price = $variant['selling_price'];
                    $variantUpdate->quantity = $variant['quantity'];
                    $variantUpdate->set_as_default = $variant['set_as_default'];
                    $variantUpdate->internal_variant_id = $variant['index'];
                    $variantUpdate->variant_options = json_encode($variant['variant_options'], true);
                    $variantUpdate->weight = $variant['weight'];
                    $variantUpdate->updated_on = Server::getDateTime();

                    // //Push notification
                    // if ($product_variant_id != '') {
                    //     $variantDetails = ProductVariant::where('product_variant_id', $product_variant_id)->where('quantity', 0)->first();
                    // }
                    // if (!empty($variantDetails)) {
                    //     if ($variantDetails->quantity == 0) {
                    //         if ($variant['quantity'] > 0) {
                    //             $notifyDetails = Notify::where('customer_id', '!=', '')->where('product_variant_id', $product_variant_id)->get();
                    //             if (!empty($notifyDetails)) {
                    //                 foreach ($notifyDetails as $Detail) {
                    //                     $cusId[] = $Detail['customer_id'];
                    //                 }
                    //             }
                    //             if (!empty($cusId)) {
                    //                 $token = Customer::whereIn('customer_id', $cusId)->where('token', '!=', NULL)->orwhere('mbl_token', '!=', NULL)->get();

                    //                 $productDetails = ProductVariant::where('product_variant_id', $product_variant_id)->leftjoin('product', 'product.product_id', '=', 'product_variant.product_id')->leftjoin('category', 'category.category_id', '=', 'product.category_id')->groupBy('product_variant.product_id')->select('product.product_id', 'product.service_id', 'product.product_name', 'product_variant.product_variant_id', 'category.category_name')->first();

                    //                 $title = "Notify me";
                    //                 $body = "Your requested item $productDetails->product_name is available 
                    //     now. Time to buy!";
                    //                 // $body = GlobalHelper::mergeFields($body, $emp_info);
                    //                 $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
                    //                 $random_id2 = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
                    //                 $module = 'Notify me';
                    //                 $portal = "website";
                    //                 $portal2 = "mobile";
                    //                 $page = 'notify_me';
                    //                 // $titlemod = "Rating & Review !";
                    //                 $data = [
                    //                     'product_id' => $productDetails->product_id,
                    //                     'service_id' => $productDetails->service_id,
                    //                     'product_variant_id' => $productDetails->product_variant_id,
                    //                     'product_name' => $productDetails->product_name,
                    //                     'category_name' => $productDetails->category_name,
                    //                     'random_id' => $random_id,
                    //                     'page' => $page,
                    //                     'url' => ''
                    //                 ];

                    //                 $data2 = [
                    //                     'product_id' => $productDetails->product_id,
                    //                     'service_id' => $productDetails->service_id,
                    //                     'product_variant_id' => $productDetails->product_variant_id,
                    //                     'product_name' => $productDetails->product_name,
                    //                     'category_name' => $productDetails->category_name,
                    //                     'random_id' => $random_id2,
                    //                     'page' => $page,
                    //                     'url' => ''
                    //                 ];

                    //                 if (!empty($token)) {
                    //                     $tokens = [];
                    //                     foreach ($token as $tk) {
                    //                         $tokens[] = $tk['token'];
                    //                     }

                    //                     $mbl_tokens = [];
                    //                     foreach ($token as $tks) {
                    //                         $mbl_tokens[] = $tks['mbl_token'];
                    //                     }

                    //                     $customerId = [];
                    //                     foreach ($token as $tk) {
                    //                         $customerId[] = $tk['customer_id'];
                    //                     }
                    //                 }
                    //                 if (!empty($tokens)) {
                    //                     foreach (array_chunk($tokens, 500) as $tok) {
                    //                         $key = $tok;
                    //                         if (!empty($key)) {
                    //                             $message = [
                    //                                 'title' => $title,
                    //                                 'body' => $body,
                    //                                 'page' => $page,
                    //                                 'data' => $data2,
                    //                                 'portal' => $portal,
                    //                                 'module' => $module
                    //                             ];
                    //                             $push = Firebase::sendMultiple($key, $message);
                    //                         }
                    //                     }
                    //                     if (!empty($customerId)) {
                    //                         foreach ($customerId as $cusId) {
                    //                             $getdata = GlobalHelper::notification_create($title, $body, 1, 1, $cusId, $module, $page, "website", $data2, $random_id2);
                    //                         }
                    //                     }
                    //                 }

                    //                 if (!empty($mbl_tokens)) {
                    //                     foreach (array_chunk($mbl_tokens, 500) as $mbl_tok) {
                    //                         $key_mbl = $mbl_tok;
                    //                         if (!empty($key_mbl)) {
                    //                             $message = [
                    //                                 'title' => $title,
                    //                                 'body' => $body,
                    //                                 'page' => $page,
                    //                                 'data' => $data,
                    //                                 'portal' => $portal2,
                    //                                 'module' => $module
                    //                             ];
                    //                             $push2 = Firebase::sendMultipleMbl($key_mbl, $message);
                    //                         }
                    //                     }
                    //                     if (!empty($customerId)) {
                    //                         foreach ($customerId as $cusId) {
                    //                             $getdata = GlobalHelper::notification_create($title, $body, 1, 1, $cusId, $module, $page, "mobile", $data, $random_id);
                    //                         }
                    //                     }
                    //                 }
                    //             }
                    //         }
                    //     }
                    // }

                    // //Email
                    // if (!empty($variantDetails)) {
                    //     if ($variantDetails->quantity == 0) {
                    //         if ($variant['quantity'] > 0) {
                    //             $cusdetails = Notify::where('product_variant_id', $product_variant_id)->where('email', '!=', 'null')->select('notifyme.*')->get();
                    //             $mails = [];
                    //             foreach ($cusdetails as $cusdetail) {
                    //                 if (!empty($cusdetail)) {
                    //                     $mails[] = $cusdetail['email'];
                    //                 }
                    //             }
                    //             $prod = array_chunk($mails, 500);
                    //             if (!empty($prod)) {
                    //                 for ($i = 0; $i < count($prod); $i++) {
                    //                     $sizeOfArrayChunk = sizeof($prod[$i]);
                    //                     for ($j = 0; $j < $sizeOfArrayChunk; $j++) {
                    //                         $mail_data = [];
                    //                         $mail_data['email'] = $prod[$i][$j];
                    //                         $mail_data['name'] = $pname;
                    //                         $mail_data['quantity'] = $variant['quantity'];
                    //                         if ($prod[$i] != '') {
                    //                             event(new SendAvailable($mail_data));
                    //                         }
                    //                     }
                    //                 }
                    //             }
                    //             $notify_delete = Notify::where('product_variant_id', $product_variant_id)->select('notifyme.*');
                    //             $notifydetails = $notify_delete->get();
                    //             $notify_deleteId = [];
                    //             foreach ($notifydetails as $notifydetail) {
                    //                 $notify_deleteId[] = $notifydetail['product_variant_id'];
                    //             }
                    //             $update = Notify::whereIn('product_variant_id', $notify_deleteId)->delete();
                    //         }
                    //     }
                    // }
                    if ($product_variant_id != '') {
                        $variantDetails = ProductVariant::where('product_variant_id', $product_variant_id)->where('quantity', 0)->first();
                    }

                    if ($variantUpdate->save()) {

                        //Push notification
                    // if ($product_variant_id != '') {
                    //     $variantDetails = ProductVariant::where('product_variant_id', $product_variant_id)->where('quantity', 0)->first();
                    // }
                    if (!empty($variantDetails)) {
                        if ($variantDetails->quantity == 0) {
                            if ($variant['quantity'] > 0) {
                                $notifyDetails = Notify::where('customer_id', '!=', '')->where('product_variant_id', $product_variant_id)->get();
                                if (!empty($notifyDetails)) {
                                    foreach ($notifyDetails as $Detail) {
                                        $cusId[] = $Detail['customer_id'];
                                    }
                                }
                                if (!empty($cusId)) {
                                    $token = Customer::whereIn('customer_id', $cusId)->where('token', '!=', NULL)->orwhere('mbl_token', '!=', NULL)->get();

                                    $productDetails = ProductVariant::where('product_variant_id', $product_variant_id)->leftjoin('product', 'product.product_id', '=', 'product_variant.product_id')->leftjoin('category', 'category.category_id', '=', 'product.category_id')->groupBy('product_variant.product_id')->select('product.product_id', 'product.service_id', 'product.product_name', 'product_variant.product_variant_id', 'category.category_name')->first();
                                    $title = "Notify me";
                                    $body = "Your requested item $productDetails->product_name is available now. Time to buy!";
                                    
                                    $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
                                    $random_id2 = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
                                    $module = 'Notify me';
                                    $portal = "website";
                                    $portal2 = "mobile";
                                    $page = 'notify_me';
                                    $data = [
                                        'product_id' => $productDetails->product_id,
                                        'service_id' => $productDetails->service_id,
                                        'product_variant_id' => $productDetails->product_variant_id,
                                        'product_name' => $productDetails->product_name,
                                        'category_name' => $productDetails->category_name,
                                        'random_id' => $random_id,
                                        'page' => $page,
                                        'url' => ''
                                    ];

                                    $data2 = [
                                        'product_id' => $productDetails->product_id,
                                        'service_id' => $productDetails->service_id,
                                        'product_variant_id' => $productDetails->product_variant_id,
                                        'product_name' => $productDetails->product_name,
                                        'category_name' => $productDetails->category_name,
                                        'random_id' => $random_id2,
                                        'page' => $page,
                                        'url' => ''
                                    ];

                                    if (!empty($token)) {
                                        $tokens = [];
                                        foreach ($token as $tk) {
                                            $tokens[] = $tk['token'];
                                        }

                                        $mbl_tokens = [];
                                        foreach ($token as $tks) {
                                            $mbl_tokens[] = $tks['mbl_token'];
                                        }

                                        $customerId = [];
                                        foreach ($token as $tk) {
                                            $customerId[] = $tk['customer_id'];
                                        }
                                    }
                                    if (!empty($tokens)) {
                                        foreach (array_chunk($tokens, 500) as $tok) {
                                            $key = $tok;
                                            if (!empty($key)) {
                                                $message = [
                                                    'title' => $title,
                                                    'body' => $body,
                                                    'page' => $page,
                                                    'data' => $data2,
                                                    'portal' => $portal,
                                                    'module' => $module
                                                ];
                                                $push = Firebase::sendMultiple($key, $message);
                                            }
                                        }
                                        if (!empty($customerId)) {
                                            foreach ($customerId as $cusId) {
                                                $getdata = GlobalHelper::notification_create($title, $body, 1, 1, $cusId, $module, $page, "website", $data2, $random_id2);
                                            }
                                        }
                                    }

                                    if (!empty($mbl_tokens)) {
                                        foreach (array_chunk($mbl_tokens, 500) as $mbl_tok) {
                                            $key_mbl = $mbl_tok;
                                            if (!empty($key_mbl)) {
                                                $message = [
                                                    'title' => $title,
                                                    'body' => $body,
                                                    'page' => $page,
                                                    'data' => $data,
                                                    'portal' => $portal2,
                                                    'module' => $module
                                                ];
                                                $push2 = Firebase::sendMultipleMbl($key_mbl, $message);
                                            }
                                        }
                                        if (!empty($customerId)) {
                                            foreach ($customerId as $cusId) {
                                                $getdata = GlobalHelper::notification_create($title, $body, 1, 1, $cusId, $module, $page, "mobile", $data, $random_id);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    //Email
                    if (!empty($variantDetails)) {
                        if ($variantDetails->quantity == 0) {
                            if ($variant['quantity'] > 0) {
                                $cusdetails = Notify::where('product_variant_id', $product_variant_id)->where('email', '!=', 'null')->select('notifyme.*')->get();

                                $mails = [];
                                foreach ($cusdetails as $cusdetail) {
                                    if (!empty($cusdetail)) {
                                        $mails[] = $cusdetail['email'];
                                    }
                                }
                                $prod = array_chunk($mails, 500);
                                if (!empty($prod)) {
                                    for ($i = 0; $i < count($prod); $i++) {
                                        $sizeOfArrayChunk = sizeof($prod[$i]);
                                        for ($j = 0; $j < $sizeOfArrayChunk; $j++) {
                                            $mail_data = [];
                                            $mail_data['email'] = $prod[$i][$j];
                                            $mail_data['name'] = $pname;
                                            $produc_details_img_get = Product::where('product_id',$product_id)->first();
                                            $mail_data['image']  = ($produc_details_img_get->thumbnail_image != '') ? env('APP_URL') . env('ECOMMERCE_URL') . $produc_details_img_get->thumbnail_image : env('APP_URL') . "avatar.jpg";
                                            $mail_data['pdes'] = $pdes;
                                            $mail_data['quantity'] = $variant['quantity'];
                                            $mail_data['selling_price'] = $variant['selling_price'];
                                            if ($prod[$i] != '') {
                                                event(new SendAvailable($mail_data));
                                            }
                                        }
                                    }
                                }
                                $notify_delete = Notify::where('product_variant_id', $product_variant_id)->select('notifyme.*');
                                $notifydetails = $notify_delete->get();
                                $notify_deleteId = [];
                                foreach ($notifydetails as $notifydetail) {
                                    $notify_deleteId[] = $notifydetail['product_variant_id'];
                                }
                                $update = Notify::whereIn('product_variant_id', $notify_deleteId)->delete();
                            }
                        }
                    }

                        $i++;
                    } else {

                        $i--;
                    }
                }
            }

            if ($i == $variant_details_array_count) {

                return true;
            } else {

                return false;
            }
        } catch (\Exception $exception) {
            Log::channel("photoframe")->error($exception);
            Log::channel("photoframe")->error('** error occure while inserting data in product varient table **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function saverelatedProducts($relatedpro, $product_id)
    {
        try {

            $i = 0;

            if (!empty($relatedpro)) {

                $relatedpro_count = count($relatedpro);

                foreach ($relatedpro as $proId) {

                    $newHardQus = new RelatedProduct();
                    $newHardQus->product_id = $product_id;
                    $newHardQus->service_id = $proId['service_id'];
                    $newHardQus->product_id_related = $proId['product_id_related'];
                    $newHardQus->created_on = Server::getDateTime();
                    $newHardQus->created_by = JwtHelper::getSesUserId();

                    if ($newHardQus->save()) {

                        $i++;
                    } else {

                        $i--;
                    }
                }
            }

            if ($i == $relatedpro_count) {

                return true;
            } else {

                return false;
            }
        } catch (\Exception $exception) {
            Log::channel("photoframe")->error($exception);
            Log::channel("photoframe")->error('** error occure while inserting data in related product table **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    //primary variants validation

    // public function validateSelectedVarients($primaryVariant)
    // {

    //     $i = 0;
    //     $imageArray = "";

    //     $primaryVariantArry = json_decode($primaryVariant, true);

    //     if (!empty($primaryVariantArry)) {

    //         $countOfprimaryVariant = count($primaryVariantArry);

    //         foreach ($primaryVariantArry as $prmvariant) {

    //             $imageArray = $prmvariant['product_image'];

    //             if ($imageArray != "") {

    //                 $imageCheck = $this->productImageValidation($imageArray);

    //                 if ($imageCheck == "true") {

    //                     $i++;

    //                 } else {

    //                     return $imageCheck;

    //                 }

    //             }

    //         }

    //         if ($i == $countOfprimaryVariant) {

    //             return "true";

    //         } else {

    //             return "false";
    //         }
    //     } else {

    //         return "Please provide valid json for primary variant details";
    //     }

    // }

    //product image validation

    public function productImageValidation($product_image)
    {

        $ary = [];
        $extension = [];
        $product_image = json_decode($product_image, true);
        if (!empty($product_image)) {

            foreach ($product_image as $img) {
                $ary[] = $img['image'];
                $extension[] = pathinfo($img['image'], PATHINFO_EXTENSION);
            }
        }
        if (array_filter($ary)) {



            $extension_array = ['jpeg', 'png', 'jpg', 'webp'];

            $array_diff = array_diff(array_filter($extension), $extension_array);

            if ($array_diff) {

                $message = "Only JPG,JPEG,PNG,WEBP formats allowed for product image";

                return $message;
            } else {

                return "true";
            }
        } else {

            $message = "Atleast One product image is required";

            return $message;
        }
    }

    //view
    public function getdefaultImages_allImages($gTImage)
    {
        $imG = [];
        if (!empty($gTImage)) {
            foreach ($gTImage as $im) {
                $ary = [];
                $ary['index'] = $im['index'];
                $ary['url'] = ($im['image'] != '') ? env('APP_URL') . env('ECOMMERCE_URL') . $im['image'] : env('APP_URL') . "avatar.jpg";
                $ary['image'] = $im['image'];
                $imG[] = $ary;
            }
        }
        return $imG;
    }

    public function varientDetailsValidation($variant_details)
    {

        $data = json_decode($variant_details, true);

        if (!empty($data)) {

            foreach ($data as $d) {

                $validator = Validator::make($d, [
                    'mrp' => 'required|numeric|min:1',
                    'selling_price' => 'required|numeric|min:1|lte:mrp',
                    'quantity' => 'required|numeric',
                    'variant_code' => 'required',
                    'weight' => ['required','numeric','regex:/^\d+(\.\d{1})?$/'],

                ]);

                if ($validator->fails()) {
                    $errors = $validator->errors();
                    return $errors->first();
                }
            }
        }
    }

    public function varientAttributeValidate($variant_details)
    {

        $det = [];

        $data = json_decode($variant_details, true);

        if (!empty($data)) {

            foreach ($data as $d) {

                $det = $this->valFun($d['variant_attributes']);

                if ($det) {

                    return $det->first();
                }
            }
        }
    }

    public function valFun($data)
    {

        foreach ($data as $dt) {

            $validator = Validator::make($dt, [
                'variant_type_id' => 'required|numeric',
                'value' => 'required',
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors();
                return $errors;
            }
        }
    }

    public function thumbnailValidation($thumbnailImage)
    {

        if ($thumbnailImage != "") {

            $Extension = pathinfo($thumbnailImage, PATHINFO_EXTENSION);

            $extension_ary = ['jpeg', 'png', 'jpg', 'webp'];

            if (in_array($Extension, $extension_ary)) {

                return "true";
            } else {

                $message = "Only JPG,JPEG,PNG,WEBP formats allowed for thumbnail image";

                return $message;
            }
        } else {

            $message = "Thumbnail image field is required";

            return $message;
        }
    }

    public function getrelated_products($proId)
    {
        $related = RelatedProduct::where('p.is_related_product_available', 1)->where('related_products.product_id', $proId)->where('related_products.status', '!=', 2)
            ->select('service.service_name', 'product.product_name', 'c.category_name', 'product.mrp', 'product.selling_price', 'product.first_copy_selling_price', 'related_products.service_id', 'related_products.product_id_related', 'product.thumbnail_image', 'product_variant.quantity')
            ->leftjoin('service', 'service.service_id', '=', 'related_products.service_id')
            ->leftjoin('product as p', 'p.product_id', '=', 'related_products.product_id')
            ->leftjoin('product', 'product.product_id', '=', 'related_products.product_id_related')
            ->leftjoin('category as c', 'c.category_id', '=', 'product.category_id')
            ->leftJoin('product_variant', function ($leftJoin) {
                $leftJoin->on('product_variant.product_id', '=', 'product.product_id')
                    ->where('product_variant.set_as_default', 1);
            })->get();
        $RelatedPro = [];
        if (!empty($related)) {
            foreach ($related as $rp) {
                $ary = [];
                $ary['service_name'] = $rp['service_name'];
                $ary['product_name'] = $rp['product_name'];
                $ary['category_name'] = $rp['category_name'];
                $ary['service_id'] = $rp['service_id'];
                $ary['product_id_related'] = $rp['product_id_related'];
                if ($rp['service_id'] == 1) {
                    $ary['thumbnail_url'] = ($rp['thumbnail_image'] != '') ? env('APP_URL') . env('PASSPORTSIZEPHOTO_URL') . $rp['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['mrp'] = $rp['mrp'];
                    $ary['selling_price'] = $rp['selling_price'];
                    $amunt = (($ary['mrp'] - $ary['selling_price']) /  $ary['mrp']) * 100;
                    $ary['offer_percentage'] =  round($amunt . '' . "%", 2);
                }
                if ($rp['service_id'] == 2) {
                    $ary['thumbnail_url'] = ($rp['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOPRINT_URL') . $rp['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['mrp'] = $rp['mrp'];
                    $ary['selling_price'] = $rp['first_copy_selling_price'];
                    $amunt = (($ary['mrp'] - $ary['selling_price']) /  $ary['mrp']) * 100;
                    $ary['offer_percentage'] =  round($amunt . '' . "%", 2);
                }
                if ($rp['service_id'] == 3) {
                    $ary['thumbnail_url'] = ($rp['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $rp['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['mrp'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "mrp");
                    $ary['selling_price'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "selling");
                    $amunt = (($ary['mrp'] - $ary['selling_price']) /  $ary['mrp']) * 100;
                    $ary['offer_percentage'] =  round($amunt . '' . "%", 2);
                }
                if ($rp['service_id'] == 4) {
                    $ary['thumbnail_url'] = ($rp['thumbnail_image'] != '') ? env('APP_URL') . env('PERSONALIZED_URL') . $rp['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['mrp'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "mrp");
                    $ary['selling_price'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "selling");
                    $amunt = (($ary['mrp'] - $ary['selling_price']) /  $ary['mrp']) * 100;
                    $ary['offer_percentage'] =  round($amunt . '' . "%", 2);
                }
                if ($rp['service_id'] == 5) {
                    $ary['thumbnail_url'] = ($rp['thumbnail_image'] != '') ? env('APP_URL') . env('ECOMMERCE_URL') . $rp['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['mrp'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "mrp");
                    $ary['selling_price'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "selling");
                    $amunt = (($ary['mrp'] - $ary['selling_price']) /  $ary['mrp']) * 100;
                    $ary['offer_percentage'] =  round($amunt . '' . "%", 2);
                }
                if ($rp['service_id'] == 6) {
                    $ary['thumbnail_url'] = ($rp['thumbnail_image'] != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $rp['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['mrp'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "mrp");
                    $ary['selling_price'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "selling");
                    $amunt = (($ary['mrp'] - $ary['selling_price']) /  $ary['mrp']) * 100;
                    $ary['offer_percentage'] =  round($amunt . '' . "%", 2);
                }
                $ary['thumbnail_image'] = $rp['thumbnail_image'];
                $ary['quantity'] = $rp['quantity'];
                $RelatedPro[] = $ary;
            }
        }
        return $RelatedPro;
    }

    public function photoframeProductAmountDetails($id, $slug)
    {

        $value = "";

        $amountDetails = ProductCatalogue::where('product.product_id', $id)->select('product.product_id', 'product.created_on', 'product.product_code', 'product.product_name', 'product_variant.selling_price', 'product_variant.mrp', 'product.is_publish', 'product.status')
            ->leftJoin('product_variant', function ($leftJoin) {
                $leftJoin->on('product_variant.product_id', '=', 'product.product_id')
                    ->where('product_variant.set_as_default', '=', 1);
            })->where('product.status', 1)->first();

        if (!empty($amountDetails)) {

            if ($slug == "mrp") {

                $value = $amountDetails->mrp;
            }

            if ($slug == "selling") {

                $value = $amountDetails->selling_price;
            }
        } else {

            $value = 0;
        }

        return $value;
    }

    public function getPrimaryVariantDetails($primaryVariantDetails)
    {

        $primaryDataArray = [];
        $resultArray = [];

        $primaryData = json_decode($primaryVariantDetails, true);

        if (!empty($primaryData)) {

            foreach ($primaryData as $data) {

                $primaryDataArray['product_image'] = $this->getProductImage($data['product_image']);
                $primaryDataArray['variant_type_id'] = $data['variant_type_id'];
                $primaryDataArray['variant_type'] = $this->getVariantTypeName($data['variant_type_id']);
                $primaryDataArray['image'] = $data['image'];
                $primaryDataArray['image_url'] = ($data['image'] != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $data['image'] : env('APP_URL') . "avatar.jpg";
                $primaryDataArray['label'] = $data['label'];
                $resultArray[] = $primaryDataArray;
            }
        }

        return $resultArray;
    }

    public function getProductImage($productImageData)
    {

        $imageArray = [];
        $resultArray = [];

        $productImageData = json_decode(json_encode($productImageData), true);

        if (!empty($productImageData)) {

            $sortedImages = collect($productImageData)->sortBy('index')->values()->all();

            foreach ($sortedImages as $data) {

                $imageArray['image'] = $data['image'];
                $imageArray['image_url'] = ($data['image'] != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $data['image'] : env('APP_URL') . "avatar.jpg";
                $imageArray['index'] = $data['index'];
                $resultArray[] = $imageArray;
            }
        }

        return $resultArray;
    }

    // public function getVariant($VariantData)
    // {

    //     $variantArray = [];
    //     $resultArray = [];

    //     $VariantData = json_decode(json_encode($VariantData), true);

    //     if (!empty($VariantData)) {

    //         foreach ($VariantData as $data) {

    //             $variantArray['variant_type_id'] = $data['variant_type_id'];
    //             $variantArray['image'] = $data['image'];
    //             $variantArray['image_url'] = ($data['image'] != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $data['image'] : env('APP_URL') . "avatar.jpg";
    //             $variantArray['label'] = $data['label'];
    //             $resultArray[] = $variantArray;

    //         }

    //     }

    //     return $resultArray;

    // }

    public function getVariantDetails($productId)
    {

        $variantArray = [];
        $resultArray = [];

        $productVariant = ProductVariant::where('product_id', $productId)->get();

        $variantDetails = json_decode($productVariant, true);

        if (!empty($variantDetails)) {

            foreach ($variantDetails as $data) {

                $variantArray['variant_attributes'] = $this->getGlobelVariantDetails(json_decode($data['variant_attributes'], true));
                $variantArray['product_variant_id'] = $data['product_variant_id'];
                $variantArray['variant_code'] = $data['variant_code'];
                $variantArray['mrp'] = $data['mrp'];
                $variantArray['selling_price'] = $data['selling_price'];
                $amunt = (($data['mrp'] - $data['selling_price']) /  $data['mrp']) * 100;
                $variantArray['offer_percentage'] =  round($amunt . '' . "%", 2);
                $variantArray['quantity'] = $data['quantity'];
                $variantArray['set_as_default'] = $data['set_as_default'];
                $variantArray['index'] = $data['internal_variant_id'];
                $variantArray['variant_options'] = json_decode($data['variant_options'], true);
                $variantArray['weight'] = $data['weight'];
                $resultArray[] = $variantArray;
            }
        }

        return $resultArray;
    }

    public function getVariantTypeName($variantTypeId)
    {

        $VariantType = VariantType::where('variant_type_id', $variantTypeId)->first();

        if (!empty($VariantType)) {

            return $VariantType->variant_type;
        } else {

            $value = "";

            return $value;
        }
    }

    public function getGlobelVariantDetails($VariantAttributeData)
    {

        $variantArray = [];
        $resultArray = [];

        if (!empty($VariantAttributeData)) {

            foreach ($VariantAttributeData as $data) {

                $variantArray['variant_type_id'] = $data['variant_type_id'];
                $variantArray['variant_type'] = $this->getVariantTypeName($data['variant_type_id']);
                $variantArray['value'] = $data['value'];
                $resultArray[] = $variantArray;
            }
        }

        return $resultArray;
    }

    public function variantCodeExistCheck($variantDetails)
    {

        $j = 0;

        $variantDetails = json_decode($variantDetails, true);

        $count = count($variantDetails);

        $dupp = [];

        if (!empty($variantDetails)) {

            for ($i = 0; $i < count($variantDetails); $i++) {
                $dupp[] = $variantDetails[$i]['variant_code'];
            }

            $dups = array();
            foreach (array_count_values($dupp) as $val => $c) {
                if ($c > 1) {
                    $dups[] = $val;
                }
            }

            if (empty($dups)) {

                foreach ($variantDetails as $data) {

                    $productVariant = ProductVariant::where('variant_code', $data['variant_code'])
                        ->first();

                    // if ($data['product_variant_id'] == 0) {

                    //     $productVariant = ProductVariant::where('variant_code', $data['variant_code'])->first();
                    // }

                    if (!empty($productVariant)) {

                        $message = "Variant code already exist";

                        return $message;
                    } else {

                        $j++;
                    }
                }

                if ($j == $count) {

                    return "true";
                } else {

                    return "false";
                }
            } else {

                $message = "Variant code must be unique";

                return $message;
            }
        }
    }


    //update
    public function variantCodeExistCheckUpdate($variantDetails, $proId)
    {

        $j = 0;

        $variantDetails = json_decode($variantDetails, true);

        $count = count($variantDetails);

        $dupp = [];

        if (!empty($variantDetails)) {

            for ($i = 0; $i < count($variantDetails); $i++) {
                $dupp[] = $variantDetails[$i]['variant_code'];
            }

            $dups = array();
            foreach (array_count_values($dupp) as $val => $c) {
                if ($c > 1) {
                    $dups[] = $val;
                }
            }

            if (empty($dups)) {

                foreach ($variantDetails as $data) {

                    $productVariant = ProductVariant::where('variant_code', $data['variant_code'])
                        ->where('product_id', '!=', $proId)
                        ->first();

                    // if ($data['product_variant_id'] == 0) {

                    //     $productVariant = ProductVariant::where('variant_code', $data['variant_code'])->first();
                    // }

                    if (!empty($productVariant)) {

                        $message = "Variant code already exist";

                        return $message;
                    } else {

                        $j++;
                    }
                }

                if ($j == $count) {

                    return "true";
                } else {

                    return "false";
                }
            } else {

                $message = "Variant code must be unique";

                return $message;
            }
        }
    }
}
