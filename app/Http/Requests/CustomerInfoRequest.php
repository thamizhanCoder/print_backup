<?php

namespace App\Http\Requests;
 
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
 
use Illuminate\Foundation\Http\FormRequest;

class CustomerInfoRequest extends FormRequest
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
            'customer_first_name' => 'required',
            'customer_last_name' => 'required',
            'mobile_no' => 'required|numeric|digits:10|regex:/^[6-9]\d{9}$/',
			'email' => 'required|regex:/^\w+([\.-]?\w+)*@[a-z]+\.[a-z]{2,3}$/'
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
