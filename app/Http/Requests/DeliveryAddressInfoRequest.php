<?php

namespace App\Http\Requests;
 
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
 
use Illuminate\Foundation\Http\FormRequest;

class DeliveryAddressInfoRequest extends FormRequest
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
            'billing_customer_first_name' => 'required',
            //'billing_customer_last_name' => 'required',
	    'billing_email' => 'required|regex:/^\w+([\.-]?\w+)*@[a-z]+\.[a-z]{2,3}$/',
            'billing_mobile_number' => 'required|numeric|digits:10|regex:/^[6-9]\d{9}$/',
            'billing_alt_mobile_number' => 'nullable|numeric|digits:10|regex:/^[6-9]\d{9}$/',
            'billing_gst_no' => 'nullable|regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
            'billing_address_1' => 'required',
            'billing_landmark' => 'required',
            'billing_state_id' => 'required',
            // 'billing_city_id' => 'required',
            'billing_pincode' => 'required|numeric|digits:6'
        ];
    }
 

    // public function messages()
    // {
    //     return [
    //         'company_name.unique' => 'Category name already exist',
    //         'state_id.required' => 'State is required',
    //         'city_id.required' => 'City is required',


    //     ];
    // }

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
