<?php

namespace App\Http\Requests;
 
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
 
use Illuminate\Foundation\Http\FormRequest;

class BulkOrderEnquiryRequest extends FormRequest
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
            'customer_type' => 'required | numeric',
            'contact_person_name' => 'required|string|max:250|regex:/^[a-zA-Z\s]+$/',
            'mobile_no' => 'required | numeric | digits:10 | starts_with:6,7,8,9',
            'alternative_mobile_no' => 'nullable | numeric | digits:10 | starts_with:6,7,8,9',
            'email' => 'nullable | email',
            'company_name' => 'required_if:customer_type,2| string | max:250',
            'state_id' => 'required | numeric',
            'district_id' => 'required | numeric',
        ];
    }

    public function messages()
    {
        return [
            'mobile_no.starts_with' => 'The mobile number must starts with 6, 7, 8, 9.',
            'email.email' => 'The email must valid mail address',
            'company_name.required_if' => 'The company name field is required',
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
