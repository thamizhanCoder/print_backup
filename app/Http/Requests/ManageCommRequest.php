<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;




class ManageCommRequest extends FormRequest
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
        // 'email' => 'email address',
    ];
}

    public function rules()
    {

        return [

            // 'subject' => 'required',
			'messages' => 'required',
            // 'attachments' => 'required',
            // 'employee_id' => 'required|numeric',
            // 'task_manager_id' => 'required|numeric',    


           ];
    }

    // public function messages()
    // {
    //     return [
    //         // 'mobile_no' => 'Please enter proper mobile number',

    //      ];
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
