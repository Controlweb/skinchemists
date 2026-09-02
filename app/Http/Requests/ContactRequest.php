<?php

namespace App\Http\Requests;

use App\Models\ContactMessage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge(['phone' => preg_replace('/[\s.\-]/', '', $this->input('phone'))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            // One of the two is required: we have to be able to answer.
            'email' => ['nullable', 'required_without:phone', 'email:filter', 'max:150'],
            'phone' => ['nullable', 'required_without:email', 'string', 'regex:/^(?:\+212|0)[5-7]\d{8}$/'],
            'subject' => ['required', Rule::in(array_keys(ContactMessage::SUBJECTS))],
            'order_number' => ['nullable', 'string', 'max:40'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],

            // Honeypot: a real visitor never fills a hidden field.
            'website' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required_without' => 'Laissez un email ou un téléphone, sinon nous ne pourrons pas vous répondre.',
            'phone.required_without' => 'Laissez un téléphone ou un email, sinon nous ne pourrons pas vous répondre.',
            'phone.regex' => 'Numéro de téléphone marocain invalide (ex. 06 61 22 84 10).',
            'message.min' => 'Merci de détailler un peu votre demande.',
            'website.prohibited' => 'Requête refusée.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'email' => 'email',
            'phone' => 'téléphone',
            'subject' => 'sujet',
            'message' => 'message',
        ];
    }
}
