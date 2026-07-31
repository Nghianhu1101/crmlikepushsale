<?php

namespace Webkul\Telesales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Contact\Support\PhoneNormalizer;

class IncomingLeadRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => app(PhoneNormalizer::class)->normalize($this->input('phone')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! app(PhoneNormalizer::class)->isValid((string) $value)) {
                        $fail('Số điện thoại không hợp lệ.');
                    }
                },
            ],
            'name' => ['nullable', 'string', 'max:255'],
            'product' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:100'],
            'campaign' => ['nullable', 'string', 'max:150'],
            'message' => ['nullable', 'string', 'max:5000'],
            'external_id' => ['nullable', 'string', 'max:191'],
            'marketing_external_id' => ['nullable', 'string', 'max:191'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
        ];
    }
}
