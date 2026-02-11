<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FlightSearchRequest extends FormRequest
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
            'from' => ['required', 'string', 'max:10'],
            'to' => ['required', 'string', 'max:10'],
            'outbound_date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . now()->addDays(300)->format('Y-m-d')],
            'return_date' => ['nullable', 'date', 'after:outbound_date', 'before_or_equal:' . now()->addDays(300)->format('Y-m-d')],
            'trip_type' => ['required', 'in:one-way,return'],
            'adults' => ['required', 'integer', 'min:1', 'max:9'],
            'booking_class' => ['required', 'string', 'in:economy,premium_economy,business,first'],
            'max_stops' => ['required', 'integer', 'min:0', 'max:3'],
            'airlines' => ['nullable', 'string'],
        ];
    }
}
