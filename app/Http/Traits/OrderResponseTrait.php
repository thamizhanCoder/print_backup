<?php

namespace App\Http\Traits;

use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Models\PassportSizeUploadHistoryModel;
use App\Models\PassportSizeUploadModel;
use App\Models\PassportSizeUploadPreviewHistoryModel;
use App\Models\PassportSizeUploadQcModel;
use App\Models\PersonalizedUploadHistoryModel;
use App\Models\PersonalizedUploadModel;
use App\Models\PhotoFrameLabelModel;
use App\Models\PhotoFramePreviewHistory;
use App\Models\PhotoFrameQcHistory;
use App\Models\PhotoFrameUploadHistoryModel;
use App\Models\PhotoFrameUploadModel;
use App\Models\PhotoPrintUploadHistoryModel;
use App\Models\PhotoPrintUploadModel;
use App\Models\PhotoPrintUploadPreviewHistoryModel;
use App\Models\PhotoPrintUploadQcUpload;
use App\Models\ProductCatalogue;
use App\Models\ProductVariant;
use App\Models\RelatedProduct;
use App\Models\SelfieUploadHistoryModel;
use App\Models\SelfieUploadModel;
use App\Models\SelfieUploadPreviewHistoryModel;
use App\Models\SelfieUploadQcModel;
use App\Models\TaskManager;
use App\Models\TaskManagerPreviewHistory;
use App\Models\TaskManagerQcHistory;
use App\Models\VariantType;
use Illuminate\Support\Facades\log;
use Illuminate\Support\Facades\Validator;

trait OrderResponseTrait
{
    public function getPhotoFrameUpload($orderItemsId)
    {

        // $photoframeUpload = PhotoFrameUploadModel::where('order_photoframe_upload.order_items_id', $orderItemsId)->leftjoin('order_photoframe_upload_label', 'order_photoframe_upload_label.order_photoframe_upload_label_id', '=', 'order_photoframe_upload.order_photoframe_upload_label_id')->select('order_photoframe_upload_label.label_name', 'order_photoframe_upload_label.order_photoframe_upload_label_id as label_id', 'order_photoframe_upload.*')->groupby('order_photoframe_upload.order_photoframe_upload_label_id')->get();
        $photoframeUpload = PhotoFrameLabelModel::where('order_photoframe_upload_label.order_items_id', $orderItemsId)->get();

        $frameArray = [];
        $resultArray = [];

        if (!empty($photoframeUpload)) {

            foreach ($photoframeUpload as $pd) {

                $frameArray['order_photoframe_upload_label_id'] = $pd['order_photoframe_upload_label_id'];
                $frameArray['label_name'] = $pd['label_name'];
                //$frameArray['qc_image'] = $pd['qc_image'];
                //$frameArray['qc_image_url'] = ($pd['qc_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['qc_image'] : env('APP_URL') . "avatar.jpg";
                //$frameArray['qc_on'] = $pd['qc_on'];
                //$frameArray['qc_reason'] = $pd['qc_reason'];
                //$frameArray['qc_reason_on'] = $pd['qc_reason_on'];
                //$frameArray['qc_status'] = $pd['qc_status'];
                //$frameArray['qc_history'] = $this->photoFrameQcHistory($pd['order_photoframe_upload_label_id']);
                //$frameArray['preview_image'] = $pd['preview_image'];
                //$frameArray['preview_image_url'] = ($pd['preview_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['preview_image'] : env('APP_URL') . "avatar.jpg";
                //$frameArray['preview_reason'] = $pd['preview_reason'];
                //$frameArray['preview_reason_on'] = $pd['preview_reason_on'];
                //$frameArray['preview_status'] = $pd['preview_status'];
                //$frameArray['preview_history'] = $this->photoFramePreviewHistory($pd['order_photoframe_upload_label_id']);
                $frameArray['images'] = $this->getPhotoFrameUploadImage($pd['order_photoframe_upload_label_id']);

                $resultArray[] = $frameArray;
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
                $frameArray['status'] = $pd['status'];
                $frameArray['approved_on'] = $pd['updated_on'];
                $frameArray['received_on'] = $pd['updated_on'] == null ? $pd['created_on'] : $pd['updated_on'];
                $frameArray['reject_reason'] = $pd['reject_reason'];
                $frameArray['rejected_on'] = $pd['rejected_on'];
                $frameArray['image_history'] = $this->photoFrameUploadHistory($pd['order_photoframe_upload_id']);
                $resultArray[] = $frameArray;
            }
        }


        return $resultArray;
    }

