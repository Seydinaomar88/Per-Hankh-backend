<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'title' => [
                'required',
                'string',
                'max:255'
            ],

            'description' => [
                'nullable',
                'string'
            ],

            'due_date' => [
                'nullable',
                'date'
            ],

            'priority' => [
                'nullable',
                'in:low,medium,high'
            ],

            'assigned_to' => [
                'nullable',
                'exists:users,id'
            ],

            'position' => [
                'nullable',
                'integer'
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