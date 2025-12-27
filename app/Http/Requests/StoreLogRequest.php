<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLogRequest extends FormRequest
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
            'message' => ['required', 'string'],
            'level' => [
                'required',
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
            'service_name' => ['required', 'string', 'max:255'],
            'timestamp' => ['nullable', 'date'],
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
            'message.required' => 'O campo message é obrigatório.',
            'level.required' => 'O campo level é obrigatório.',
            'level.in' => 'O campo level deve ser um dos seguintes valores: debug, info, notice, warning, error, critical, alert, emergency.',
            'service_name.required' => 'O campo service_name é obrigatório.',
            'service_name.max' => 'O campo service_name não pode ter mais de 255 caracteres.',
            'timestamp.date' => 'O campo timestamp deve ser uma data válida.',
        ];
    }
}
