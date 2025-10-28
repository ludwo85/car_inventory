<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePartRequest extends FormRequest
{
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
            'name' => 'required|string|max:255',
            'serialnumber' => 'required|string|max:255',
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
            'serialnumber.required' => 'Serial number is required',
            'car_id.required' => 'Car is required',
            'car_id.exists' => 'Selected car does not exist',
        ];
    }
}
