<?php

namespace App\Http\Requests;
 
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
 
use Illuminate\Foundation\Http\FormRequest;

class orderdispatchRequest extends FormRequest
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
           
            'courier_no' => 'required',
            'courier_name' => 'required',

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