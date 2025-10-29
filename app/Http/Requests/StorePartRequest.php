<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePartRequest extends FormRequest
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
            'name.required' => 'validation.partNameRequired',
            'name.max' => 'validation.partNameMax:' . self::MAX_STRING_LENGTH,
            'serialnumber.required' => 'validation.serialNumberRequired',
            'serialnumber.max' => 'validation.serialNumberMax:' . self::MAX_STRING_LENGTH,
            'car_id.required' => 'validation.carRequired',
            'car_id.exists' => 'validation.carNotExists',
        ];
    }
}
