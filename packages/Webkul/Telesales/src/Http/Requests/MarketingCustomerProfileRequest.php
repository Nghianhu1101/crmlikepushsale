<?php

namespace Webkul\Telesales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarketingCustomerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'source_id' => ['nullable', 'integer', 'exists:lead_sources,id'],
            'sales_owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'stage_id' => ['nullable', 'integer', 'exists:lead_pipeline_stages,id'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }
}
