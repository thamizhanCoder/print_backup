<?php

namespace App\Http\Traits;

use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Models\BulkOrderQuote;
use App\Models\BulkOrderQuoteDetails;
use App\Models\DeliveryManagement;
use App\Models\ProductCatalogue;
use App\Models\ProductVariant;
use App\Models\Service;
use App\Models\TermsAndConditions;
use App\Models\VariantType;
use Illuminate\Support\Facades\log;
use Illuminate\Support\Facades\Validator;

trait QuoteTrait
{

    public function quoteDetailsValidation($quote_details)
    {

        $data = json_decode($quote_details, true);

        if (!empty($data)) {

            foreach ($data as $d) {

                $validator = Validator::make($d, [
                    'service_id' => 'required',
                    'product_id' => 'required',
                    'rate' => 'required',
                    'quantity' => 'required',
                    'discount_percentage' => 'required',
                    'discount_amount' => 'required',
                    'taxable_amount' => 'required',
                    'amount' => 'required',
                ]);

                if ($validator->fails()) {
                    $errors = $validator->errors();
                    return $errors->first();
                }
            }
        }
    }

    public function quoteDetailsInsert($quoteDetails, $quoteId, $deliveryCharge)
    {

        try {

            $i = 0;

            $quoteDetailsArray = json_decode($quoteDetails, true);

            if (!empty($quoteDetailsArray)) {

                $quoteDetailsArrayCount = count($quoteDetailsArray);

                foreach ($quoteDetailsArray as $quoteDetail) {

                    $quote_data = new BulkOrderQuoteDetails();
                    $quote_data->bulk_order_quote_id = $quoteId;
                    $quote_data->service_id = $quoteDetail['service_id'];
                    $quote_data->product_id = $quoteDetail['product_id'];
                    $quote_data->product_variant_id = $quoteDetail['product_variant_id'];
                    $quote_data->rate = $quoteDetail['rate'];
                    $quote_data->quantity = $quoteDetail['quantity'];
                    $quote_data->discount_percentage = $quoteDetail['discount_percentage'];
                    $quote_data->discount_amount = $quoteDetail['discount_amount'];
                    $quote_data->taxable_amount = $quoteDetail['taxable_amount'];
                    $quote_data->amount = $quoteDetail['amount'];
                    $quote_data->delivery_charge = $quoteDetail['delivery_charge'];
                    $quote_data->is_customized = $quoteDetail['is_customized'];
                    $quote_data->is_delivery_charge = $quoteDetail['delivery_charge'];

                    $quote_data->gross_amount = $quoteDetail['gross_amount'];
                    $quote_data->cgst_percentage = $quoteDetail['cgst_percentage'];
                    $quote_data->cgst_amount = $quoteDetail['cgst_amount'];
                    $quote_data->sgst_percentage = $quoteDetail['sgst_percentage'];
                    $quote_data->sgst_amount = $quoteDetail['sgst_amount'];
                    $quote_data->igst_percentage = $quoteDetail['igst_percentage'];
                    $quote_data->igst_amount = $quoteDetail['igst_amount'];


                    $termsDetails = TermsAndConditions::where('service_id', $quoteDetail['service_id'])->first();
                    $quote_data->terms_and_conditions = !empty($termsDetails) ? $termsDetails->description : null;

                    $slabDetails = DeliveryManagement::where('service_id', $quoteDetail['service_id'])->first();
                    $quote_data->delivery_slab_details = !empty($slabDetails) ? $slabDetails->slab_details : null;

                    if ($quoteDetail['service_id'] == 1 || $quoteDetail['service_id'] == 2) {
                        $product = ProductCatalogue::where('product_id', $quoteDetail['product_id'])->leftjoin('photo_print_setting', 'photo_print_setting.photo_print_settings_id', '=', 'product.print_size')->leftjoin('gst_percentage', 'gst_percentage.gst_percentage_id', '=', 'product.gst_percentage')->select('product.*', 'photo_print_setting.width', 'photo_print_setting.height', 'gst_percentage.gst_percentage as gst_value')->first();
                        $quote_data->product_name  = $product->product_name;
                        $quote_data->product_code  = $product->product_code;
                        $quote_data->print_size  = $product->print_size;
                        $quote_data->customer_description = $product->customer_description;
                        $quote_data->designer_description = $product->designer_description;
                        $quote_data->product_description = $product->product_description;
                        $quote_data->product_specification = $product->product_specification;
                        $quote_data->p_mrp = $product->mrp;
                        $quote_data->p_selling_price = $product->selling_price;
                        $quote_data->first_copy_selling_price = $product->first_copy_selling_price;
                        $quote_data->additional_copy_selling_price = $product->additional_copy_selling_price;
                        $quote_data->thumbnail_image = $product->thumbnail_image;
                        $quote_data->photoprint_width = $product->width;
                        $quote_data->photoprint_height = $product->height;
                        $quote_data->gst_value  = $product->gst_value;
                        $quote_data->additional_price  = $product->additional_copy_selling_price;
                        $quote_data->product_weight = $product->weight;
                        // $DeliveryCalculationAmount =  $this->DeliveryCalculationAmount($quoteDetail['quantity'],  $product->weight, $slabDetails->slab_details);
                        // $quote_data->delivery_charge = sprintf("%.2f", $DeliveryCalculationAmount);
                    }

                    if ($quoteDetail['service_id'] == 3 || $quoteDetail['service_id'] == 4 || $quoteDetail['service_id'] == 5 || $quoteDetail['service_id'] == 6) {
                        $product = ProductVariant::where('product_variant_id', $quoteDetail['product_variant_id'])->leftjoin('product', 'product.product_id', '=', 'product_variant.product_id')
                            ->leftjoin('category', 'category.category_id', '=', 'product.category_id')
                            ->leftjoin('gst_percentage', 'gst_percentage.gst_percentage_id', '=', 'product.gst_percentage')
                            ->leftjoin('variant_type', 'variant_type.variant_type_id', '=', 'product_variant.variant_type_id')->select('product_variant.*', 'product.product_name', 'product.product_code', 'product.thumbnail_image', 'product.customer_description', 'product.designer_description', 'product.product_description', 'product.product_specification', 'product.service_id', 'product.is_customized', 'category.category_name', 'gst_percentage.gst_percentage as gst_value', 'variant_type.variant_type as variant_type_name', 'product.frame_details', 'product.label_name_details', 'product.no_of_images')->first();

                        $productVariantDetails = ProductVariant::where('product_variant_id', $quoteDetail['product_variant_id'])->first();
                        $quote_data->product_name  = $product->product_name;
                        $quote_data->product_code  = $product->product_code;
                        $quote_data->thumbnail_image  = $product->thumbnail_image;
                        $quote_data->customer_description  = $product->customer_description;
                        $quote_data->designer_description  = $product->designer_description;
                        $quote_data->product_description  = $product->product_description;
                        $quote_data->product_specification  = $product->product_specification;
                        $quote_data->variant_code  = $product->variant_code;
                        $quote_data->pv_mrp  = $product->mrp;
                        $quote_data->pv_selling_price  = $product->selling_price;
                        $quote_data->pv_variant_attributes  = $product->variant_attributes;
                        $quote_data->customized_price  = $product->customized_price;
                        // $quote_data->is_customized  = $product->is_customized;
                        $quote_data->pv_is_customized  = $product->is_customized;
                        $quote_data->category_name  = $product->category_name;
                        $quote_data->gst_value  = $product->gst_value;
                        $quote_data->variant_type_name  = $product->variant_type_name;
                        $quote_data->variant_label  = $product->label;
                        $quote_data->product_weight = $product->weight;
                        $quote_data->frame_details = $product->frame_details;
                        $quote_data->label_name_details = $product->label_name_details;
                        $quote_data->no_of_images = $product->no_of_images;
                        $quote_data->product_variant_details = $productVariantDetails;
                        // $DeliveryCalculationAmount =  $this->DeliveryCalculationAmount($quoteDetail['quantity'],  $product->weight, $slabDetails->slab_details);
                        // $quote_data->delivery_charge = sprintf("%.2f", $DeliveryCalculationAmount);
                    }

                    $quote_data->created_on  = Server::getDateTime();
                    $quote_data->created_by  = JwtHelper::getSesEmpUserId();

                    if ($quote_data->save()) {

                        $quoteDetailsIds[] = $quote_data->bulk_order_quote_details_id;

                        $i++;
                    } else {

                        $i--;
                    }
                }
            }

            if (!empty($quoteDetailsIds)) {
                $GetQuoteDetails = BulkOrderQuoteDetails::whereIn('bulk_order_quote_details_id', $quoteDetailsIds)->get();
                if (!empty($GetQuoteDetails)) {
                    foreach ($GetQuoteDetails as $quoteSavedDetails) {
                        $ary = [];
                        $ary['bulk_order_quote_details_id'] = $quoteSavedDetails['bulk_order_quote_details_id'];
                        $ary['weight'] = $quoteSavedDetails['product_weight'];
                        $ary['delivery_slab_details'] = json_decode($quoteSavedDetails['delivery_slab_details'], true);
                        $ary['quantity'] = $quoteSavedDetails['quantity'];
                        // $DeliveryCalculationAmount =  $this->DeliveryCalculationAmount($quoteSavedDetails['quantity'], $quoteSavedDetails['product_weight'], $quoteSavedDetails['delivery_slab_details']);
                        // $ary['delivery_charge_amount'] = sprintf("%.2f", $DeliveryCalculationAmount);
                        $ary['delivery_charge_amount'] = $quoteSavedDetails['delivery_charge'];

                        $final[] = $ary;
                    }
                }

                $totalAmount = array_sum(array_column($final, 'delivery_charge_amount'));

                if ($totalAmount == $deliveryCharge) {
                    foreach ($GetQuoteDetails as $res) {
                        $updateQuoteDetails = BulkOrderQuoteDetails::find($res['bulk_order_quote_details_id']);
                        $updateQuoteDetails->delivery_charge = $res['delivery_charge'];
                        $updateQuoteDetails->updated_on  = Server::getDateTime();
                        $updateQuoteDetails->updated_by  = JwtHelper::getSesEmpUserId();
                        $updateQuoteDetails->save();
                    }
                } else {

                    // Calculate the proportion each amount contributes to the total
                    $proportions = array_map(function ($item) use ($totalAmount) {
                        return $item['delivery_charge_amount'] / $totalAmount;
                    }, $final);

                    // Distribute the required amount based on the proportions
                    $newAmounts = array_map(function ($proportion) use ($deliveryCharge) {
                        // return round($proportion * $deliveryCharge);
                        // return $proportion * $deliveryCharge;
                        return  sprintf("%.2f", $proportion * $deliveryCharge);
                    }, $proportions);

                    // If the sum of new amounts is less than the required amount,
                    // distribute the remaining amount among the new amounts
                    $remainingAmount = $deliveryCharge - array_sum($newAmounts);
                    if ($remainingAmount > 0) {
                        // Add the remaining amount to the first original amount
                        $newAmounts[0] += $remainingAmount;
                    }

                    // Prepare the result array with order_items_id and order_amount
                    $result = [];
                    foreach ($final as $key => $originalAmount) {
                        $result[] = [
                            'bulk_order_quote_details_id' => $originalAmount['bulk_order_quote_details_id'],
                            'delivery_charge_amount' => $newAmounts[$key]
                        ];
                    }

                    if (!empty($result)) {
                        foreach ($result as $res) {
                            $updateQuoteDetails = BulkOrderQuoteDetails::find($res['bulk_order_quote_details_id']);
                            $updateQuoteDetails->delivery_charge = $res['delivery_charge_amount'];
                            $updateQuoteDetails->updated_on  = Server::getDateTime();
                            $updateQuoteDetails->updated_by  = JwtHelper::getSesEmpUserId();
                            $updateQuoteDetails->save();
                        }
                    }
                }
            }

            if ($i == $quoteDetailsArrayCount) {

                return true;
            } else {

                return false;
            }
        } catch (\Exception $exception) {
            Log::channel("quote")->error($exception);
            Log::channel("quote")->error('** error occure while inserting data in quote details table **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function DeliveryCalculationAmount($quantity, $weight, $slabDetails)
    {

        $searchWeight = $weight * $quantity;
        $jsonArray = json_decode($slabDetails, true);
        $foundAmount = null;
        $highestAmount = null;
        foreach ($jsonArray as $item) {
            $from = floatval($item['weight_from']);

            $to = floatval($item['weight_to']);
            $amount = floatval($item['amount']);

            if ($searchWeight >= $from && $searchWeight <= $to) {
                // Value is within the range
                $foundAmount = $amount;
                break;
            }
            // Track the highest amount
            if ($highestAmount === null || $amount > $highestAmount) {
                $highestAmount = $amount;
            }
        }
        if ($foundAmount !== null) {
            return $foundAmount;
        } else {
            return $highestAmount;
        }
    }

    public function productAmountDetails($id, $slug)
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

    public function getPhotoFrameVariantDetails($productId)
    {

        $variantArray = [];
        $resultArray = [];

        $productVariant = ProductVariant::where('product_id', $productId)->get();

        $variantDetails = json_decode($productVariant, true);

        if (!empty($variantDetails)) {

            foreach ($variantDetails as $data) {

                $variantArray['variant_attributes'] = $this->getGlobelVariantDetails(json_decode($data['variant_attributes'], true));
                $variantArray['product_variant_id'] = $data['product_variant_id'];
                $variantArray['mrp'] = $data['mrp'];
                $variantArray['selling_price'] = $data['selling_price'];
                $amunt = (($data['mrp'] - $data['selling_price']) /  $data['mrp']) * 100;
                $variantArray['offer_percentage'] =  round($amunt . '' . "%", 2);
                $variantArray['quantity'] = $data['quantity'];
                $variantArray['set_as_default'] = $data['set_as_default'];
                $variantArray['customized_price'] = $data['customized_price'];
                $variantArray['variant_type_id'] = $data['variant_type_id'];
                $variantArray['variant_type'] = $this->getVariantTypeName($data['variant_type_id']);
                $variantArray['image'] = $data['image'];
                $variantArray['image_url'] = ($data['image'] != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $data['image'] : env('APP_URL') . "avatar.jpg";
                $variantArray['label'] = $data['label'];
                $variantArray['index'] = $data['internal_variant_id'];
                $variantArray['variant_options'] = json_decode($data['variant_options'], true);
                $variantArray['weight'] = $data['weight'];
                $resultArray[] = $variantArray;
            }
        }

        return $resultArray;
    }

    public function getPersonalizedVariantDetails($productId)
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
                // $qtyCheck = AddToCart::where('customer_id', JwtHelper::getSesUserId())->where('product_variant_id', $data['product_variant_id'])->sum('quantity');
                // $variantArray['quantity'] = $data['quantity'] - $qtyCheck;
                $variantArray['quantity'] = $data['quantity'];
                $variantArray['set_as_default'] = $data['set_as_default'];
                $variantArray['customized_price'] = $data['customized_price'] == "0.00" ? null : $data['customized_price'];
                $variantArray['variant_type_id'] = $data['variant_type_id'];
                $variantArray['variant_type'] = $this->getVariantTypeName($data['variant_type_id']);
                $variantArray['image'] = $data['image'];
                $variantArray['image_url'] = ($data['image'] != '') ? env('APP_URL') . env('PERSONALIZED_URL') . $data['image'] : env('APP_URL') . "avatar.jpg";
                $variantArray['label'] = $data['label'];
                $variantArray['index'] = $data['internal_variant_id'];
                $variantArray['variant_options'] = json_decode($data['variant_options'], true);
                $variantArray['weight'] = $data['weight'];
                $resultArray[] = $variantArray;
            }
        }

        return $resultArray;
    }

    public function getEcommerceVariantDetails($productId)
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
                // $variantArray['customized_price'] = $data['customized_price'];
                // $variantArray['variant_type_id'] = $data['variant_type_id'];
                // $variantArray['variant_type'] = $this->getVariantTypeName($data['variant_type_id']);
                // $variantArray['image'] = $data['image'];
                // $variantArray['image_url'] = ($data['image'] != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $data['image'] : env('APP_URL') . "avatar.jpg";
                // $variantArray['label'] = $data['label'];
                $variantArray['index'] = $data['internal_variant_id'];
                $variantArray['variant_options'] = json_decode($data['variant_options'], true);
                $variantArray['weight'] = $data['weight'];
                $resultArray[] = $variantArray;
            }
        }

        return $resultArray;
    }

    public function getSelfieVariantDetails($productId)
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
                // $variantArray['customized_price'] = $data['customized_price'];
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

    public function bulkOrderQuoteDetailsPdf($quoteId)
    {

        $quoteOrderDetails = BulkOrderQuoteDetails::where('bulk_order_quote_details.bulk_order_quote_id', $quoteId)->leftjoin('service', 'service.service_id', '=', 'bulk_order_quote_details.service_id')->leftjoin('bulk_order_quote', 'bulk_order_quote.bulk_order_quote_id', '=', 'bulk_order_quote_details.bulk_order_quote_id')->select('bulk_order_quote_details.*', 'service.service_name', 'bulk_order_quote.billing_state_id')->get();


        if (!empty($quoteOrderDetails)) {
            $final = [];
            $serialNumber = 1;
            foreach ($quoteOrderDetails as $detail) {
                $ary = [];
                $ary['serial_number'] = $serialNumber++;
                $ary['bulk_order_quote_details_id'] = $detail->bulk_order_quote_details_id;
                $ary['service_name'] = $detail->service_name;
                $ary['product_code'] = $detail->product_code;
                $ary['product_name'] = $detail->product_name . ' - ' . $detail->product_code;
                if ($detail->service_id == 1 || $detail->service_id == 2) {
                    $ary['variant_attributes'] = "No variant";
                } else if ($detail->service_id == 3 || $detail->service_id == 5 || $detail->service_id == 6) {
                    
                    $productVariantDetails = json_decode($detail->product_variant_details);
                    $ary['variant_options'] = !empty($productVariantDetails) ? $productVariantDetails->label : "";
                    
                    $variant_attributes = !empty($detail->pv_variant_attributes) ? $this->variantGetDetails(json_decode($detail->pv_variant_attributes, true)) : $ary['variant_options'];
                    // $ary['variant_attributes'] = $variant_attributes;
                    $ary['variant_attributes'] = !empty($ary['variant_options']) ? $ary['variant_options'] . ' , ' . $variant_attributes : $variant_attributes;
                } else if($detail->service_id == 4){
                    $productVariantDetails = json_decode($detail->product_variant_details);
                    $ary['variant_options'] = !empty($productVariantDetails) ? $productVariantDetails->label : "";

                    if($detail->is_customized == 0){
                        $personalizedLabelName = "Regular";
                    } else {
                        $personalizedLabelName = "Customized";
                    }
                    $variant_attributes = !empty($detail->pv_variant_attributes) ? $this->variantGetDetails(json_decode($detail->pv_variant_attributes, true)) : $ary['variant_options'];
                    // $ary['variant_attributes'] = $variant_attributes;
                    if($variant_attributes != []){
                    $ary['variant_attributes'] = !empty($ary['variant_options']) ? $personalizedLabelName. ' , '.$ary['variant_options'] . ' , ' . $variant_attributes : $variant_attributes;
                    } else {
                        $ary['variant_attributes'] = !empty($ary['variant_options']) ? $personalizedLabelName. ' , '.$ary['variant_options'] : "";
                    }
                }
                $ary['photoprint_variants'] = $detail->service_id == 2 ? $detail->photoprint_width . '*' . $detail->photoprint_height : null;
                $ary['rate'] = $detail->rate;
                $ary['quantity'] = $detail->quantity;
                $ary['discount_percentage'] = $detail->discount_percentage;
                $ary['discount_amount'] = $detail->discount_amount;
                $ary['taxable_amount'] = $detail->taxable_amount;
                $ary['gst_value'] = $detail->gst_value;
                $ary['amount'] = $detail->amount;

                $ary['gross_amount'] = $detail->gross_amount;
                if ($detail->billing_state_id == 33) {
                    $ary['cgst_percent'] = $detail->cgst_percentage;
                    $ary['cgst_amount'] = $detail->cgst_amount;
                    $ary['sgst_percent'] = $detail->sgst_percentage;
                    $ary['sgst_amount'] = $detail->sgst_amount;
                    $ary['igst_percent'] = "-";
                    $ary['igst_amount'] = "0";
                } else {
                    $ary['cgst_percent'] = "-";
                    $ary['cgst_amount'] = "0";
                    $ary['sgst_percent'] = "-";
                    $ary['sgst_amount'] = "0";
                    $ary['igst_percent'] = $detail->igst_percentage;
                    $ary['igst_amount'] = $detail->igst_amount;
                }

                // $gst_calc = 1 + ($detail->gst_value / 100 * 1);
                // $exc_gst = $detail->taxable_amount / $gst_calc;
                // $amt = $detail->taxable_amount - $exc_gst;
                // if ($detail->billing_state_id == 33) {
                //     $ary['cgst_percent'] = $detail->gst_value / 2;
                //     // $csgtsgstAmountCalculation = (($detail->taxable_amount * ($detail->gst_value / 2)) / 100);
                //     // $csgtsgstAmount = sprintf("%.2f", $csgtsgstAmountCalculation);
                //     $ary['cgst_amount'] = sprintf("%.2f", $amt / 2);
                //     $ary['sgst_percent'] = $detail->gst_value / 2;
                //     $ary['sgst_amount'] = sprintf("%.2f", $amt / 2);
                //     // $ary['net_amount'] = $ary['taxable_amount'] + $ary['cgst_amount'] + $ary['sgst_amount'];
                //     $ary['igst_percent'] = '-';
                //     $ary['igst_amount'] = '-';
                // } else {
                //     $ary['cgst_percent'] = '-';
                //     $ary['cgst_amount'] = '-';
                //     $ary['sgst_percent'] = '-';
                //     $ary['sgst_amount'] =  '-';
                //     // $igstAmountCalculation = (($detail->taxable_amount * $detail->gst_value) / 100);
                //     // $igstAmount = sprintf("%.2f", $igstAmountCalculation);
                //     $ary['igst_percent'] = $detail->gst_value;
                //     $ary['igst_amount'] = sprintf("%.2f", $amt);
                //     // $ary['net_amount'] = $ary['taxable_amount'] + $ary['igst_amount'];
                // }
                $final[] = $ary;
            }
        }
        return $final;
    }

    public function variantGetdetails($variantDetails)
    {
        // $details = json_decode($details, true);
        // print_r($details);exit;
        if (!empty($variantDetails)) {
            $variantAry = [];
            foreach ($variantDetails as $variantDetail) {
                $variantAry[] = $variantDetail['value'];
            }
            $varinatDetails = implode(", ", $variantAry);
            return $varinatDetails;
        }
    }

    public function bulkOrderQuoteDetails($quoteId)
    {

        $quoteOrderDetails = BulkOrderQuoteDetails::where('bulk_order_quote_details.bulk_order_quote_id', $quoteId)->leftjoin('service', 'service.service_id', '=', 'bulk_order_quote_details.service_id')->leftjoin('bulk_order_quote', 'bulk_order_quote.bulk_order_quote_id', '=', 'bulk_order_quote_details.bulk_order_quote_id')->select('bulk_order_quote_details.*', 'service.service_name', 'bulk_order_quote.billing_state_id')->get();


        if (!empty($quoteOrderDetails)) {
            $final = [];
            foreach ($quoteOrderDetails as $detail) {
                $ary = [];
                $ary['bulk_order_quote_details_id'] = $detail->bulk_order_quote_details_id;
                $ary['service_id'] = $detail->service_id;
                $ary['service_name'] = $detail->service_name;
                $ary['product_id'] = $detail->product_id;
                $ary['product_code'] = $detail->product_code;
                $ary['product_name'] = $detail->product_name;
                $ary['variant_attributes'] = $detail->pv_variant_attributes;
                $ary['product_variant_id'] = $detail->product_variant_id;
                if ($detail->service_id == 3) {
                    $ary['product_variant_details'] = $this->getPhotoFrameVariantDetailsQuoteView($detail->product_variant_details);
                } else if ($detail->service_id == 4) {
                    $ary['product_variant_details'] = $this->getPersonalizedVariantDetailsQuoteView($detail->product_variant_details);
                } else if ($detail->service_id == 5) {
                    $ary['product_variant_details'] = $this->getEcommerceVariantDetailsQuoteView($detail->product_variant_details);
                } else if ($detail->service_id == 6) {
                    $ary['product_variant_details'] = $this->getSelfieVariantDetailsQuoteView($detail->product_variant_details);
                }
                $ary['photoprint_variants'] = $detail->service_id == 2 ? $detail->photoprint_width . '*' . $detail->photoprint_height : null;
                $ary['rate'] = $detail->rate;
                $ary['quantity'] = $detail->quantity;
                $ary['discount_percentage'] = $detail->discount_percentage;
                $ary['discount_amount'] = $detail->discount_amount;
                $ary['taxable_amount'] = $detail->taxable_amount;
                $ary['gst_value'] = $detail->gst_value;
                $ary['amount'] = $detail->amount;
                $ary['first_copy_selling_price'] = $detail->first_copy_selling_price;
                $ary['additional_copy_selling_price'] = $detail->additional_copy_selling_price;
                $ary['delivery_charge'] = $detail->is_delivery_charge;
                $ary['is_customized'] = $detail->is_customized;
                $ary['pv_is_customized'] = $detail->pv_is_customized;
                $ary['product_weight'] = $detail->product_weight;
                $ary['delivery_slab_details'] = $detail->delivery_slab_details;

                $ary['gross_amount'] = $detail->gross_amount;
                $ary['cgst_percent'] = $detail->cgst_percentage;
                $ary['cgst_amount'] = $detail->cgst_amount;
                $ary['sgst_percent'] = $detail->sgst_percentage;
                $ary['sgst_amount'] = $detail->sgst_amount;
                $ary['igst_percent'] = $detail->igst_percentage;
                $ary['igst_amount'] = $detail->igst_amount;

                // $gst_calc = 1 + ($detail['gst_value'] / 100 * 1);
                // $exc_gst = $ary['taxable_amount'] / $gst_calc;
                // $amt = $ary['taxable_amount'] - $exc_gst;

                // if ($detail->billing_state_id == 33) {
                //     $ary['cgst_percent'] = $detail['gst_value'] / 2;
                //     // $csgtsgstAmountCalculation = (($detail->taxable_amount * ($detail['gst_value'] / 2)) / 100);
                //     // $csgtsgstAmount = sprintf("%.2f", $csgtsgstAmountCalculation);
                //     $ary['cgst_amount'] = sprintf("%.2f", $amt / 2);
                //     $ary['sgst_percent'] = $detail['gst_value'] / 2;
                //     $ary['sgst_amount'] = sprintf("%.2f", $amt / 2);
                //     // $ary['net_amount'] = $ary['taxable_amount'] + $ary['cgst_amount'] + $ary['sgst_amount'];
                //     // $igstAmountCalculation = (($detail->taxable_amount * $detail['gst_value']) / 100);
                //     // $igstAmount = sprintf("%.2f", $igstAmountCalculation);
                //     $ary['igst_percent'] = $detail['gst_value'];
                //     $ary['igst_amount'] = sprintf("%.2f", $amt);
                // } else {
                //     // $csgtsgstAmountCalculation = (($detail->taxable_amount * ($detail['gst_value'] / 2)) / 100);
                //     // $csgtsgstAmount = sprintf("%.2f", $csgtsgstAmountCalculation);
                //     $ary['cgst_percent'] = $detail['gst_value'] / 2;
                //     $ary['cgst_amount'] = sprintf("%.2f", $amt / 2);
                //     $ary['sgst_percent'] = $detail['gst_value'] / 2;
                //     $ary['sgst_amount'] =  sprintf("%.2f", $amt / 2);
                //     // $igstAmountCalculation = (($detail->taxable_amount * $detail['gst_value']) / 100);
                //     // $igstAmount = sprintf("%.2f", $igstAmountCalculation);
                //     $ary['igst_percent'] = $detail['gst_value'];
                //     $ary['igst_amount'] = sprintf("%.2f", $amt);
                //     // $ary['net_amount'] = $ary['taxable_amount'] + $ary['igst_amount'];
                // }
                $final[] = $ary;
            }
        }
        return $final;
    }

    public function reviewList($enquiryId, $quoteId)
    {

        $quoteOrderDetails = BulkOrderQuote::where('bulk_order_enquiry_id', $enquiryId)->where('bulk_order_quote_id', '!=', $quoteId)->get();


        if (!empty($quoteOrderDetails)) {
            $final = [];
            foreach ($quoteOrderDetails as $detail) {
                $ary = [];
                $ary['created_on'] = $detail->created_on;
                $ary['bulk_order_quote_id'] = $detail->bulk_order_quote_id;
                $ary['quote_code'] = $detail->quote_code;
                $ary['grand_total'] = $detail->grand_total;
                $ary['status'] = $detail->status;
                $final[] = $ary;
            }
        }
        return $final;
    }

    public function primaryVariantDetailsJson($primaryVariantDetails)
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

            foreach ($productImageData as $data) {

                $imageArray['image'] = $data['image'];
                $imageArray['image_url'] = ($data['image'] != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $data['image'] : env('APP_URL') . "avatar.jpg";
                $imageArray['index'] = $data['index'];
                $resultArray[] = $imageArray;
            }
        }

        return $resultArray;
    }

    public function termsAndCOnditionDetails($quoteId)
    {

        $quoteOrderDetails = BulkOrderQuoteDetails::where('bulk_order_quote_id', $quoteId)->leftjoin('service', 'service.service_id', '=', 'bulk_order_quote_details.service_id')->select('bulk_order_quote_details.*', 'service.service_name')->where('bulk_order_quote_details.terms_and_conditions', '!=', NULL)->groupby('bulk_order_quote_details.service_id')->distinct()->get();

        if (!empty($quoteOrderDetails)) {
            $final = [];
            foreach ($quoteOrderDetails as $detail) {
                $ary = [];
                $ary['service_name'] = $detail->service_name;
                $ary['terms_and_conditions'] = $detail->terms_and_conditions;
                $final[] = $ary;
            }
        }
        return $final;
    }

    public function getServiceNameforList($serviceId)
    {
        $serviceNameClk = json_decode($serviceId, true);
        $serviceClicked = Service::whereIn('service_id', $serviceNameClk)->orderBy('service_id', 'asc')->get();
        $service_name = [];
        foreach ($serviceClicked as $key => $name) {
            $service_name[$key] = $name->service_name;
        }
        return $service_name;
    }

    public function getPhotoFrameVariantDetailsQuoteView($productVariant)
    {

        $variantArray = [];
        $resultArray = [];

        $variantDetails = json_decode($productVariant, true);

        if (!empty($variantDetails)) {

            $variantArray['variant_attributes'] = $this->getGlobelVariantDetails(json_decode($variantDetails['variant_attributes'], true));
            $variantArray['product_variant_id'] = $variantDetails['product_variant_id'];
            $variantArray['mrp'] = $variantDetails['mrp'];
            $variantArray['selling_price'] = $variantDetails['selling_price'];
            $amunt = (($variantDetails['mrp'] - $variantDetails['selling_price']) /  $variantDetails['mrp']) * 100;
            $variantArray['offer_percentage'] =  round($amunt . '' . "%", 2);
            $variantArray['quantity'] = $variantDetails['quantity'];
            $variantArray['set_as_default'] = $variantDetails['set_as_default'];
            $variantArray['customized_price'] = $variantDetails['customized_price'];
            $variantArray['variant_type_id'] = $variantDetails['variant_type_id'];
            $variantArray['variant_type'] = $this->getVariantTypeName($variantDetails['variant_type_id']);
            $variantArray['image'] = $variantDetails['image'];
            $variantArray['image_url'] = ($variantDetails['image'] != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $variantDetails['image'] : env('APP_URL') . "avatar.jpg";
            $variantArray['label'] = $variantDetails['label'];
            $variantArray['index'] = $variantDetails['internal_variant_id'];
            $variantArray['variant_options'] = json_decode($variantDetails['variant_options'], true);
            $variantArray['weight'] = $variantDetails['weight'];
            $resultArray[] = $variantArray;
        }

        return $resultArray;
    }

    public function getPersonalizedVariantDetailsQuoteView($productVariant)
    {

        $variantArray = [];
        $resultArray = [];

        $variantDetails = json_decode($productVariant, true);

        if (!empty($variantDetails)) {

            $variantArray['variant_attributes'] = $this->getGlobelVariantDetails(json_decode($variantDetails['variant_attributes'], true));
            $variantArray['product_variant_id'] = $variantDetails['product_variant_id'];
            $variantArray['variant_code'] = $variantDetails['variant_code'];
            $variantArray['mrp'] = $variantDetails['mrp'];
            $variantArray['selling_price'] = $variantDetails['selling_price'];
            $amunt = (($variantDetails['mrp'] - $variantDetails['selling_price']) /  $variantDetails['mrp']) * 100;
            $variantArray['offer_percentage'] =  round($amunt . '' . "%", 2);
            // $qtyCheck = AddToCart::where('customer_id', JwtHelper::getSesUserId())->where('product_variant_id', $variantDetails['product_variant_id'])->sum('quantity');
            // $variantArray['quantity'] = $variantDetails['quantity'] - $qtyCheck;
            $variantArray['quantity'] = $variantDetails['quantity'];
            $variantArray['set_as_default'] = $variantDetails['set_as_default'];
            $variantArray['customized_price'] = $variantDetails['customized_price'] == "0.00" ? null : $variantDetails['customized_price'];
            $variantArray['variant_type_id'] = $variantDetails['variant_type_id'];
            $variantArray['variant_type'] = $this->getVariantTypeName($variantDetails['variant_type_id']);
            $variantArray['image'] = $variantDetails['image'];
            $variantArray['image_url'] = ($variantDetails['image'] != '') ? env('APP_URL') . env('PERSONALIZED_URL') . $variantDetails['image'] : env('APP_URL') . "avatar.jpg";
            $variantArray['label'] = $variantDetails['label'];
            $variantArray['index'] = $variantDetails['internal_variant_id'];
            $variantArray['variant_options'] = json_decode($variantDetails['variant_options'], true);
            $variantArray['weight'] = $variantDetails['weight'];
            $resultArray[] = $variantArray;
        }

        return $resultArray;
    }

    public function getEcommerceVariantDetailsQuoteView($productVariant)
    {

        $variantArray = [];
        $resultArray = [];

        $variantDetails = json_decode($productVariant, true);

        if (!empty($variantDetails)) {

            $variantArray['variant_attributes'] = $this->getGlobelVariantDetails(json_decode($variantDetails['variant_attributes'], true));
            $variantArray['product_variant_id'] = $variantDetails['product_variant_id'];
            $variantArray['variant_code'] = $variantDetails['variant_code'];
            $variantArray['mrp'] = $variantDetails['mrp'];
            $variantArray['selling_price'] = $variantDetails['selling_price'];
            $amunt = (($variantDetails['mrp'] - $variantDetails['selling_price']) /  $variantDetails['mrp']) * 100;
            $variantArray['offer_percentage'] =  round($amunt . '' . "%", 2);
            $variantArray['quantity'] = $variantDetails['quantity'];
            $variantArray['set_as_default'] = $variantDetails['set_as_default'];
            // $variantArray['customized_price'] = $variantDetails['customized_price'];
            // $variantArray['variant_type_id'] = $variantDetails['variant_type_id'];
            // $variantArray['variant_type'] = $this->getVariantTypeName($variantDetails['variant_type_id']);
            // $variantArray['image'] = $variantDetails['image'];
            // $variantArray['image_url'] = ($variantDetails['image'] != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $variantDetails['image'] : env('APP_URL') . "avatar.jpg";
            // $variantArray['label'] = $variantDetails['label'];
            $variantArray['index'] = $variantDetails['internal_variant_id'];
            $variantArray['variant_options'] = json_decode($variantDetails['variant_options'], true);
            $variantArray['weight'] = $variantDetails['weight'];
            $resultArray[] = $variantArray;
        }

        return $resultArray;
    }

    public function getSelfieVariantDetailsQuoteView($productVariant)
    {

        $variantArray = [];
        $resultArray = [];

        $variantDetails = json_decode($productVariant, true);

        if (!empty($variantDetails)) {

            $variantArray['variant_attributes'] = $this->getGlobelVariantDetails(json_decode($variantDetails['variant_attributes'], true));
            $variantArray['product_variant_id'] = $variantDetails['product_variant_id'];
            $variantArray['variant_code'] = $variantDetails['variant_code'];
            $variantArray['mrp'] = $variantDetails['mrp'];
            $variantArray['selling_price'] = $variantDetails['selling_price'];
            $variantArray['quantity'] = $variantDetails['quantity'];
            $variantArray['set_as_default'] = $variantDetails['set_as_default'];
            // $variantArray['customized_price'] = $variantDetails['customized_price'];
            $variantArray['variant_type_id'] = $variantDetails['variant_type_id'];
            $variantArray['variant_type'] = $this->getVariantTypeName($variantDetails['variant_type_id']);
            $variantArray['image'] = $variantDetails['image'];
            $variantArray['image_url'] = ($variantDetails['image'] != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $variantDetails['image'] : env('APP_URL') . "avatar.jpg";
            $variantArray['label'] = $variantDetails['label'];
            $variantArray['index'] = $variantDetails['internal_variant_id'];
            $variantArray['variant_options'] = json_decode($variantDetails['variant_options'], true);
            $variantArray['weight'] = $variantDetails['weight'];
            $resultArray[] = $variantArray;
        }

        return $resultArray;
    }
}
