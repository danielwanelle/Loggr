<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexLogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'trace_id' => ['nullable', 'string', 'uuid'],
            'level' => [
                'nullable',
                'string',
                Rule::in([
                    'debug',
                    'info',
                    'notice',
                    'warning',
                    'error',
                    'critical',
                    'alert',
                    'emergency'
                ])
            ],
            'service_name' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'trace_id.uuid' => 'O campo trace_id deve ser um UUID válido.',
            'level.in' => 'O campo level deve ser um dos seguintes valores: debug, info, notice, warning, error, critical, alert, emergency.',
            'service_name.max' => 'O campo service_name não pode ter mais de 255 caracteres.',
            'date_from.date' => 'O campo date_from deve ser uma data válida.',
            'date_to.date' => 'O campo date_to deve ser uma data válida.',
            'date_to.after_or_equal' => 'O campo date_to deve ser posterior ou igual a date_from.',
            'per_page.integer' => 'O campo per_page deve ser um número inteiro.',
            'per_page.min' => 'O campo per_page deve ser no mínimo 1.',
            'per_page.max' => 'O campo per_page deve ser no máximo 100.',
        ];
    }
}
