<?php

namespace App\Http\Traits;

use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Models\ProductCatalogue;
use App\Models\ProductVariant;
use App\Models\RelatedProduct;
use App\Models\VariantType;
use Illuminate\Support\Facades\log;
use Illuminate\Support\Facades\Validator;

trait SelfieAlbumTrait
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
                    $variantInsert->mrp = $variant['mrp'];
                    $variantInsert->selling_price = $variant['selling_price'];   
                    $variantInsert->set_as_default = $variant['set_as_default'];
                    $variantInsert->variant_type_id = $variant['variant_type_id'];
                    $variantInsert->image = $variant['image'];
                    $variantInsert->label = $variant['label'];
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
            Log::channel("selfiealbum")->error($exception);
            Log::channel("selfiealbum")->error('** error occure while inserting data in product varient table **');
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
            Log::channel("selfiealbum")->error($exception);
            Log::channel("selfiealbum")->error('** error occure while inserting data in related product table **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function productVariantUpdate($variant_details, $product_id)
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
                    $variantUpdate->mrp = $variant['mrp'];
                    $variantUpdate->selling_price = $variant['selling_price'];
                    $variantUpdate->set_as_default = $variant['set_as_default'];
                    $variantUpdate->variant_type_id = $variant['variant_type_id'];
                    $variantUpdate->image = $variant['image'];
                    $variantUpdate->label = $variant['label'];
                    $variantUpdate->internal_variant_id = $variant['index'];
                    $variantUpdate->variant_options = json_encode($variant['variant_options'], true);
                    $variantUpdate->weight = $variant['weight'];
                    $variantUpdate->updated_on = Server::getDateTime();

                    if ($variantUpdate->save()) {

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
            Log::channel("selfiealbum")->error($exception);
            Log::channel("selfiealbum")->error('** error occure while inserting data in product varient table **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }

    }

    //primary variants validation

    public function validateSelectedVarients($primaryVariant)
    {

        $i = 0;
        $imageArray = "";

        $primaryVariantArry = json_decode($primaryVariant, true);

        if (!empty($primaryVariantArry)) {

            $countOfprimaryVariant = count($primaryVariantArry);

            foreach ($primaryVariantArry as $prmvariant) {

                if ($prmvariant['image'] != '') {
                    $Extension =  pathinfo($prmvariant['image'], PATHINFO_EXTENSION);
                    $extension_ary = ['jpeg', 'png', 'jpg', 'webp'];
                    if (in_array($Extension, $extension_ary)) {
                        $prmvariant['image'];
                    } else {
                            $message = "Only JPG,JPEG,PNG,WEBP formats allowed for image";
                            return $message;
                    }
                }

                $validator = Validator::make($prmvariant, [
                    // 'mrp' => 'required|numeric|min:1',
                    // 'selling_price' => 'required|numeric|min:1|lte:mrp',

                    'label' => 'required',
                    // 'quantity' => 'required|numeric',
                    // 'customized_price' => 'required|numeric',
                    // 'variant_code' => 'required',

                ]);

                if ($validator->fails()) {
                    $errors = $validator->errors();
                    return $errors->first();
                }

                $imageArray = $prmvariant['product_image'];

                if ($imageArray != "") {

                    $imageCheck = $this->productImageValidation($imageArray);

                    if ($imageCheck == "true") {

                        $i++;

                    } else {

                        return $imageCheck;

                    }

                }

            }

            if ($i == $countOfprimaryVariant) {

                return "true";

            } else {

                return "false";
            }
        } else {

            return "Please provide valid json for primary variant details";
        }

    }

    public function productImageValidation($imageArray)
    {

        $ary = [];
        $extension = [];

        if (!empty($imageArray)) {
            foreach ($imageArray as $img) {

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

    public function varientDetailsValidation($variant_details)
    {

        $data = json_decode($variant_details, true);

        if (!empty($data)) {

            foreach ($data as $d) {

                $validator = Validator::make($d, [
                    'mrp' => 'required|numeric|min:1',
                    'selling_price' => 'required|numeric|min:1|lte:mrp',
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

            $message = "Thumbnail Image Field is Required";

            return $message;

        }

    }

    public function getrelated_products($proId)
    {
        $related = RelatedProduct::where('p.is_related_product_available', 1)->where('related_products.product_id', $proId)->where('related_products.status','!=', 2)
            ->select('service.service_name', 'product.product_name', 'product.mrp', 'product.selling_price', 'product.first_copy_selling_price', 'related_products.service_id', 'related_products.product_id_related', 'product.thumbnail_image')
            ->leftjoin('service', 'service.service_id', '=', 'related_products.service_id')
            ->leftjoin('product as p', 'p.product_id', '=', 'related_products.product_id')
            ->leftjoin('product', 'product.product_id', '=', 'related_products.product_id_related')->get();
        $RelatedPro = [];
        if (!empty($related)) {
            foreach ($related as $rp) {
                $ary = [];
                $ary['service_name'] = $rp['service_name'];
                $ary['product_name'] = $rp['product_name'];
                $ary['mrp'] = $rp['mrp'];
                $ary['selling_price'] = !empty($rp['selling_price']) ? $rp['selling_price'] : $rp['first_copy_selling_price'];
                $ary['service_id'] = $rp['service_id'];
                $ary['product_id_related'] = $rp['product_id_related'];
                if ($rp['service_id'] == 1) {
                    $ary['thumbnail_url'] = ($rp['thumbnail_image'] != '') ? env('APP_URL') . env('PASSPORTSIZEPHOTO_URL') . $rp['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['mrp'] = $rp['mrp'];
                    $ary['selling_price'] = $rp['selling_price'];
                }
                if ($rp['service_id'] == 2) {
                    $ary['thumbnail_url'] = ($rp['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOPRINT_URL') . $rp['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['mrp'] = $rp['mrp'];
                    $ary['selling_price'] = $rp['first_copy_selling_price'];
                }
                if ($rp['service_id'] == 3) {
                    $ary['thumbnail_url'] = ($rp['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $rp['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['mrp'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "mrp");
                    $ary['selling_price'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "selling");
                }
                if ($rp['service_id'] == 4) {
                    $ary['thumbnail_url'] = ($rp['thumbnail_image'] != '') ? env('APP_URL') . env('PERSONALIZED_URL') . $rp['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['mrp'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "mrp");
                    $ary['selling_price'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "selling");
                }
                if ($rp['service_id'] == 5) {
                    $ary['thumbnail_url'] = ($rp['thumbnail_image'] != '') ? env('APP_URL') . env('ECOMMERCE_URL') . $rp['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['mrp'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "mrp");
                    $ary['selling_price'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "selling");
                }
                if ($rp['service_id'] == 6) {
                    $ary['thumbnail_url'] = ($rp['thumbnail_image'] != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $rp['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['mrp'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "mrp");
                    $ary['selling_price'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "selling");
                }
                $ary['thumbnail_image'] = $rp['thumbnail_image'];
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
                $primaryDataArray['image_url'] = ($data['image'] != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $data['image'] : env('APP_URL') . "avatar.jpg";
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
    //             $variantArray['image_url'] = ($data['image'] != '') ? env('APP_URL') . env('selfiealbum_URL') . $data['image'] : env('APP_URL') . "avatar.jpg";
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
                $variantArray['quantity'] = $data['quantity'];
                $variantArray['set_as_default'] = $data['set_as_default'];
                $variantArray['variant_type_id'] = $data['variant_type_id'];
                $variantArray['variant_type'] = $this->getVariantTypeName($data['variant_type_id']);
                $variantArray['image'] = $data['image'];
                $variantArray['image_url'] = ($data['image'] != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $data['image'] : env('APP_URL') . "avatar.jpg";
                $variantArray['label'] = $data['label'];
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

                    if ($data['product_variant_id'] > 0) {

                        $productVariant = ProductVariant::where('variant_code', $data['variant_code'])
                            ->where('product_variant_id', '!=', $data['product_variant_id'])
                            ->first();

                    }

                    if ($data['product_variant_id'] == 0) {

                        $productVariant = ProductVariant::where('variant_code', $data['variant_code'])->first();

                    }

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