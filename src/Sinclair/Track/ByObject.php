<?php

namespace Sinclair\Track;

use Illuminate\Foundation\Http\FormRequest;

class ByObject extends FormRequest
{
    public function rules()
    {
        return [
            'object_class' => 'required|string',
            'object_id'    => 'required|integer'
        ];
    }
}