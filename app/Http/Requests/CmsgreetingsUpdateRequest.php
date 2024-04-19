<?php

namespace App\Http\Requests;
 
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
 
use Illuminate\Foundation\Http\FormRequest;

class CmsgreetingsUpdateRequest extends FormRequest
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
           
            'greeting_image' => 'required',
            //'greeting_url' => 'nullable|url|unique:cms_greeting',
            'from_date' => ['required', 'after_or_equal:' .  Date('Y-m-d')],
            'to_date' => ['required', 'after:from_date'],
        ];
    }
 

    // public function messages()
    // {
    //     return [
    //         'company_name.unique' => 'gstpercentage name already exist',
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