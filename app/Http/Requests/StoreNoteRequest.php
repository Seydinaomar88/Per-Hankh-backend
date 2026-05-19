<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNoteRequest extends FormRequest
{
    /**
     * AUTHORIZE
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * RULES
     */
    public function rules(): array
    {
        return [

            'title' => [
                'required',
                'string',
                'max:255'
            ],

            'content' => [
                'required',
                'string'
            ],
        ];
    }
}