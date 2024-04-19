<?php

namespace App\Http\Traits;

use App\Helpers\Firebase;
use App\Helpers\GlobalHelper;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Models\BulkOrderQuote;
use App\Models\BulkOrderQuoteDetails;
use App\Models\OrderItems;
use App\Models\PassportSizeUploadModel;
use App\Models\PersonalizedUploadModel;
use App\Models\PhotoFrameLabelModel;
use App\Models\PhotoFrameUploadModel;
use App\Models\PhotoPrintUploadModel;
use App\Models\ProductVariant;
use App\Models\SelfieUploadModel;
use App\Models\UserModel;
use App\Models\VariantType;
use Illuminate\Support\Facades\log;
use Illuminate\Support\Facades\Validator;

trait CreateOrderTrait
{

    public function getQuoteDetails($id)
    {
        return BulkOrderQuote::where('bulk_order_quote_id', $id)->leftjoin('bulk_order_enquiry', 'bulk_order_enquiry.bulk_order_enquiry_id', '=', 'bulk_order_quote.bulk_order_enquiry_id')->select('bulk_order_enquiry.*', 'bulk_order_quote.bulk_order_quote_id', 'bulk_order_quote.quote_code', 'bulk_order_quote.billing_customer_first_name', 'bulk_order_quote.billing_mobile_number', 'bulk_order_quote.billing_alt_mobile_number', 'bulk_order_quote.billing_email', 'bulk_order_quote.billing_gst_no', 'bulk_order_quote.billing_pincode', 'bulk_order_quote.billing_address_1', 'bulk_order_quote.billing_address_2', 'bulk_order_quote.billing_landmark', 'bulk_order_quote.billing_state_id', 'bulk_order_quote.billing_city_id', 'bulk_order_quote.sub_total', 'bulk_order_quote.delivery_charge', 'bulk_order_quote.round_off', 'bulk_order_quote.grand_total')->first();
    }

