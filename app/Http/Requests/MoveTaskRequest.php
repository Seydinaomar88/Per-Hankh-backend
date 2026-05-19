<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            /**
             * nouvelle colonne
             */
            'kanban_column_id' => [
                'required',
                'exists:kanban_columns,id'
            ],

            /**
             * nouvelle position
             */
            'position' => [
                'required',
                'integer',
                'min:0'
            ],
        ];
    }
}