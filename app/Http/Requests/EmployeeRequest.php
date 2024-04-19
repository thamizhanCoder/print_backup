<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;




class EmployeeRequest extends FormRequest
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

    /**
 * Get custom attributes for validator errors.
 *
 * @return array
 */
public function attributes()
{
    return [
        'email' => 'email address',
    ];
}

    public function rules()
    {

        return [

            // 'product_name' => 'required|string',
            // 'category_id' => 'required|numeric',
            // 'gst_percentage' => 'required|numeric',
            // 'product_description' => 'required|string',
            // 'product_specification' => 'required|string'
            'employee_type' => 'required|string',
            'employee_name' => 'required|string',
            'mobile_no' => 'required|numeric|regex:/^([0-9\s\-\+\(\)]*)$/|digits:10',
            // 'email' => [
            //     'required',  'email:rfc,dns'],
                'email' => 'required|regex:/(.+)@(.+)\.(.+)/i'


            // 'email' => 'required|regex:/(.+)@(.+)\.(.+)/i',

           
             
            // 'mobile_no' => 'required|numeric',
            // 'employee_image' => 'required|string'


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
