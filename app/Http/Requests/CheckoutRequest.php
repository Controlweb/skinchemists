<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            // Accept how people actually type it: 06 61 22 84 10, +212 661…
            $this->merge(['phone' => preg_replace('/[\s.\-]/', '', $this->input('phone'))]);
        }
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'regex:/^(?:\+212|0)[5-7]\d{8}$/'],
            'email' => ['nullable', 'email:filter', 'max:150'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:80'],
            'zip' => ['nullable', 'string', 'max:12'],
            'shipping_method' => ['required', 'in:standard,express'],
            'coupon_code' => ['nullable', 'string', 'max:40'],
            'note' => ['nullable', 'string', 'max:500'],

            // Honeypot: a real customer never fills a hidden field.
            'website' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Numéro de téléphone marocain invalide (ex. 06 61 22 84 10).',
            'website.prohibited' => 'Requête refusée.',
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'prénom',
            'last_name' => 'nom',
            'phone' => 'téléphone',
            'address' => 'adresse',
            'city' => 'ville',
        ];
    }
}
