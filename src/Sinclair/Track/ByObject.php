<?php

namespace Sinclair\Track;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class ByObject
 * @package Sinclair\Track
 */
class ByObject extends FormRequest
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
            'object_class' => 'required',
            'object_id'    => 'required'
        ];
    }
}