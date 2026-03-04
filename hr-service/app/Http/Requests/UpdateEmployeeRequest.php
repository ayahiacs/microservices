<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
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
        $rules = [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'salary_per_annum' => ['sometimes', 'numeric', 'min:0'],
            'country' => ['sometimes', 'string', 'in:USA,Germany'],
            'country_data' => ['nullable', 'array'],
        ];

        // country-specific optional data
        $rules['country_data.ssn'] = ['required_if:country,USA', 'string'];
        $rules['country_data.address'] = ['required_if:country,USA', 'string'];
        $rules['country_data.tax_id'] = ['required_if:country,Germany', 'string'];
        $rules['country_data.goal'] = ['required_if:country,Germany', 'string'];

        return $rules;
    }
}
