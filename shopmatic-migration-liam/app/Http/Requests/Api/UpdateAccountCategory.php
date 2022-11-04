<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\ApiFormRequest;

class UpdateAccountCategory extends ApiFormRequest
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
            'account_id' => 'required',
            'parent_id' => 'required',
            'name' => 'required|string',
            'is_leaf' => 'required|boolean',
            'category_id' => 'required|exists:categories,id',
            'breadcrumb' => 'required'
        ];
    }
}
