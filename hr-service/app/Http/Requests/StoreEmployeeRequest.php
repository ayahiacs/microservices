<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // authorization will be handled elsewhere or open for now
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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'salary_per_annum' => ['required', 'numeric', 'min:0'],
            'country' => ['required', 'string', 'in:USA,Germany'],
            'country_data' => ['nullable', 'array'],
        ];

        // add country-specific requirements
        $rules['country_data.ssn'] = ['required_if:country,USA', 'string'];
        $rules['country_data.address'] = ['required_if:country,USA', 'string'];

        $rules['country_data.tax_id'] = ['required_if:country,Germany', 'string'];
        $rules['country_data.goal'] = ['required_if:country,Germany', 'string'];

        return $rules;
    }
}
