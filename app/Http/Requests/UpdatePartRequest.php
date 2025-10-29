<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePartRequest extends FormRequest
{
    private const MAX_STRING_LENGTH = 255;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:' . self::MAX_STRING_LENGTH,
            'serialnumber' => 'required|string|max:' . self::MAX_STRING_LENGTH,
            'car_id' => 'required|exists:cars,id',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Part name is required',
            'name.max' => 'Part name cannot exceed ' . self::MAX_STRING_LENGTH . ' characters',
            'serialnumber.required' => 'Serial number is required',
            'serialnumber.max' => 'Serial number cannot exceed ' . self::MAX_STRING_LENGTH . ' characters',
            'car_id.required' => 'Car is required',
            'car_id.exists' => 'Selected car does not exist',
        ];
    }
}
