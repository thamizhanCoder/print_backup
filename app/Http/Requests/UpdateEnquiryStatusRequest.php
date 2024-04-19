<?php

namespace App\Http\Requests;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEnquiryStatusRequest extends FormRequest
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
            'bulk_order_enquiry_id' => 'required | numeric',
            'enquiry_status' => 'required | numeric',
            'enquiry_date' => 'nullable | date_format:Y-m-d', // Example: Accepts date in 'YYYY-MM-DD' format
        ];
    }


    public function messages()
    {
        return [
            'bulk_order_enquiry_id.required' => 'The enquiry id field is required',
            'bulk_order_enquiry_id.numeric' => 'The enquiry id field is must be number',
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
