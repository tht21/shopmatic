<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class StoreProduct extends FormRequest
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
            'name' => 'required|string',
            'associated_sku' => 'required',
            'category_id' => 'required|exists:categories,id',
            'html_description' => 'required',
            'prices' => 'array',
            'prices.*.price' => 'required|numeric|min:0,5',
            'prices.*.type' => 'required', // use in_array with ProductPriceType constant
            'images' => 'array',
            'attributes' => 'array',
            'variants' => 'required|array|min:1',
            'variants.*.name' => 'required|distinct',
            'variants.*.inventory' => 'required',
            'variants.*.sku' => 'required|distinct',
            'variants.*.weight' => 'numeric|min:0.5',
            'variants.*.width' => 'numeric|min:0.5',
            'variants.*.length' => 'numeric|min:0.5',
            'variants.*.height' => 'numeric|min:0.5',
            'variants.*.prices' => 'required|array|min:1',
            'variants.*.prices.*.price' => 'required|numeric|min:0,5',
            'variants.*.prices.*.type' => 'required', // use in_array with ProductPriceType constant
            'variants.*.images' => 'array',
            'variants.*.attributes' => 'array'
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