    public function passportSizeUploadHistory($uploadId)
    {

        $Upload = PassportSizeUploadHistoryModel::where('order_passport_upload_id', $uploadId)->select('order_passport_upload_history.*')->get();

        $uploadArray = [];
        $resultArray = [];

        if (!empty($Upload)) {

            foreach ($Upload as $pd) {

                $uploadArray['history_id'] = $pd['order_passport_upload_history_id'];
                $uploadArray['id'] = $pd['order_passport_upload_id'];
                $uploadArray['image'] = $pd['image'];
                $uploadArray['image_url'] = ($pd['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['image'] : env('APP_URL') . "avatar.jpg";
                $uploadArray['approved_on'] = $pd['created_on'];
                $uploadArray['reject_reason'] = $pd['reject_reason'];
                $uploadArray['rejected_on'] = $pd['rejected_on'];
                $uploadArray['status'] = $pd['status'];
                $resultArray[] = $uploadArray;
            }
        }


        return $resultArray;
    }

    public function photoPrintSizeUploadHistory($uploadId)
    {

        $Upload = PhotoPrintUploadHistoryModel::where('order_photoprint_upload_id', $uploadId)->select('order_photoprint_upload_history.*')->get();

        $uploadArray = [];
        $resultArray = [];

        if (!empty($Upload)) {

            foreach ($Upload as $pd) {

                $uploadArray['history_id'] = $pd['order_photoprint_upload_history_id'];
                $uploadArray['id'] = $pd['order_photoprint_upload_id'];
                $uploadArray['image'] = $pd['image'];
                $uploadArray['image_url'] = ($pd['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['image'] : env('APP_URL') . "avatar.jpg";
                $uploadArray['approved_on'] = $pd['created_on'];
                $uploadArray['reject_reason'] = $pd['reject_reason'];
                $uploadArray['rejected_on'] = $pd['rejected_on'];
                $uploadArray['status'] = $pd['status'];
                $resultArray[] = $uploadArray;
            }
        }


        return $resultArray;
    }

    public function photoFrameUploadHistory($uploadId)
    {

        $Upload = PhotoFrameUploadHistoryModel::where('order_photoframe_upload_id', $uploadId)->select('order_photoframe_upload_history.*')->get();

        $uploadArray = [];
        $resultArray = [];

        if (!empty($Upload)) {

            foreach ($Upload as $pd) {

                $uploadArray['history_id'] = $pd['order_photoframe_upload_history_id'];
                $uploadArray['id'] = $pd['order_photoframe_upload_id'];
                $uploadArray['image'] = $pd['image'];
                $uploadArray['image_url'] = ($pd['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['image'] : env('APP_URL') . "avatar.jpg";
                $uploadArray['approved_on'] = $pd['created_on'];
                $uploadArray['reject_reason'] = $pd['reject_reason'];
                $uploadArray['rejected_on'] = $pd['rejected_on'];
                $uploadArray['status'] = $pd['status'];
                $resultArray[] = $uploadArray;
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
            //$personalizedArray['qcPreviewDatils'] = $this->taskManagerQcPreviewDetails($orderItemsId);
            $personalizedArray['labels'] = $this->getPersonalizedLabel($variantDetails);
            $resultArray[] = $personalizedArray;

            return $resultArray;
        }
    }

    public function taskManagerQcPreviewDetails($orderItemsId)
    {

        $personalizedUpload = TaskManager::where('order_items_id', $orderItemsId)->first();

        $personalizedArray = [];
        $resultArray = [];

        if (!empty($personalizedUpload)) {

            // foreach ($personalizedUpload as $pd) {

                $personalizedArray['qc_image'] = $personalizedUpload['qc_image'];
                $personalizedArray['qc_image_url'] = ($personalizedUpload['qc_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $personalizedUpload['qc_image'] : env('APP_URL') . "avatar.jpg";
                $personalizedArray['qc_on'] = $personalizedUpload['qc_on'];
                $personalizedArray['qc_reason'] = $personalizedUpload['qc_reason'];
                $personalizedArray['qc_reason_on'] = $personalizedUpload['qc_reason_on'];
                $personalizedArray['qc_status'] = $personalizedUpload['qc_status'];
                $personalizedArray['qc_history'] = $this->personalizedQcHistory($personalizedUpload['task_manager_id']);
                $personalizedArray['preview_image'] = $personalizedUpload['preview_image'];
                $personalizedArray['preview_image_url'] = ($personalizedUpload['preview_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $personalizedUpload['preview_image'] : env('APP_URL') . "avatar.jpg";
                $personalizedArray['preview_image'] = $personalizedUpload['preview_image'];
                $personalizedArray['preview_reason'] = $personalizedUpload['preview_reason'];
                $personalizedArray['preview_reason_on'] = $personalizedUpload['preview_reason_on'];
                $personalizedArray['preview_status'] = $personalizedUpload['preview_status'];
                $resultArray = $personalizedArray;
            // }
        }


        return $resultArray;
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
                $personalizedArray['image_history'] = PersonalizedUploadHistoryModel::where('order_personalized_upload_id', $pd['order_personalized_upload_id'])->select('order_personalized_upload_history.*')->get();
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
                $personalizedArray['status'] = $pd['status'];
                $personalizedArray['approved_on'] = $pd['updated_on'];
                $personalizedArray['received_on'] = $pd['updated_on'] == null ? $pd['created_on'] : $pd['updated_on'];
                $personalizedArray['reject_reason'] = $pd['reject_reason'];
                $personalizedArray['rejected_on'] = $pd['rejected_on'];
                $personalizedArray['image_url'] = ($pd['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['image'] : env('APP_URL') . "avatar.jpg";
                $personalizedArray['image_history'] = $this->personalizedUploadHistory($pd['order_personalized_upload_id']);
                $resultArray[] = $personalizedArray;
            }
        }


        return $resultArray;
    }

    public function personalizedUploadHistory($uploadId)
    {

        $Upload = PersonalizedUploadHistoryModel::where('order_personalized_upload_id', $uploadId)->select('order_personalized_upload_history.*')->get();

        $uploadArray = [];
        $resultArray = [];

        if (!empty($Upload)) {

            foreach ($Upload as $pd) {

                $uploadArray['history_id'] = $pd['order_personalized_upload_history_id'];
                $uploadArray['id'] = $pd['order_personalized_upload_id'];
                $uploadArray['image'] = $pd['image'];
                $uploadArray['image_url'] = ($pd['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['image'] : env('APP_URL') . "avatar.jpg";
                $uploadArray['is_customized'] = $pd['is_customized'];
                $uploadArray['approved_on'] = $pd['created_on'];
                $uploadArray['reject_reason'] = $pd['reject_reason'];
                $uploadArray['rejected_on'] = $pd['rejected_on'];
                $uploadArray['status'] = $pd['status'];
                $resultArray[] = $uploadArray;
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
                $selfieArray['status'] = $sd['status'];
                $selfieArray['approved_on'] = $sd['updated_on'];
                $selfieArray['received_on'] = $sd['updated_on'] == null ? $sd['created_on'] : $sd['updated_on'];
                $selfieArray['reject_reason'] = $sd['reject_reason'];
                $selfieArray['rejected_on'] = $sd['rejected_on'];
                $selfieArray['image_history'] = $this->selfieUploadHistory($sd['order_selfie_upload_id']);
                $resultArray[] = $selfieArray;
            }
        }


        return $resultArray;
    }

    public function selfieUploadHistory($uploadId)
    {

        $Upload = SelfieUploadHistoryModel::where('order_selfie_upload_id', $uploadId)->select('order_selfie_upload_history.*')->get();

        $uploadArray = [];
        $resultArray = [];

        if (!empty($Upload)) {

            foreach ($Upload as $pd) {

                $uploadArray['history_id'] = $pd['order_selfie_upload_history_id'];
                $uploadArray['id'] = $pd['order_selfie_upload_id'];
                $uploadArray['image'] = $pd['image'];
                $uploadArray['image_url'] = ($pd['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['image'] : env('APP_URL') . "avatar.jpg";
                $uploadArray['approved_on'] = $pd['created_on'];
                $uploadArray['reject_reason'] = $pd['reject_reason'];
                $uploadArray['rejected_on'] = $pd['rejected_on'];
                $uploadArray['status'] = $pd['status'];
                $resultArray[] = $uploadArray;
            }
        }


        return $resultArray;
    }

    public function getPassportSizeUpload($orderItemsId)
    {

        $photoprintUpload = PassportSizeUploadModel::where('order_items_id', $orderItemsId)->get();

        $passportSizeArray = [];
        $resultArray = [];

        if (!empty($photoprintUpload)) {

            foreach ($photoprintUpload as $sd) {

                $passportSizeArray['order_passport_upload_id'] = $sd['order_passport_upload_id'];
                $passportSizeArray['image'] = $sd['image'];
                $passportSizeArray['image_url'] = ($sd['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $sd['image'] : env('APP_URL') . "avatar.jpg";
                $passportSizeArray['background_color'] = $sd['background_color'];
                $passportSizeArray['status'] = $sd['status'];
                $passportSizeArray['approved_on'] = $sd['updated_on'];
                $passportSizeArray['received_on'] = $sd['updated_on'] == null ? $sd['created_on'] : $sd['updated_on'];
                $passportSizeArray['reject_reason'] = $sd['reject_reason'];
                $passportSizeArray['rejected_on'] = $sd['rejected_on'];
                $passportSizeArray['image_history'] = $this->passportUploadHistory($sd['order_passport_upload_id']);
                $resultArray[] = $passportSizeArray;
            }
        }


        return $resultArray;
    }

    public function passportUploadHistory($uploadId)
    {

        $Upload = PassportSizeUploadHistoryModel::where('order_passport_upload_id', $uploadId)->select('order_passport_upload_history.*')->get();

        $uploadArray = [];
        $resultArray = [];

        if (!empty($Upload)) {

            foreach ($Upload as $pd) {

                $uploadArray['history_id'] = $pd['order_passport_upload_history_id'];
                $uploadArray['id'] = $pd['order_passport_upload_id'];
                $uploadArray['image'] = $pd['image'];
                $uploadArray['image_url'] = ($pd['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['image'] : env('APP_URL') . "avatar.jpg";
                $uploadArray['approved_on'] = $pd['created_on'];
                $uploadArray['reject_reason'] = $pd['reject_reason'];
                $uploadArray['rejected_on'] = $pd['rejected_on'];
                $uploadArray['status'] = $pd['status'];
                $resultArray[] = $uploadArray;
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
                $photoprintArray['status'] = $sd['status'];
                $photoprintArray['approved_on'] = $sd['updated_on'];
                $photoprintArray['received_on'] = $sd['updated_on'] == null ? $sd['created_on'] : $sd['updated_on'];
                $photoprintArray['reject_reason'] = $sd['reject_reason'];
                $photoprintArray['rejected_on'] = $sd['rejected_on'];
                $photoprintArray['image_history'] = $this->photoPrintUploadHistory($sd['order_photoprint_upload_id']);
                $resultArray[] = $photoprintArray;
            }
        }


        return $resultArray;
    }

    public function photoPrintUploadHistory($uploadId)
    {

        $Upload = PhotoPrintUploadHistoryModel::where('order_photoprint_upload_id', $uploadId)->select('order_photoprint_upload_history.*')->get();

        $uploadArray = [];
        $resultArray = [];

        if (!empty($Upload)) {

            foreach ($Upload as $pd) {

                $uploadArray['history_id'] = $pd['order_photoprint_upload_history_id'];
                $uploadArray['id'] = $pd['order_photoprint_upload_id'];
                $uploadArray['image'] = $pd['image'];
                $uploadArray['image_url'] = ($pd['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['image'] : env('APP_URL') . "avatar.jpg";
                $uploadArray['approved_on'] = $pd['created_on'];
                $uploadArray['reject_reason'] = $pd['reject_reason'];
                $uploadArray['rejected_on'] = $pd['rejected_on'];
                $uploadArray['status'] = $pd['status'];
                $resultArray[] = $uploadArray;
            }
        }


        return $resultArray;
    }

    public function qcImageHistory($taskManagerId)
    {
        $qcImageHistoryDetails = TaskManagerQcHistory::where('task_manager_id', $taskManagerId)->get();
        $array = [];
        $resultArray = [];
        if (!empty($qcImageHistoryDetails)) {
            foreach ($qcImageHistoryDetails as $qc) {

                $array['task_manager_qc_history_id'] = $qc['task_manager_qc_history_id'];
                $array['task_manager_id'] = $qc['task_manager_id'];
                $array['qc_image'] = $qc['qc_image'];
                $array['qc_image_url'] = ($qc['qc_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $qc['qc_image'] : env('APP_URL') . "avatar.jpg";
                $array['qc_on'] = $qc['qc_on'];
                $array['qc_by'] = $qc['qc_by'];
                $array['qc_reason'] = $qc['qc_reason'];
                $array['qc_reason_on'] = $qc['qc_reason_on'];
                $array['qc_status'] = $qc['qc_status'];
                $resultArray[] = $array;
            }
        }
        return $resultArray;
    }

    public function previewImageHistory($taskManagerId)
    {
        $qcImageHistoryDetails = TaskManagerPreviewHistory::where('task_manager_id', $taskManagerId)->get();
        $array = [];
        $resultArray = [];
        if (!empty($qcImageHistoryDetails)) {
            foreach ($qcImageHistoryDetails as $qc) {

                $array['task_manager_preview_history_id'] = $qc['task_manager_preview_history_id'];
                $array['task_manager_id'] = $qc['task_manager_id'];
                $array['preview_image'] = $qc['preview_image'];
                $array['preview_image_url'] = ($qc['preview_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $qc['preview_image'] : env('APP_URL') . "avatar.jpg";
                $array['preview_on'] = $qc['preview_on'];
                $array['preview_by'] = $qc['preview_by'];
                $array['preview_reason'] = $qc['preview_reason'];
                $array['preview_reason_on'] = $qc['preview_reason_on'];
                $array['preview_status'] = $qc['preview_status'];
                $resultArray[] = $array;
            }
        }
        return $resultArray;
    }

    public function getPassportPhotoQcUpload($orderItemsId)
    {

        $selfieUpload = PassportSizeUploadModel::where('order_items_id', $orderItemsId)->get();

        $selfieArray = [];
        $resultArray = [];

        if (!empty($selfieUpload)) {

            foreach ($selfieUpload as $sd) {

                $selfieArray['id'] = $sd['order_passport_upload_id'];
                $selfieArray['qc_image'] = $sd['qc_image'];
                $selfieArray['qc_image_url'] = ($sd['qc_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $sd['qc_image'] : env('APP_URL') . "avatar.jpg";
                $selfieArray['qc_status'] = $sd['qc_status'];
                $selfieArray['qc_on'] = $sd['qc_on'];
                $selfieArray['qc_reason'] = $sd['qc_reason'];
                $selfieArray['qc_reason_on'] = $sd['qc_reason_on'];
                $selfieArray['qc_image_history'] = $this->PassportPhotoUploadQcHistory($sd['order_passport_upload_id']);
                $resultArray[] = $selfieArray;
            }
        }


        return $resultArray;
    }

    public function PassportPhotoUploadQcHistory($uploadId)
    {

        $Upload = PassportSizeUploadQcModel::where('order_passport_upload_id', $uploadId)->select('order_passportupload_qc_history.*')->get();

        $uploadArray = [];
        $resultArray = [];

        if (!empty($Upload)) {

            foreach ($Upload as $pd) {

                $uploadArray['qc_history_id'] = $pd['order_passportupload_qc_history_id'];
                $uploadArray['id'] = $pd['order_passport_upload_id'];
                $uploadArray['qc_image'] = $pd['qc_image'];
                $uploadArray['qc_image_url'] = ($pd['qc_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['qc_image'] : env('APP_URL') . "avatar.jpg";
                $uploadArray['qc_on'] = $pd['qc_on'];
                $uploadArray['qc_by'] = $pd['qc_by'];
                $uploadArray['qc_reason'] = $pd['qc_reason'];
                $uploadArray['qc_reason_on'] = $pd['qc_reason_on'];
                $uploadArray['qc_status'] = $pd['qc_status'];
                $resultArray[] = $uploadArray;
            }
        }


        return $resultArray;
    }

    public function getPhotoPrintQcUpload($orderItemsId)
    {
        $selfieUpload = PhotoPrintUploadModel::where('order_items_id', $orderItemsId)->get();

        $selfieArray = [];
        $resultArray = [];

        if (!empty($selfieUpload)) {

            foreach ($selfieUpload as $sd) {

                $selfieArray['id'] = $sd['order_photoprint_upload_id'];
                $selfieArray['qc_image'] = $sd['qc_image'];
                $selfieArray['qc_image_url'] = ($sd['qc_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $sd['qc_image'] : env('APP_URL') . "avatar.jpg";
                $selfieArray['qc_status'] = $sd['qc_status'];
                $selfieArray['qc_on'] = $sd['qc_on'];
                $selfieArray['qc_reason'] = $sd['qc_reason'];
                $selfieArray['qc_reason_on'] = $sd['qc_reason_on'];
                $selfieArray['qc_image_history'] = $this->PhotoPrintUploadQcHistory($sd['order_photoprint_upload_id']);
                $resultArray[] = $selfieArray;
            }
        }


        return $resultArray;
    }

    public function PhotoPrintUploadQcHistory($uploadId)
    {

        $Upload = PhotoPrintUploadQcUpload::where('order_photoprint_upload_id', $uploadId)->select('order_photoprint_qc_history.*')->get();
        $uploadArray = [];
        $resultArray = [];

        if (!empty($Upload)) {

            foreach ($Upload as $pd) {

                $uploadArray['qc_history_id'] = $pd['order_photoprint_qc_history_id'];
                $uploadArray['id'] = $pd['order_photoprint_upload_id'];
                $uploadArray['qc_image'] = $pd['qc_image'];
                $uploadArray['qc_image_url'] = ($pd['qc_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['qc_image'] : env('APP_URL') . "avatar.jpg";
                $uploadArray['qc_on'] = $pd['qc_on'];
                $uploadArray['qc_by'] = $pd['qc_by'];
                $uploadArray['qc_reason'] = $pd['qc_reason'];
                $uploadArray['qc_reason_on'] = $pd['qc_reason_on'];
                $uploadArray['qc_status'] = $pd['qc_status'];
                $resultArray[] = $uploadArray;
            }
        }


        return $resultArray;
    }

    //Photoframe Qc Details
    public function getPhotoFrameQcUpload($orderItemsId)
    {

        $selfieUpload = PhotoFrameLabelModel::where('order_items_id', $orderItemsId)->get();

        $selfieArray = [];
        $resultArray = [];

        if (!empty($selfieUpload)) {

            foreach ($selfieUpload as $sd) {

                $selfieArray['id'] = $sd['order_photoframe_upload_label_id'];
                $selfieArray['qc_image'] = $sd['qc_image'];
                $selfieArray['qc_image_url'] = ($sd['qc_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $sd['qc_image'] : env('APP_URL') . "avatar.jpg";
                $selfieArray['qc_status'] = $sd['qc_status'];
                $selfieArray['qc_on'] = $sd['qc_on'];
                $selfieArray['qc_reason'] = $sd['qc_reason'];
                $selfieArray['qc_reason_on'] = $sd['qc_reason_on'];
                $selfieArray['qc_image_history'] = $this->photoFrameQcHistory($sd['order_photoframe_upload_label_id']);
                $resultArray[] = $selfieArray;
            }
        }


        return $resultArray;
    }

    public function getPhotoFrameAdminQcUpload($orderItemsId)
    {

        $selfieUpload = PhotoFrameLabelModel::where('order_items_id', $orderItemsId)->where('qc_status', '!=', 0)->get();

        $selfieArray = [];
        $resultArray = [];

        if (!empty($selfieUpload)) {

            foreach ($selfieUpload as $sd) {

                $selfieArray['id'] = $sd['order_photoframe_upload_label_id'];
                $selfieArray['qc_image'] = $sd['qc_image'];
                $selfieArray['qc_image_url'] = ($sd['qc_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $sd['qc_image'] : env('APP_URL') . "avatar.jpg";
                $selfieArray['qc_status'] = $sd['qc_status'];
                $selfieArray['qc_on'] = $sd['qc_on'];
                $selfieArray['qc_reason'] = $sd['qc_reason'];
                $selfieArray['qc_reason_on'] = $sd['qc_reason_on'];
                $selfieArray['qc_image_history'] = $this->photoFrameQcHistory($sd['order_photoframe_upload_label_id']);
                $resultArray[] = $selfieArray;
            }
        }


        return $resultArray;
    }

    public function photoFrameQcHistory($labelId)
    {

        $Upload = PhotoFrameQcHistory::where('order_photoframe_upload_label_id', $labelId)->select('order_photoframeupload_qc_history.*')->get();

        $uploadArray = [];
        $resultArray = [];

        if (!empty($Upload)) {

            foreach ($Upload as $pd) {

                $uploadArray['qc_history_id'] = $pd['order_photoframeupload_qc_history_id'];
                $uploadArray['id'] = $pd['order_photoframe_upload_label_id'];
                $uploadArray['qc_image'] = $pd['qc_image'];
                $uploadArray['qc_image_url'] = ($pd['qc_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['qc_image'] : env('APP_URL') . "avatar.jpg";
                $uploadArray['qc_on'] = $pd['qc_on'];
                $uploadArray['qc_by'] = $pd['qc_by'];
                $uploadArray['qc_reason'] = $pd['qc_reason'];
                $uploadArray['qc_reason_on'] = $pd['qc_reason_on'];
                $uploadArray['qc_status'] = $pd['qc_status'];
                $resultArray[] = $uploadArray;
            }
        }


        return $resultArray;
    }

    public function getSelfieQcUpload($orderItemsId)
    {

        $selfieUpload = SelfieUploadModel::where('order_items_id', $orderItemsId)->get();

        $selfieArray = [];
        $resultArray = [];

        if (!empty($selfieUpload)) {

            foreach ($selfieUpload as $sd) {

                $selfieArray['id'] = $sd['order_selfie_upload_id'];
                $selfieArray['qc_image'] = $sd['qc_image'];
                $selfieArray['qc_image_url'] = ($sd['qc_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $sd['qc_image'] : env('APP_URL') . "avatar.jpg";
                $selfieArray['qc_status'] = $sd['qc_status'];
                $selfieArray['qc_on'] = $sd['qc_on'];
                $selfieArray['qc_reason'] = $sd['qc_reason'];
                $selfieArray['qc_reason_on'] = $sd['qc_reason_on'];
                $selfieArray['qc_image_history'] = $this->selfieUploadQcHistory($sd['order_selfie_upload_id']);
                $resultArray[] = $selfieArray;
            }
        }


        return $resultArray;
    }

    public function getSelfieAdminQcUpload($orderItemsId)
    {

        $selfieUpload = SelfieUploadModel::where('order_items_id', $orderItemsId)->where('qc_status', '!=', 0)->get();

        $selfieArray = [];
        $resultArray = [];

        if (!empty($selfieUpload)) {

            foreach ($selfieUpload as $sd) {

                $selfieArray['id'] = $sd['order_selfie_upload_id'];
                $selfieArray['qc_image'] = $sd['qc_image'];
                $selfieArray['qc_image_url'] = ($sd['qc_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $sd['qc_image'] : env('APP_URL') . "avatar.jpg";
                $selfieArray['qc_status'] = $sd['qc_status'];
                $selfieArray['qc_on'] = $sd['qc_on'];
                $selfieArray['qc_reason'] = $sd['qc_reason'];
                $selfieArray['qc_reason_on'] = $sd['qc_reason_on'];
                $selfieArray['qc_image_history'] = $this->selfieUploadQcHistory($sd['order_selfie_upload_id']);
                $resultArray[] = $selfieArray;
            }
        }


        return $resultArray;
    }

    public function getPhotoPrintAdminQcUpload($orderItemsId)
    {

        $photoPrintUpload = PhotoPrintUploadModel::where('order_items_id', $orderItemsId)->where('qc_status', '!=', 0)->get();

        $photoPrintArray = [];
        $resultArray = [];

        if (!empty($photoPrintUpload)) {

            foreach ($photoPrintUpload as $sd) {

                $photoPrintArray['id'] = $sd['order_photoprint_upload_id'];
                $photoPrintArray['qc_image'] = $sd['qc_image'];
                $photoPrintArray['qc_image_url'] = ($sd['qc_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $sd['qc_image'] : env('APP_URL') . "avatar.jpg";
                $photoPrintArray['qc_status'] = $sd['qc_status'];
                $photoPrintArray['qc_on'] = $sd['qc_on'];
                $photoPrintArray['qc_reason'] = $sd['qc_reason'];
                $photoPrintArray['qc_reason_on'] = $sd['qc_reason_on'];
                $photoPrintArray['qc_image_history'] = $this->PhotoPrintQcHistory($sd['order_photoprint_upload_id']);
                $resultArray[] = $photoPrintArray;
            }
        }


        return $resultArray;
    }

    public function getPassportSizeAdminQcUpload($orderItemsId)
    {

        $PassportSizeUpload = PassportSizeUploadModel::where('order_items_id', $orderItemsId)->where('qc_status', '!=', 0)->get();

        $passportArray = [];
        $resultArray = [];

        if (!empty($PassportSizeUpload)) {

            foreach ($PassportSizeUpload as $sd) {

                $passportArray['id'] = $sd['order_passport_upload_id'];
                $passportArray['qc_image'] = $sd['qc_image'];
                $passportArray['qc_image_url'] = ($sd['qc_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $sd['qc_image'] : env('APP_URL') . "avatar.jpg";
                $passportArray['qc_status'] = $sd['qc_status'];
                $passportArray['qc_on'] = $sd['qc_on'];
                $passportArray['qc_reason'] = $sd['qc_reason'];
                $passportArray['qc_reason_on'] = $sd['qc_reason_on'];
                $passportArray['qc_image_history'] = $this->passportSizeUploadQcHistory($sd['order_passport_upload_id']);
                $resultArray[] = $passportArray;
            }
        }


        return $resultArray;
    }

    public function passportSizeUploadQcHistory($uploadId)
    {

        $Upload = PassportSizeUploadQcModel::where('order_passport_upload_id', $uploadId)->select('order_passportupload_qc_history.*')->get();

        $uploadArray = [];
        $resultArray = [];

        if (!empty($Upload)) {

            foreach ($Upload as $pd) {

                $uploadArray['qc_history_id'] = $pd['order_passportupload_qc_history_id'];
                $uploadArray['id'] = $pd['order_passport_upload_id'];
                $uploadArray['qc_image'] = $pd['qc_image'];
                $uploadArray['qc_image_url'] = ($pd['qc_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['qc_image'] : env('APP_URL') . "avatar.jpg";
                $uploadArray['qc_on'] = $pd['qc_on'];
                $uploadArray['qc_by'] = $pd['qc_by'];
                $uploadArray['qc_reason'] = $pd['qc_reason'];
                $uploadArray['qc_reason_on'] = $pd['qc_reason_on'];
                $uploadArray['qc_status'] = $pd['qc_status'];
                $resultArray[] = $uploadArray;
            }
        }


        return $resultArray;
    }

    public function PhotoPrintQcHistory($uploadId)
    {

        $Upload = PhotoPrintUploadQcUpload::where('order_photoprint_upload_id', $uploadId)->select('order_photoprint_qc_history.*')->get();

        $uploadArray = [];
        $resultArray = [];

        if (!empty($Upload)) {

            foreach ($Upload as $pd) {

                $uploadArray['qc_history_id'] = $pd['order_photoprint_qc_history_id'];
                $uploadArray['id'] = $pd['order_photoprint_upload_id'];
                $uploadArray['qc_image'] = $pd['qc_image'];
                $uploadArray['qc_image_url'] = ($pd['qc_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['qc_image'] : env('APP_URL') . "avatar.jpg";
                $uploadArray['qc_on'] = $pd['qc_on'];
                $uploadArray['qc_by'] = $pd['qc_by'];
                $uploadArray['qc_reason'] = $pd['qc_reason'];
                $uploadArray['qc_reason_on'] = $pd['qc_reason_on'];
                $uploadArray['qc_status'] = $pd['qc_status'];
                $resultArray[] = $uploadArray;
            }
        }


        return $resultArray;
    }

    public function selfieUploadQcHistory($uploadId)
    {

        $Upload = SelfieUploadQcModel::where('order_selfie_upload_id', $uploadId)->select('order_selfieupload_qc_history.*')->get();

        $uploadArray = [];
        $resultArray = [];

        if (!empty($Upload)) {

            foreach ($Upload as $pd) {

                $uploadArray['qc_history_id'] = $pd['order_selfieupload_qc_history_id'];
                $uploadArray['id'] = $pd['order_selfie_upload_id'];
                $uploadArray['qc_image'] = $pd['qc_image'];
                $uploadArray['qc_image_url'] = ($pd['qc_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['qc_image'] : env('APP_URL') . "avatar.jpg";
                $uploadArray['qc_on'] = $pd['qc_on'];
                $uploadArray['qc_by'] = $pd['qc_by'];
                $uploadArray['qc_reason'] = $pd['qc_reason'];
                $uploadArray['qc_reason_on'] = $pd['qc_reason_on'];
                $uploadArray['qc_status'] = $pd['qc_status'];
                $resultArray[] = $uploadArray;
            }
        }


        return $resultArray;
    }

    public function getPersonalizedQcUpload($orderItemsId)
    {

        $personalizedUpload = TaskManager::where('order_items_id', $orderItemsId)->get();

        $personalizedArray = [];
        $resultArray = [];

        if (!empty($personalizedUpload)) {

            foreach ($personalizedUpload as $pd) {

                $personalizedArray['id'] = $pd['task_manager_id'];
                $personalizedArray['qc_image'] = $pd['qc_image'];
                $personalizedArray['qc_image_url'] = ($pd['qc_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['qc_image'] : env('APP_URL') . "avatar.jpg";
                $personalizedArray['qc_on'] = $pd['qc_on'];
                $personalizedArray['qc_reason'] = $pd['qc_reason'];
                $personalizedArray['qc_reason_on'] = $pd['qc_reason_on'];
                $personalizedArray['qc_status'] = $pd['qc_status'];
                $personalizedArray['qc_image_history'] = $this->personalizedQcHistory($pd['task_manager_id']);
                $resultArray[] = $personalizedArray;
            }
        }


        return $resultArray;
    }

    public function getPersonalizedAdminQcUpload($orderItemsId)
    {

        $personalizedUpload = TaskManager::where('order_items_id', $orderItemsId)->where('qc_status', '!=', 0)->get();

        $personalizedArray = [];
        $resultArray = [];

        if (!empty($personalizedUpload)) {

            foreach ($personalizedUpload as $pd) {

                $personalizedArray['id'] = $pd['task_manager_id'];
                $personalizedArray['qc_image'] = $pd['qc_image'];
                $personalizedArray['qc_image_url'] = ($pd['qc_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['qc_image'] : env('APP_URL') . "avatar.jpg";
                $personalizedArray['qc_on'] = $pd['qc_on'];
                $personalizedArray['qc_reason'] = $pd['qc_reason'];
                $personalizedArray['qc_reason_on'] = $pd['qc_reason_on'];
                $personalizedArray['qc_status'] = $pd['qc_status'];
                $personalizedArray['qc_image_history'] = $this->personalizedQcHistory($pd['task_manager_id']);
                $resultArray[] = $personalizedArray;
            }
        }


        return $resultArray;
    }

    public function personalizedQcHistory($taskmanagerId)
    {

        $Upload = TaskManagerQcHistory::where('task_manager_id', $taskmanagerId)->select('task_manager_qc_history.*')->get();

        $uploadArray = [];
        $resultArray = [];

        if (!empty($Upload)) {

            foreach ($Upload as $pd) {

                $uploadArray['qc_history_id'] = $pd['task_manager_qc_history_id'];
                $uploadArray['id'] = $pd['task_manager_id'];
                $uploadArray['qc_image'] = $pd['qc_image'];
                $uploadArray['qc_image_url'] = ($pd['qc_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['qc_image'] : env('APP_URL') . "avatar.jpg";
                $uploadArray['qc_on'] = $pd['qc_on'];
                $uploadArray['qc_reason'] = $pd['qc_reason'];
                $uploadArray['qc_reason_on'] = $pd['qc_reason_on'];
                $uploadArray['qc_status'] = $pd['qc_status'];
                $resultArray[] = $uploadArray;
            }
        }


        return $resultArray;
    }

    //Photoframe Qc Details
    public function getPhotoFramePreviewUpload($orderItemsId)
    {

        $selfieUpload = PhotoFrameLabelModel::where('order_items_id', $orderItemsId)->where('preview_image', '!=', '')->get();

        $selfieArray = [];
        $resultArray = [];

        if (!empty($selfieUpload)) {

            foreach ($selfieUpload as $sd) {

                $selfieArray['order_photoframe_upload_label_id'] = $sd['order_photoframe_upload_label_id'];
                $selfieArray['id'] = $sd['order_photoframe_upload_label_id'];
                $selfieArray['preview_image'] = $sd['preview_image'];
                $selfieArray['preview_image_url'] = ($sd['preview_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $sd['preview_image'] : env('APP_URL') . "avatar.jpg";
                $selfieArray['image_url'] = ($sd['preview_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $sd['preview_image'] : env('APP_URL') . "avatar.jpg";
                $selfieArray['preview_status'] = $sd['preview_status'];
                $selfieArray['preview_on'] = $sd['preview_on'];
                $selfieArray['preview_reason'] = $sd['preview_reason'];
                $selfieArray['preview_reason_on'] = $sd['preview_reason_on'];
                $selfieArray['preview_image_history'] = $this->getphotoFramePreviewHistory($sd['order_photoframe_upload_label_id']);
                $resultArray[] = $selfieArray;
            }
        }


        return $resultArray;
    }

    public function getphotoFramePreviewHistory($labelId)
    {

        $Upload = PhotoFramePreviewHistory::where('order_photoframe_upload_label_id', $labelId)->select('order_photoframeupload_preview_history.*')->get();

        $uploadArray = [];
        $resultArray = [];

        if (!empty($Upload)) {

            foreach ($Upload as $pd) {

                $uploadArray['preview_history_id'] = $pd['order_photoframeupload_preview_history_id'];
                $uploadArray['id'] = $pd['order_photoframe_upload_label_id'];
                $uploadArray['preview_image'] = $pd['preview_image'];
                $uploadArray['preview_image_url'] = ($pd['preview_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['preview_image'] : env('APP_URL') . "avatar.jpg";
                $uploadArray['preview_on'] = $pd['preview_on'];
                $uploadArray['preview_by'] = $pd['preview_by'];
                $uploadArray['preview_reason'] = $pd['preview_reason'];
                $uploadArray['preview_reason_on'] = $pd['preview_reason_on'];
                $uploadArray['preview_status'] = $pd['preview_status'];
                $resultArray[] = $uploadArray;
            }
        }


        return $resultArray;
    }


    public function getPersonalizedPreviewUpload($orderItemsId)
    {

        $personalizedUpload = TaskManager::where('order_items_id', $orderItemsId)->where('preview_image', '!=', '')->get();

        $personalizedArray = [];
        $resultArray = [];

        if (!empty($personalizedUpload)) {

            foreach ($personalizedUpload as $pd) {

                $personalizedArray['id'] = $pd['task_manager_id'];
                $personalizedArray['preview_image'] = $pd['preview_image'];
                $personalizedArray['preview_image_url'] = ($pd['preview_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['preview_image'] : env('APP_URL') . "avatar.jpg";
                $personalizedArray['image_url'] = ($pd['preview_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['preview_image'] : env('APP_URL') . "avatar.jpg";
                $personalizedArray['preview_on'] = $pd['preview_on'];
                $personalizedArray['preview_reason'] = $pd['preview_reason'];
                $personalizedArray['preview_reason_on'] = $pd['preview_reason_on'];
                $personalizedArray['preview_status'] = $pd['preview_status'];
                $personalizedArray['preview_image_history'] = $this->personalizedPreviewHistory($pd['task_manager_id']);
                $resultArray[] = $personalizedArray;
            }
        }


        return $resultArray;
    }

    public function personalizedPreviewHistory($taskmanagerId)
    {

        $Upload = TaskManagerPreviewHistory::where('task_manager_id', $taskmanagerId)->select('task_manager_preview_history.*')->get();
        
        $uploadArray = [];
        $resultArray = [];

        if (!empty($Upload)) {

            foreach ($Upload as $pd) {

                $uploadArray['preview_history_id '] = $pd['task_manager_preview_history_id '];
                $uploadArray['id'] = $pd['task_manager_id'];
                $uploadArray['preview_image'] = $pd['preview_image'];
                $uploadArray['preview_image_url'] = ($pd['preview_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['preview_image'] : env('APP_URL') . "avatar.jpg";
                $uploadArray['preview_on'] = $pd['preview_on'];
                $uploadArray['preview_reason'] = $pd['preview_reason'];
                $uploadArray['preview_reason_on'] = $pd['preview_reason_on'];
                $uploadArray['preview_status'] = $pd['preview_status'];
                $resultArray[] = $uploadArray;
            }
        }


        return $resultArray;
    }

    public function getPassportSizePreviewUpload($orderItemsId)
    {

        $selfieUpload = PassportSizeUploadModel::where('order_items_id', $orderItemsId)->where('preview_image', '!=', '')->get();

        $selfieArray = [];
        $resultArray = [];

        if (!empty($selfieUpload)) {

            foreach ($selfieUpload as $sd) {

                $selfieArray['id'] = $sd['order_passport_upload_id'];
                $selfieArray['preview_image'] = $sd['preview_image'];
                $selfieArray['preview_image_url'] = ($sd['preview_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $sd['preview_image'] : env('APP_URL') . "avatar.jpg";
                $selfieArray['image_url'] = ($sd['preview_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $sd['preview_image'] : env('APP_URL') . "avatar.jpg";
                $selfieArray['preview_status'] = $sd['preview_status'];
                $selfieArray['preview_on'] = $sd['preview_on'];
                $selfieArray['preview_reason'] = $sd['preview_reason'];
                $selfieArray['preview_reason_on'] = $sd['preview_reason_on'];
                $selfieArray['preview_image_history'] = $this->passportSizePreviewHistory($sd['order_passport_upload_id']);
                $resultArray[] = $selfieArray;
            }
        }


        return $resultArray;
    }

    public function passportSizePreviewHistory($uploadId)
    {

        $Upload = PassportSizeUploadPreviewHistoryModel::where('order_passport_upload_id', $uploadId)->select('order_passportupload_preview_history.*')->get();

        $uploadArray = [];
        $resultArray = [];

        if (!empty($Upload)) {

            foreach ($Upload as $pd) {

                $uploadArray['preview_history_id '] = $pd['order_passportupload_preview_history_id  '];
                $uploadArray['id'] = $pd['order_passport_upload_id'];
                $uploadArray['preview_image'] = $pd['preview_image'];
                $uploadArray['preview_image_url'] = ($pd['preview_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['preview_image'] : env('APP_URL') . "avatar.jpg";
                $uploadArray['preview_on'] = $pd['preview_on'];
                $uploadArray['preview_reason'] = $pd['preview_reason'];
                $uploadArray['preview_reason_on'] = $pd['preview_reason_on'];
                $uploadArray['preview_status'] = $pd['preview_status'];
                $resultArray[] = $uploadArray;
            }
        }


        return $resultArray;
    }

    public function getPhotoPrintPreviewUpload($orderItemsId)
    {

        $selfieUpload = PhotoPrintUploadModel::where('order_items_id', $orderItemsId)->where('preview_image', '!=', '')->get();

        $selfieArray = [];
        $resultArray = [];

        if (!empty($selfieUpload)) {

            foreach ($selfieUpload as $sd) {

                $selfieArray['id'] = $sd['order_photoprint_upload_id'];
                $selfieArray['preview_image'] = $sd['preview_image'];
                $selfieArray['preview_image_url'] = ($sd['preview_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $sd['preview_image'] : env('APP_URL') . "avatar.jpg";
                $selfieArray['image_url'] = ($sd['preview_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $sd['preview_image'] : env('APP_URL') . "avatar.jpg";
                $selfieArray['preview_status'] = $sd['preview_status'];
                $selfieArray['preview_on'] = $sd['preview_on'];
                $selfieArray['preview_reason'] = $sd['preview_reason'];
                $selfieArray['preview_reason_on'] = $sd['preview_reason_on'];
                $selfieArray['preview_image_history'] = $this->photoPrintPreviewHistory($sd['order_photoprint_upload_id']);
                $resultArray[] = $selfieArray;
            }
        }


        return $resultArray;
    }

    public function photoPrintPreviewHistory($uploadId)
    {

        $Upload = PhotoPrintUploadPreviewHistoryModel::where('order_photoprint_upload_id', $uploadId)->select('order_photoprint_preview_history.*')->get();

        $uploadArray = [];
        $resultArray = [];

        if (!empty($Upload)) {

            foreach ($Upload as $pd) {

                $uploadArray['preview_history_id '] = $pd['order_photoprint_preview_history_id  '];
                $uploadArray['id'] = $pd['order_photoprint_upload_id'];
                $uploadArray['preview_image'] = $pd['preview_image'];
                $uploadArray['preview_image_url'] = ($pd['preview_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['preview_image'] : env('APP_URL') . "avatar.jpg";
                $uploadArray['preview_on'] = $pd['preview_on'];
                $uploadArray['preview_reason'] = $pd['preview_reason'];
                $uploadArray['preview_reason_on'] = $pd['preview_reason_on'];
                $uploadArray['preview_status'] = $pd['preview_status'];
                $resultArray[] = $uploadArray;
            }
        }


        return $resultArray;
    }

    public function getSelfiePreviewUpload($orderItemsId)
    {

        $selfieUpload = SelfieUploadModel::where('order_items_id', $orderItemsId)->where('preview_image', '!=', '')->get();

        $selfieArray = [];
        $resultArray = [];

        if (!empty($selfieUpload)) {

            foreach ($selfieUpload as $sd) {

                $selfieArray['id'] = $sd['order_selfie_upload_id'];
                $selfieArray['preview_image'] = $sd['preview_image'];
                $selfieArray['preview_image_url'] = ($sd['preview_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $sd['preview_image'] : env('APP_URL') . "avatar.jpg";
                $selfieArray['image_url'] = ($sd['preview_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $sd['preview_image'] : env('APP_URL') . "avatar.jpg";
                $selfieArray['preview_status'] = $sd['preview_status'];
                $selfieArray['preview_on'] = $sd['preview_on'];
                $selfieArray['preview_reason'] = $sd['preview_reason'];
                $selfieArray['preview_reason_on'] = $sd['preview_reason_on'];
                $selfieArray['preview_image_history'] = $this->selfieUploadPreviewHistory($sd['order_selfie_upload_id']);
                $resultArray[] = $selfieArray;
            }
        }


        return $resultArray;
    }

    public function selfieUploadPreviewHistory($uploadId)
    {

        $Upload = SelfieUploadPreviewHistoryModel::where('order_selfie_upload_id', $uploadId)->select('order_selfieupload_preview_history.*')->get();

        $uploadArray = [];
        $resultArray = [];

        if (!empty($Upload)) {

            foreach ($Upload as $pd) {

                $uploadArray['preview_history_id '] = $pd['order_selfieupload_preview_history_id  '];
                $uploadArray['id'] = $pd['order_selfie_upload_id'];
                $uploadArray['preview_image'] = $pd['preview_image'];
                $uploadArray['preview_image_url'] = ($pd['preview_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['preview_image'] : env('APP_URL') . "avatar.jpg";
                $uploadArray['preview_on'] = $pd['preview_on'];
                $uploadArray['preview_reason'] = $pd['preview_reason'];
                $uploadArray['preview_reason_on'] = $pd['preview_reason_on'];
                $uploadArray['preview_status'] = $pd['preview_status'];
                $resultArray[] = $uploadArray;
            }
        }


        return $resultArray;
    }
}
