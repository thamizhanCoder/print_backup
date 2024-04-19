<?php

namespace App\Http\Requests;
 
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
 
use Illuminate\Foundation\Http\FormRequest;

class PhotoPrintRequest extends FormRequest
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
			'product_name' => 'required|string',
            'print_size' => 'required|numeric',
            'customer_description' => 'required|string',
            'designer_description' => 'required|string',
            'help_url' => ['required','regex:/^(?:https?:\/\/)?(?:m\.|www\.)?(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))((\w|-){11})(?:\S+)?$/i'],
            'mrp' => 'required|numeric|min:1',
            'first_copy_selling_price' => 'required|numeric|min:1|lte:mrp',
            'additional_copy_selling_price' => 'required|numeric|min:1|lte:first_copy_selling_price',
            'gst_percentage' => 'required|numeric',
            'weight' => ['required','numeric','regex:/^\d+(\.\d{1})?$/'],
			
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