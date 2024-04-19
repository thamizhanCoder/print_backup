<?php

namespace App\Http\Requests;
 
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
 
use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends FormRequest
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
			'name' => 'required|string',
			'email' => 'required|email',
              'mobile_no' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|digits:10',
              'acl_role_id' => 'required|numeric',
            //   'password'         => 'nullable|required|min:8',
            
        ];
    }
 

     public function messages()
    {
        return [
            'mobile_no' => 'Please enter proper mobile number',

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
