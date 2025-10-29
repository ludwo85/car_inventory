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
            'name.required' => 'Car name is required',
            'registration_number.required_if' => 'Registration number is required for registered cars',
            'registration_number.max' => 'Registration number cannot exceed ' . self::REGISTRATION_NUMBER_MAX_LENGTH . ' characters',
            'registration_number.regex' => 'Registration number must be in format AA000XX (2 letters, 3 digits, 2 letters)',
        ];
    }
}