    public function insertOrderItems($bulkOrderQuoteId, $orderId)
    {
        try {

            $quoteOrderDetails = BulkOrderQuoteDetails::where('bulk_order_quote_id', $bulkOrderQuoteId)->get();

            if (!empty($quoteOrderDetails)) {
                $quoteDetails = json_decode($quoteOrderDetails, true);

                for ($i = 0; $i < count($quoteDetails); $i++) {


                    $order_data = new OrderItems();
                    $order_data->product_variant_id = $quoteDetails[$i]['product_variant_id'];
                    $order_data->order_id = $orderId;
                    $order_data->service_id = $quoteDetails[$i]['service_id'];
                    $order_data->image = $quoteDetails[$i]['image'];
                    $order_data->images = $quoteDetails[$i]['images'];
                    $order_data->background_color = $quoteDetails[$i]['background_color'];
                    $order_data->variant_attributes = $quoteDetails[$i]['variant_attributes'];
                    $order_data->frames = $quoteDetails[$i]['frames'];
                    $order_data->cart_type = 3;
                    $order_data->product_id = $quoteDetails[$i]['product_id'];
                    $order_data->quantity = $quoteDetails[$i]['quantity'];
                    $order_data->unit_price = $quoteDetails[$i]['rate'];
                    $order_data->additional_price = $quoteDetails[$i]['additional_price'];
                    $order_data->taxable_amount = $quoteDetails[$i]['taxable_amount'];
                    $order_data->sub_total = $quoteDetails[$i]['amount'];
                    $order_data->is_cod = 2;
                    $order_data->photoprint_variant = $quoteDetails[$i]['photoprint_variant'];
                    $order_data->delivery_charge = $quoteDetails[$i]['delivery_charge'];
                    $order_data->is_customized = $quoteDetails[$i]['is_customized'];
                    $order_data->product_name = $quoteDetails[$i]['product_name'];
                    $order_data->product_code = $quoteDetails[$i]['product_code'];
                    $order_data->print_size = $quoteDetails[$i]['print_size'];
                    $order_data->customer_description = $quoteDetails[$i]['customer_description'];
                    $order_data->designer_description = $quoteDetails[$i]['designer_description'];
                    $order_data->product_description = $quoteDetails[$i]['product_description'];
                    $order_data->product_specification = $quoteDetails[$i]['product_specification'];
                    $order_data->p_mrp = $quoteDetails[$i]['p_mrp'];
                    $order_data->p_selling_price = $quoteDetails[$i]['p_selling_price'];
                    $order_data->first_copy_selling_price = $quoteDetails[$i]['first_copy_selling_price'];
                    $order_data->additional_copy_selling_price = $quoteDetails[$i]['additional_copy_selling_price'];
                    $order_data->thumbnail_image = $quoteDetails[$i]['thumbnail_image'];
                    $order_data->pv_is_customized = $quoteDetails[$i]['pv_is_customized'];
                    $order_data->variant_code = $quoteDetails[$i]['variant_code'];
                    $order_data->pv_mrp = $quoteDetails[$i]['pv_mrp'];
                    $order_data->pv_selling_price = $quoteDetails[$i]['pv_selling_price'];
                    $order_data->pv_variant_attributes = $quoteDetails[$i]['pv_variant_attributes'];
                    $order_data->customized_price = $quoteDetails[$i]['customized_price'];
                    $order_data->photoprint_width = $quoteDetails[$i]['photoprint_width'];
                    $order_data->photoprint_height = $quoteDetails[$i]['photoprint_height'];
                    $order_data->gst_value = $quoteDetails[$i]['gst_value'];
                    $order_data->category_name = $quoteDetails[$i]['category_name'];
                    $order_data->variant_type_name = $quoteDetails[$i]['variant_type_name'];
                    $order_data->variant_label = $quoteDetails[$i]['variant_label'];
                    $order_data->bulk_order_quote_id = $quoteDetails[$i]['bulk_order_quote_id'];
                    $order_data->discount_percentage = $quoteDetails[$i]['discount_percentage'];
                    $order_data->discount_amount = $quoteDetails[$i]['discount_amount'];
                    $order_data->delivery_slab_details = $quoteDetails[$i]['delivery_slab_details'];
                    $order_data->product_weight = $quoteDetails[$i]['product_weight'];
                    $order_data->frame_details = $quoteDetails[$i]['frame_details'];
                    $order_data->quote_amount = $quoteDetails[$i]['amount'];
                    $order_data->label_name_details = $quoteDetails[$i]['label_name_details'];
                    $order_data->no_of_images = $quoteDetails[$i]['no_of_images'];
                    $order_data->product_variant_details = $quoteDetails[$i]['product_variant_details'];
                    $order_data->gross_amount = $quoteDetails[$i]['gross_amount'];
                    $order_data->cgst_percentage = $quoteDetails[$i]['cgst_percentage'];
                    $order_data->cgst_amount = $quoteDetails[$i]['cgst_amount'];
                    $order_data->sgst_percentage = $quoteDetails[$i]['sgst_percentage'];
                    $order_data->sgst_amount = $quoteDetails[$i]['sgst_amount'];
                    $order_data->igst_percentage = $quoteDetails[$i]['igst_percentage'];
                    $order_data->igst_amount = $quoteDetails[$i]['igst_amount'];
                    $order_data->created_on  = Server::getDateTime();
                    $order_data->created_by  = JwtHelper::getSesEmpUserId();
                    $order_data->save();
                    Log::channel("createOrder")->info("quote insertOrderItems order items save value ::" . json_encode($order_data, true));

                    $total_quantity = array_sum(array_column($quoteDetails, 'quantity'));

                    if ($quoteDetails[$i]['service_id'] == 1) {
                        $photoprintsave = $this->passportSizeSaveDetails($quoteDetails[$i]['image'], $quoteDetails[$i]['background_color'], $order_data);
                    }

                    if ($quoteDetails[$i]['service_id'] == 2) {
                        $photoprintsave = $this->photoprintSaveDetails($quoteDetails[$i]['photoprint_variant'], $order_data);
                    }

                    if ($quoteDetails[$i]['service_id'] == 3) {
                        $photoframedetails = $this->photoframeaddtocartDetail($quoteDetails[$i]['frames']);
                        $photoframesave = $this->photoframesavedetails($photoframedetails, $order_data);
                    }

                    if ($quoteDetails[$i]['service_id'] == 4) {
                        $personalizedDetails = $this->personalizedaddtocartDetail($quoteDetails[$i]['variant_attributes']);
                        $personalizedsave = $this->personalizedSaveDetails($personalizedDetails, $order_data);
                    }

                    if ($quoteDetails[$i]['service_id'] == 6) {
                        $selfiesave = $this->selfieSaveDetails($quoteDetails[$i]['images'], $order_data);
                    }
                }

                return $total_quantity;
            }
        } catch (\Exception $exception) {
            Log::channel("createOrder")->info('** start the insertOrderItems error method **');
            Log::channel("createOrder")->error($exception);
            Log::channel("createOrder")->info('** end the insertOrderItems error method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function bulkOrderQuoteDetails($quoteId)
    {

        $quoteOrderDetails = BulkOrderQuoteDetails::where('bulk_order_quote_id', $quoteId)->leftjoin('service', 'service.service_id', '=', 'bulk_order_quote_details.service_id')->select('bulk_order_quote_details.*', 'service.service_name')->get();


        if (!empty($quoteOrderDetails)) {
            $final = [];
            foreach ($quoteOrderDetails as $detail) {
                $ary = [];
                $ary['bulk_order_quote_details_id'] = $detail->bulk_order_quote_details_id;
                $ary['service_id'] = $detail->service_id;
                $ary['service_name'] = $detail->service_name;
                $ary['product_code'] = $detail->product_code;
                $ary['product_name'] = $detail->product_name;
                $ary['variant_attributes'] = !empty($detail->pv_variant_attributes) ? json_decode($detail->pv_variant_attributes, true) : [];
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
                $ary['first_copy_selling_price'] = $detail->first_copy_selling_price;
                $ary['additional_copy_selling_price'] = $detail->additional_copy_selling_price;
                $ary['quantity'] = $detail->quantity;
                $ary['frame_details'] = !empty($detail->frame_details) ? json_decode($detail->frame_details, true) : [];
                $ary['personalized_label_name_details'] = !empty($detail->label_name_details) ? json_decode($detail->label_name_details, true) : [];
                $ary['is_customized'] = !empty($detail->is_customized) ? $detail->is_customized : 0;
                $ary['no_of_images'] = !empty($detail->no_of_images) ? $detail->no_of_images : 0;
                $final[] = $ary;
            }
        }
        return $final;
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

    public function bulkOrderItemDetails($orderId)
    {

        $orderItemDetails = OrderItems::where('order_items.order_id', $orderId)->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')->select('order_items.*', 'service.service_name')->get();


        if (!empty($orderItemDetails)) {
            $final = [];
            foreach ($orderItemDetails as $detail) {
                $ary = [];
                $ary['order_items_id'] = $detail->order_items_id;
                $ary['service_id'] = $detail->service_id;
                $ary['service_name'] = $detail->service_name;
                $ary['product_code'] = $detail->product_code;
                $ary['product_name'] = $detail->product_name;
                $ary['quantity'] = $detail->quantity;
                $ary['is_customized'] = !empty($detail->is_customized) ? $detail->is_customized : 0;
                $ary['variant_details'] = !empty($detail->pv_variant_attributes) ? json_decode($detail->pv_variant_attributes, true) : [];
                if ($detail->service_id == 3) {
                    $ary['product_variant_details'] = $this->getPhotoFrameVariantDetailsQuoteView($detail->product_variant_details);
                } else if ($detail->service_id == 4) {
                    $ary['product_variant_details'] = $this->getPersonalizedVariantDetailsQuoteView($detail->product_variant_details);
                } else if ($detail->service_id == 5) {
                    $ary['product_variant_details'] = $this->getEcommerceVariantDetailsQuoteView($detail->product_variant_details);
                } else if ($detail->service_id == 6) {
                    $ary['product_variant_details'] = $this->getSelfieVariantDetailsQuoteView($detail->product_variant_details);
                }
                $ary['photoprint_width'] = $detail->photoprint_width;
                $ary['photoprint_height'] = $detail->photoprint_height;
                $ary['first_copy_selling_price'] = $detail->first_copy_selling_price;
                $ary['additional_copy_selling_price'] = $detail->additional_copy_selling_price;
                $ary['background_color'] = $detail->background_color;
                $ary['image'] = $detail->image;
                $ary['image_url'] = ($detail['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $detail['image'] : env('APP_URL') . "avatar.jpg";
                $ary['photoprint_variant'] = $this->getPhotoPrintUpload($detail->order_items_id);
                $ary['frames'] = !empty($detail->frame_details) ? $this->getPhotoFrameUpload($detail->order_items_id, $detail->frame_details) : [];
                $ary['variant_attributes'] = !empty($detail->variant_attributes) ? $this->getPersonalizedUpload($detail->order_items_id, json_decode($detail->variant_attributes, true)) : [];
                $ary['images'] = !empty($detail->images) ? $this->getSelfieUpload($detail->order_items_id) : [];
                $ary['no_of_images'] = !empty($detail->no_of_images) ? $detail->no_of_images : 0;
                $final[] = $ary;
            }
        }
        return $final;
    }

    public function getPhotoFrameUpload($orderItemsId, $frameDetails)
    {

        $frameArray = [];
        $resultArray = [];

        $frameDetails = json_decode($frameDetails, true);

        if (!empty($frameDetails)) {

            foreach ($frameDetails as $pd) {

                $frameArray['label'] = $pd['frame'];
                $frameArray['image_count'] = $pd['image_count'];
                $frameArray['images'] = $this->getPhotoFrameUploadLabelDetails($orderItemsId, $pd['frame']);

                $resultArray[] = $frameArray;
            }
        }


        return $resultArray;
    }

    public function getPhotoFrameUploadLabelDetails($orderItemsId, $frame)
    {
        $photoframeUpload = PhotoFrameLabelModel::where('order_photoframe_upload_label.order_items_id', $orderItemsId)->where('label_name', $frame)->get();

        $frameArray = [];
        $resultArray = [];

        if (!empty($photoframeUpload)) {

            foreach ($photoframeUpload as $pd) {

                $frameArray = $this->getPhotoFrameUploadImage($pd['order_photoframe_upload_label_id']);

                $resultArray = $frameArray;
            }
        }


        return $resultArray;
    }

    public function getPhotoFrameUploadImage($uploadlabelId)
    {

        $photoframeUpload = PhotoFrameUploadModel::where('order_photoframe_upload.order_photoframe_upload_label_id', $uploadlabelId)->leftjoin('order_photoframe_upload_label', 'order_photoframe_upload_label.order_photoframe_upload_label_id', '=', 'order_photoframe_upload.order_photoframe_upload_label_id')->select('order_photoframe_upload_label.label_name', 'order_photoframe_upload.*')->get();

        $frameArray = [];
        $resultArray = [];

        if (!empty($photoframeUpload)) {

            foreach ($photoframeUpload as $pd) {

                $frameArray['id'] = $pd['order_photoframe_upload_id'];
                $frameArray['image'] = $pd['image'];
                $frameArray['image_url'] = ($pd['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['image'] : env('APP_URL') . "avatar.jpg";
                $resultArray[] = $frameArray;
            }
        }


        return $resultArray;
    }

    public function getPhotoPrintUpload($orderItemsId)
    {

        $photoprintUpload = PhotoPrintUploadModel::where('order_items_id', $orderItemsId)->get();

        $photoprintArray = [];
        $resultArray = [];

        if (!empty($photoprintUpload)) {

            foreach ($photoprintUpload as $sd) {

                $photoprintArray['order_photoprint_upload_id'] = $sd['order_photoprint_upload_id'];
                $photoprintArray['image'] = $sd['image'];
                $photoprintArray['image_url'] = ($sd['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $sd['image'] : env('APP_URL') . "avatar.jpg";
                $photoprintArray['quantity'] = $sd['quantity'];
                $resultArray[] = $photoprintArray;
            }
        }


        return $resultArray;
    }

    public function getPersonalizedUpload($orderItemsId, $variantDetails)
    {
        if (!empty($orderItemsId)) {
            $resultArray = [];

            $personalizedArray['reference_image'] = $this->personalizedreferenceImage($orderItemsId);
            $personalizedArray['image'] = $this->personalizedImage($orderItemsId);
            $personalizedArray['labels'] = $this->getPersonalizedLabel($variantDetails);
            $resultArray[] = $personalizedArray;

            return $resultArray;
        }
    }

    public function personalizedreferenceImage($orderItemsId)
    {

        $personalizedUpload = PersonalizedUploadModel::where('order_items_id', $orderItemsId)->where('reference_image', '!=', '')->get();

        $personalizedArray = [];
        $resultArray = [];

        if (!empty($personalizedUpload)) {

            foreach ($personalizedUpload as $pd) {

                $personalizedArray['order_personalized_upload_id'] = $pd['order_personalized_upload_id'];
                $personalizedArray['image'] = $pd['reference_image'];
                $personalizedArray['image_url'] = ($pd['reference_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['reference_image'] : env('APP_URL') . "avatar.jpg";
                $resultArray[] = $personalizedArray;
            }
        }


        return $resultArray;
    }

    public function personalizedImage($orderItemsId)
    {

        $personalizedUpload = PersonalizedUploadModel::where('order_items_id', $orderItemsId)->where('image', '!=', '')->get();

        $personalizedArray = [];
        $resultArray = [];

        if (!empty($personalizedUpload)) {

            foreach ($personalizedUpload as $pd) {

                $personalizedArray['id'] = $pd['order_personalized_upload_id'];
                $personalizedArray['image'] = $pd['image'];
                $personalizedArray['image_url'] = ($pd['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['image'] : env('APP_URL') . "avatar.jpg";
                $resultArray[] = $personalizedArray;
            }
        }


        return $resultArray;
    }

    public function getPersonalizedLabel($variantDetails)
    {

        $labelArray = [];
        $resultArray = [];

        if (!empty($variantDetails)) {

            foreach ($variantDetails as $cm) {

                $labelArray = $cm['labels'];
                $resultArray = $labelArray;
            }
        }


        return $resultArray;
    }

    public function getSelfieUpload($orderItemsId)
    {

        $selfieUpload = SelfieUploadModel::where('order_items_id', $orderItemsId)->get();

        $selfieArray = [];
        $resultArray = [];

        if (!empty($selfieUpload)) {

            foreach ($selfieUpload as $sd) {

                $selfieArray['id'] = $sd['order_selfie_upload_id'];
                $selfieArray['image'] = $sd['image'];
                $selfieArray['image_url'] = ($sd['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $sd['image'] : env('APP_URL') . "avatar.jpg";
                $resultArray[] = $selfieArray;
            }
        }


        return $resultArray;
    }

    public function photoprintSaveDetails($images, $orderItems)
    {
        $images = json_decode($images, true);
        if (!empty($images)) {

            foreach ($images as $img) {
                $newupload = new PhotoPrintUploadModel();
                $newupload->order_items_id = $orderItems->order_items_id;
                $newupload->image = $img['image'];
                $newupload->quantity = $img['quantity'];
                $newupload->created_on = Server::getDateTime();
                $newupload->created_by = JwtHelper::getSesEmpUserId();
                $newupload->save();
            }
        }
    }

    public function passportSizeSaveDetails($image, $backgroundColor, $orderItems)
    {
        if (!empty($image)) {
            $newupload = new PassportSizeUploadModel();
            $newupload->order_items_id = $orderItems->order_items_id;
            $newupload->image = $image;
            $newupload->background_color = $backgroundColor;
            $newupload->created_on = Server::getDateTime();
            $newupload->created_by = JwtHelper::getSesUserId();
            $newupload->save();
        }
    }

    public function photoframeaddtocartDetail($frames)
    {
        $DataArray = [];
        $resultArray = [];

        $Data = json_decode($frames, true);

        if (!empty($Data)) {

            foreach ($Data as $data) {

                $DataArray['label'] = $data['label'];
                $DataArray['images'] = $data['images'];
                $resultArray[] = $DataArray;
            }
        }

        return $resultArray;
    }

    public function photoframesavedetails($label, $orderItems)
    {
        if (!empty($label)) {
            foreach ($label as $lab) {
                $newLabel = new PhotoFrameLabelModel();
                $newLabel->label_name = $lab['label'];
                $newLabel->order_items_id = $orderItems->order_items_id;
                $newLabel->created_on = Server::getDateTime();
                $newLabel->created_by = JwtHelper::getSesEmpUserId();
                $newLabel->save();
                $resultArray = $this->photoframeuploadsavedetails($lab['images'], $newLabel, $orderItems);
            }
        }
    }

    public function photoframeuploadsavedetails($images, $newLabel, $orderItems)
    {

        if (!empty($images)) {

            foreach ($images as $res) {
                $newupload = new PhotoFrameUploadModel();
                $newupload->order_items_id = $orderItems->order_items_id;
                $newupload->order_photoframe_upload_label_id = $newLabel->order_photoframe_upload_label_id;
                $newupload->image = $res['image'];
                $newupload->created_on = Server::getDateTime();
                $newupload->created_by = JwtHelper::getSesEmpUserId();
                $newupload->save();
            }
        }
    }

    public function personalizedaddtocartDetail($variantattributes)
    {
        $DataArray = [];
        $resultArray = [];

        $Data = json_decode($variantattributes, true);

        if (!empty($Data)) {

            foreach ($Data as $data) {

                // $DataArray['is_customized'] = $data['is_customized'];
                $DataArray['reference_image'] = $data['reference_image'];
                $DataArray['image'] = $data['image'];
                $resultArray[] = $DataArray;
            }
        }

        return $resultArray;
    }

    public function personalizedSaveDetails($personalized, $orderItems)
    {
        if (!empty($personalized)) {
            foreach ($personalized as $person) {
                $resultArray = $this->personalizeduploadsavedetails($person['reference_image'], $person['image'], $orderItems);
            }
        }
    }

    public function personalizeduploadsavedetails($referenceImage, $image, $orderItems)
    {
        if (!empty($referenceImage)) {

            foreach ($referenceImage as $res) {
                $newupload = new PersonalizedUploadModel();
                $newupload->order_items_id = $orderItems->order_items_id;
                $newupload->is_customized = $orderItems->is_customized;
                $newupload->reference_image = $res['image'];
                $newupload->created_on = Server::getDateTime();
                $newupload->created_by = JwtHelper::getSesEmpUserId();
                $newupload->save();
            }
        }

        if (!empty($image)) {

            foreach ($image as $img) {
                $newupload = new PersonalizedUploadModel();
                $newupload->order_items_id = $orderItems->order_items_id;
                $newupload->is_customized = $orderItems->is_customized;
                $newupload->image = $img['image'];
                $newupload->created_on = Server::getDateTime();
                $newupload->created_by = JwtHelper::getSesEmpUserId();
                $newupload->save();
            }
        }
    }

    public function selfieSaveDetails($images, $orderItems)
    {
        $images = json_decode($images, true);
        if (!empty($images)) {

            foreach ($images as $img) {
                $newupload = new SelfieUploadModel();
                $newupload->order_items_id = $orderItems->order_items_id;
                $newupload->image = $img['image'];
                $newupload->created_on = Server::getDateTime();
                $newupload->created_by = JwtHelper::getSesEmpUserId();
                $newupload->save();
            }
        }
    }

    public function updateQuoteItems($quoteDetails)
    {
        try {
            Log::channel("createOrder")->info('** start the updateQuoteItems error method **');

            $i = 0;

            $quoteDetailsArray = json_decode($quoteDetails, true);

            if (!empty($quoteDetailsArray)) {

                $quoteDetailsArrayCount = count($quoteDetailsArray);

                $quoteDetails = json_decode($quoteDetails, true);

                foreach ($quoteDetails as $detail) {

                    $bulkOrderQuoteDetailId = $detail['bulk_order_quote_details_id'];
                    $quoteUpdate = BulkOrderQuoteDetails::find($bulkOrderQuoteDetailId);
                    if ($detail['service_id'] == 1) {
                        $quoteUpdate->image = $detail['attachment_image'];
                        $quoteUpdate->background_color = $detail['variant'];
                    } else if ($detail['service_id'] == 2) {
                        $quoteUpdate->photoprint_variant = $detail['attachment_image'];
                    } else if ($detail['service_id'] == 3) {
                        $quoteUpdate->frames = $detail['attachment_image'];
                    } else if ($detail['service_id'] == 4) {
                        $quoteUpdate->variant_attributes = $detail['attachment_image'];
                    } else if ($detail['service_id'] == 6) {
                        $quoteUpdate->images = $detail['attachment_image'];
                    }
                    if ($quoteUpdate->save()) {

                        $i++;
                    } else {

                        $i--;
                    }
                }
            }

            if ($i == $quoteDetailsArrayCount) {

                return true;
            } else {

                return false;
            }
        } catch (\Exception $exception) {
            Log::channel("createOrder")->info('** start the updateQuoteItems error method **');
            Log::channel("createOrder")->error($exception);
            Log::channel("createOrder")->info('** end the updateQuoteItems error method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    //UpdateQuoteDetails
    public function photoPrintImageValidation($images)
    {
        if (!empty($images)) {
            $gTImage = json_decode($images, true);
            if (!empty($gTImage)) {
                foreach ($gTImage as $d) {

                    $validator = Validator::make($d, [
                        'image' => 'required',
                        'quantity' => 'required|numeric|min:1'

                    ]);

                    if ($validator->fails()) {
                        return response()->json([
                            "keyword"    => 'failed',
                            "message"   => $validator->errors()->first(),
                            "data"  => []
                        ]);
                    }
                }
                foreach ($gTImage as $im) {
                    $ary[] = pathinfo($im['image'], PATHINFO_EXTENSION);
                }
                $extension_array = ['jpeg', 'png', 'jpg'];
                if (!array_diff($ary, $extension_array)) {
                    return "success";
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
                        'data'        => []
                    ]);
                }
            }
        }
    }

    public function photoFrameImageValidation($images)
    {
        if (!empty($images)) {
            $gTImage = json_decode($images, true);
            if (!empty($gTImage)) {
                foreach ($gTImage as $im) {
                    $validator = Validator::make($im, [
                        'label' => 'required'
                    ]);
                    if ($validator->fails()) {
                        return response()->json([
                            "keyword"    => 'failed',
                            "message"   => $validator->errors()->first(),
                            "data"  => []
                        ]);
                    }
                    $imageArray = $im['images'];
                }
                foreach ($imageArray as $d) {

                    $validator = Validator::make($d, [
                        'image' => 'required'
                    ]);

                    if ($validator->fails()) {
                        return response()->json([
                            "keyword"    => 'failed',
                            "message"   => $validator->errors()->first(),
                            "data"  => []
                        ]);
                    }
                }
                if (!empty($imageArray)) {
                    foreach ($imageArray as $img) {
                        $ary[] = pathinfo($img['image'], PATHINFO_EXTENSION);
                    }
                }
                $extension_array = ['jpeg', 'png', 'jpg'];
                if (!array_diff($ary, $extension_array)) {
                    return "success";
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
                        'data'        => []
                    ]);
                }
            }
        }
    }

    public function personalizedImageValidation($images)
    {
        if (!empty($images)) {
            $gTImage = json_decode($images, true);
            if (!empty($gTImage)) {
                foreach ($gTImage as $im) {
                    if (isset($im['reference_image'])) {
                        $reference_image = $im['reference_image'];
                    }
                    if (isset($im['image'])) {
                        $image = $im['image'];
                    }
                    if (isset($im['labels'])) {
                        $labels = $im['labels'];
                    }
                }

                //label
                if (!empty($labels)) {
                    foreach ($labels as $d) {

                        $validator = Validator::make($d, [
                            'label' => 'required',
                            'content' => 'required'
                        ]);

                        if ($validator->fails()) {
                            return response()->json([
                                "keyword"    => 'failed',
                                "message"   => $validator->errors()->first(),
                                "data"  => []
                            ]);
                        }
                    }
                }
                //reference_image
                if (!empty($reference_image)) {
                    foreach ($reference_image as $d) {

                        $validator = Validator::make($d, [
                            'image' => 'required'
                        ]);

                        if ($validator->fails()) {
                            return response()->json([
                                "keyword"    => 'failed',
                                "message"   => $validator->errors()->first(),
                                "data"  => []
                            ]);
                        }
                    }
                    foreach ($reference_image as $img) {
                        $ary[] = pathinfo($img['image'], PATHINFO_EXTENSION);
                    }
                    $extension_array = ['jpeg', 'png', 'jpg'];
                    if (!array_diff($ary, $extension_array)) {
                        return "success";
                    } else {
                        return response()->json([
                            'keyword'      => 'failed',
                            'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
                            'data'        => []
                        ]);
                    }
                }

                //image
                if (!empty($image)) {
                    foreach ($image as $d) {

                        $validator = Validator::make($d, [
                            'image' => 'required'
                        ]);

                        if ($validator->fails()) {
                            return response()->json([
                                "keyword"    => 'failed',
                                "message"   => $validator->errors()->first(),
                                "data"  => []
                            ]);
                        }
                    }
                    foreach ($image as $img) {
                        $ary[] = pathinfo($img['image'], PATHINFO_EXTENSION);
                    }
                    $extension_array = ['jpeg', 'png', 'jpg'];
                    if (!array_diff($ary, $extension_array)) {
                        return "success";
                    } else {
                        return response()->json([
                            'keyword'      => 'failed',
                            'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
                            'data'        => []
                        ]);
                    }
                }
            }
        }
    }

    public function selfieImageValidation($images)
    {
        if (!empty($images)) {
            $gTImage = json_decode($images, true);
            if (!empty($gTImage)) {
                foreach ($gTImage as $d) {

                    $validator = Validator::make($d, [
                        'image' => 'required'
                    ]);

                    if ($validator->fails()) {
                        return response()->json([
                            "keyword"    => 'failed',
                            "message"   => $validator->errors()->first(),
                            "data"  => []
                        ]);
                    }
                }
                foreach ($gTImage as $im) {
                    $ary[] = pathinfo($im['image'], PATHINFO_EXTENSION);
                }
                $extension_array = ['jpeg', 'png', 'jpg'];
                if (!array_diff($ary, $extension_array)) {
                    return "success";
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
                        'data'        => []
                    ]);
                }
            }
        }
    }

    public function updateOrderItems($orderItemDetails)
    {
        try {
            Log::channel("createOrder")->info('** start the updateOrderItems error method **');

            $i = 0;

            $orderItemsDetailsArray = json_decode($orderItemDetails, true);

            if (!empty($orderItemsDetailsArray)) {

                $orderItemsDetailsArrayCount = count($orderItemsDetailsArray);

                $orderItems = json_decode($orderItemDetails, true);

                foreach ($orderItems as $detail) {

                    $orderItemId = $detail['order_items_id'];
                    $orderItemUpdate = OrderItems::find($orderItemId);
                    if ($detail['service_id'] == 1) {
                        $orderItemUpdate->image = $detail['attachment_image'];
                        $orderItemUpdate->background_color = $detail['variant'];
                    } else if ($detail['service_id'] == 2) {
                        $orderItemUpdate->photoprint_variant = $detail['attachment_image'];
                    } else if ($detail['service_id'] == 3) {
                        $orderItemUpdate->frames = $detail['attachment_image'];
                    } else if ($detail['service_id'] == 4) {
                        $orderItemUpdate->variant_attributes = $detail['attachment_image'];
                    } else if ($detail['service_id'] == 6) {
                        $orderItemUpdate->images = $detail['attachment_image'];
                    }
                    if ($orderItemUpdate->save()) {

                        if ($detail[$i]['service_id'] == 1) {
                            $photoprintsave = $this->passportSizeSaveDetailsUpdate($detail['attachment_image'], $detail['variant'], $orderItemUpdate);
                        }

                        if ($detail['service_id'] == 2) {
                            $photoprintsave = $this->photoprintSaveDetailsUpdate($detail['attachment_image'], $orderItemUpdate);
                        }

                        if ($detail['service_id'] == 3) {
                            $photoframedetails = $this->photoframeaddtocartDetailUpdate($detail['attachment_image']);
                            $photoframesave = $this->photoframesavedetailsUpdate($photoframedetails, $orderItemUpdate);
                        }

                        if ($detail['service_id'] == 4) {
                            $personalizedDetails = $this->personalizedaddtocartDetailUpdate($detail['attachment_image']);
                            $personalizedsave = $this->personalizedSaveDetailsUpdate($personalizedDetails, $orderItemUpdate);
                        }

                        if ($detail['service_id'] == 6) {
                            $selfiesave = $this->selfieSaveDetailsUpdate($detail['attachment_image'], $orderItemUpdate);
                        }

                        $i++;
                    } else {

                        $i--;
                    }
                }
            }

            if ($i == $orderItemsDetailsArrayCount) {

                return true;
            } else {

                return false;
            }
        } catch (\Exception $exception) {
            Log::channel("createOrder")->info('** start the updateOrderItems error method **');
            Log::channel("createOrder")->error($exception);
            Log::channel("createOrder")->info('** end the updateOrderItems error method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function passportSizeSaveDetailsUpdate($image, $backgroundColor, $orderItems)
    {
        if (!empty($image)) {
            $itemDetails = PassportSizeUploadModel::where('order_items_id', $orderItems->order_items_id)->delete();

            $newupload = new PassportSizeUploadModel();
            $newupload->order_items_id = $orderItems->order_items_id;
            $newupload->image = $image;
            $newupload->background_color = $backgroundColor;
            $newupload->created_on = Server::getDateTime();
            $newupload->created_by = JwtHelper::getSesUserId();
            $newupload->save();
        }
    }

    public function photoprintSaveDetailsUpdate($images, $orderItems)
    {
        // $images = json_decode($images, true);
        if (!empty($images)) {

            $itemDetails = PhotoPrintUploadModel::where('order_items_id', $orderItems->order_items_id)->delete();
            foreach ($images as $img) {
                $newupload = new PhotoPrintUploadModel();
                $newupload->order_items_id = $orderItems->order_items_id;
                $newupload->image = $img['image'];
                $newupload->quantity = $img['quantity'];
                $newupload->created_on = Server::getDateTime();
                $newupload->created_by = JwtHelper::getSesEmpUserId();
                $newupload->save();
            }
        }
    }

    public function photoframeaddtocartDetailUpdate($frames)
    {
        $DataArray = [];
        $resultArray = [];

        // $Data = json_decode($frames, true);

        if (!empty($frames)) {

            foreach ($frames as $data) {

                $DataArray['label'] = $data['label'];
                $DataArray['images'] = $data['images'];
                $resultArray[] = $DataArray;
            }
        }

        return $resultArray;
    }

    public function photoframesavedetailsUpdate($label, $orderItems)
    {
        if (!empty($label)) {

            $itemDetails = PhotoFrameLabelModel::where('order_items_id', $orderItems->order_items_id)->delete();

            $itemDetails = PhotoFrameUploadModel::where('order_items_id', $orderItems->order_items_id)->delete();
            foreach ($label as $lab) {
                $newLabel = new PhotoFrameLabelModel();
                $newLabel->label_name = $lab['label'];
                $newLabel->order_items_id = $orderItems->order_items_id;
                $newLabel->created_on = Server::getDateTime();
                $newLabel->created_by = JwtHelper::getSesEmpUserId();
                $newLabel->save();
                $resultArray = $this->photoframeuploadsavedetailsUpdate($lab['images'], $newLabel, $orderItems);
            }
        }
    }

    public function photoframeuploadsavedetailsUpdate($images, $newLabel, $orderItems)
    {

        if (!empty($images)) {

            foreach ($images as $res) {
                $newupload = new PhotoFrameUploadModel();
                $newupload->order_items_id = $orderItems->order_items_id;
                $newupload->order_photoframe_upload_label_id = $newLabel->order_photoframe_upload_label_id;
                $newupload->image = $res['image'];
                $newupload->created_on = Server::getDateTime();
                $newupload->created_by = JwtHelper::getSesEmpUserId();
                $newupload->save();
            }
        }
    }

    public function personalizedaddtocartDetailUpdate($variantattributes)
    {
        $DataArray = [];
        $resultArray = [];

        // $Data = json_decode($variantattributes, true);

        if (!empty($variantattributes)) {

            foreach ($variantattributes as $data) {

                // $DataArray['is_customized'] = $data['is_customized'];
                $DataArray['reference_image'] = $data['reference_image'];
                $DataArray['image'] = $data['image'];
                $resultArray[] = $DataArray;
            }
        }

        return $resultArray;
    }

    public function personalizedSaveDetailsUpdate($personalized, $orderItems)
    {
        if (!empty($personalized)) {
            foreach ($personalized as $person) {
                $resultArray = $this->personalizeduploadsavedetailsUpdate($person['reference_image'], $person['image'], $orderItems);
            }
        }
    }

    public function personalizeduploadsavedetailsUpdate($referenceImage, $image, $orderItems)
    {

        $itemDetails = PersonalizedUploadModel::where('order_items_id', $orderItems->order_items_id)->delete();

        if (!empty($referenceImage)) {

            foreach ($referenceImage as $res) {
                $newupload = new PersonalizedUploadModel();
                $newupload->order_items_id = $orderItems->order_items_id;
                $newupload->is_customized = $orderItems->is_customized;
                $newupload->reference_image = $res['image'];
                $newupload->created_on = Server::getDateTime();
                $newupload->created_by = JwtHelper::getSesEmpUserId();
                $newupload->save();
            }
        }

        if (!empty($image)) {

            foreach ($image as $img) {
                $newupload = new PersonalizedUploadModel();
                $newupload->order_items_id = $orderItems->order_items_id;
                $newupload->is_customized = $orderItems->is_customized;
                $newupload->image = $img['image'];
                $newupload->created_on = Server::getDateTime();
                $newupload->created_by = JwtHelper::getSesEmpUserId();
                $newupload->save();
            }
        }
    }

    public function selfieSaveDetailsUpdate($images, $orderItems)
    {
        // $images = json_decode($images, true);
        if (!empty($images)) {

            $itemDetails = SelfieUploadModel::where('order_items_id', $orderItems->order_items_id)->delete();

            foreach ($images as $img) {
                $newupload = new SelfieUploadModel();
                $newupload->order_items_id = $orderItems->order_items_id;
                $newupload->image = $img['image'];
                $newupload->created_on = Server::getDateTime();
                $newupload->created_by = JwtHelper::getSesEmpUserId();
                $newupload->save();
            }
        }
    }
}
