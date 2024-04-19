<?php

namespace App\Http\Requests;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

use Illuminate\Foundation\Http\FormRequest;

class CreateEnquiryRequest extends FormRequest
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
            'contact_person_name' => 'required | string | max:250 | regex:/^[a-zA-Z\s]+$/',
            'mobile_number' => 'required | numeric | digits:10 | starts_with:6,7,8,9',
            'alternative_mobile_number' => 'nullable | numeric | digits:10 | starts_with:6,7,8,9',
            'email' => 'nullable | email',
            'company_name' => 'required_if:customer_type,2| string | max:250',
            'state' => 'required | numeric',
            'city' => 'required | numeric',
            'message' => 'required'
        ];
    }


    public function messages()
    {
        return [
            'mobile_number.starts_with' => 'The mobile number must starts with 6, 7, 8, 9.',
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
