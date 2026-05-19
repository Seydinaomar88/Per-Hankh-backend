<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'title' => [
                'sometimes',
                'string',
                'max:255'
            ],

            'description' => [
                'nullable',
                'string'
            ],

            'priority' => [
                'nullable',
                'in:low,medium,high'
            ],

            'due_date' => [
                'nullable',
                'date'
            ],

            'position' => [
                'nullable',
                'integer'
            ],

            'kanban_column_id' => [
                'nullable',
                'exists:kanban_columns,id'
            ],

            'assigned_to' => [
                'nullable',
                'exists:users,id'
            ],

            'tags' => [
                'nullable',
                'array'
            ],

            'tags.*' => [
                'string',
                'max:50'
            ],
        ];
    }
}