<?php

namespace App\Http\Requests;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

use Illuminate\Foundation\Http\FormRequest;

class QuoteCreateRequest extends FormRequest
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
            'billing_customer_name' => 'required',
            'billing_mobile_number' => 'required | numeric | digits:10 | starts_with:6,7,8,9',
            'billing_gst_no' => ['required', 'regex:/^[0-9]{2}[A-Za-z]{5}[0-9]{4}[A-Za-z]{1}[0-9A-Za-z]{1}[Z]{1}[0-9A-Za-z]{1}$/'],
            'billing_pincode' => 'required',
            'billing_address_1' => 'required',
            'billing_address_2' => 'required',
            'billing_state_id' => 'required',
            'billing_city_id' => 'required',
            'quote_details' => 'required',  
            'sub_total' => 'required | numeric',
            'round_off' => 'required | numeric',
            'grand_total' => 'required | numeric',

            

        ];
    }


    public function messages()
    {
        return [
            'bulk_order_enquiry_id.required' => 'The enquiry id field is required',
            'bulk_order_enquiry_id.numeric' => 'The enquiry id field is must be number',
            'billing_gst_no.regex' => 'The :attribute is not a valid GST number.',
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
