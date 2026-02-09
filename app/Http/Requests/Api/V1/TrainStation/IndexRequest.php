<?php

namespace App\Http\Requests\Api\V1\TrainStation;

use Illuminate\Foundation\Http\FormRequest;

class IndexRequest extends FormRequest
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
            'page' => 'sometimes|int|min:1',
            'perPage' => 'sometimes|int|between:1,100',
            'search' => 'sometimes|string|max:255',
        ];
    }
}
