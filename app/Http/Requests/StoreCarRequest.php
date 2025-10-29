<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCarRequest extends FormRequest
{
    private const REGISTRATION_NUMBER_MAX_LENGTH = 7;
    private const REGISTRATION_NUMBER_PATTERN = '/^[A-Z]{2}[0-9]{3}[A-Z]{2}$/';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'registration_number' => [
                'required_if:is_registered,true',
                'nullable',
                'string',
                'max:' . self::REGISTRATION_NUMBER_MAX_LENGTH,
                'regex:' . self::REGISTRATION_NUMBER_PATTERN,
            ],
            'is_registered' => 'boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'validation.carNameRequired',
            'registration_number.required_if' => 'validation.registrationNumberRequired',
            'registration_number.max' => 'validation.registrationNumberMax:' . self::REGISTRATION_NUMBER_MAX_LENGTH,
            'registration_number.regex' => 'validation.registrationNumberFormat',
        ];
    }
}
