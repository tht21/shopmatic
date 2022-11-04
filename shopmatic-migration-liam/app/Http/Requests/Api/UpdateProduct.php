<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class UpdateProduct extends FormRequest
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
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'string',
            'associated_sku' => 'string',
            'category_id' => 'exists:categories,id',
            'variants' => 'array',
            'variants.*.name' => 'distinct',
            'variants.*.sku' => 'distinct',
            'variants.*.weight' => 'numeric|min:0.5',
            'variants.*.width' => 'numeric|min:0.5',
            'variants.*.length' => 'numeric|min:0.5',
            'variants.*.height' => 'numeric|min:0.5',
            'variants.*.price' => 'numeric'
        ];
    }

    /**
     * @TODO - move this to a call and extend
     * format failed validation response
     *
     * @param Validator $validator
     * @return void
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->all();

        throw new HttpResponseException(response()->json([
            'meta' => [
                'error' => true,
                'message' => implode(' ', $errors),
                'status_code' => Response::HTTP_BAD_REQUEST,
            ]
        ]));
    }
}
