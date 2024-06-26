<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class SelfieAlbumRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function rules()
    {

        return [

            'product_name' => 'required|string',
            // 'category_id' => 'required|numeric',
            'no_of_images' => 'required|numeric',
            // 'Material Name' => 'required|string',
            // 'Mark as default' => 'required|string',
            'gst_percentage' => 'required|numeric',
            'customer_description' => 'required|string',
            'designer_description' => 'required|string',
            'help_url' => ['required','regex:/^(?:https?:\/\/)?(?:m\.|www\.)?(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))((\w|-){11})(?:\S+)?$/i'],
            // 'mrp' => 'required|numeric|min:1',
            // 'selling_price' => 'required|numeric|min:1|lte:mrp',
        ];
    }

    protected function failedValidation(Validator $validator)
    {

        $errors = $validator->errors();
        throw new HttpResponseException(response()->json([
            "keyword" => 'failed',
            "message" => $errors->first(),
            "data" => [],
        ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY));

    }
}