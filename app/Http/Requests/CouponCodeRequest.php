<?php

namespace App\Http\Requests;
 
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
 
use Illuminate\Foundation\Http\FormRequest;

class CouponCodeRequest extends FormRequest
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
			'coupon_code' => 'required|string',
			'percentage' => 'required|numeric',
            'set_min_amount' => 'required|numeric',
            'customer_eligibility' => 'required|numeric',
            'start_date' => ['required', 'after_or_equal:' .  Date('Y-m-d')],
            'start_time' => 'required|date_format:H:i',
            'set_end_date' => ['required', 'after_or_equal:' .  Date('Y-m-d')],
            'set_end_time' => 'required|date_format:H:i'

        ];
    }
 

    public function messages()
    {
        return [
            'start_time.date_format' => 'start time must be a proper format',
            'set_end_time.date_format' => 'set end time must be a proper format',

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
