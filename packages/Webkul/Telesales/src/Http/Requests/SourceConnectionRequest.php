<?php

namespace Webkul\Telesales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SourceConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'channel' => ['required', Rule::in(['website', 'facebook', 'api', 'other'])],
            'source_id' => ['nullable', 'integer', 'exists:lead_sources,id'],
            'source_name' => ['nullable', 'string', 'max:100'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'marketing_owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'campaign' => ['nullable', 'string', 'max:150'],
            'map_phone' => ['required', 'string', 'max:100'],
            'map_name' => ['nullable', 'string', 'max:100'],
            'map_product' => ['nullable', 'string', 'max:100'],
            'map_message' => ['nullable', 'string', 'max:100'],
            'map_external_id' => ['nullable', 'string', 'max:100'],
        ];
    }
}
