<?php

namespace Webkul\Telesales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RankingFiltersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'date_basis' => ['nullable', Rule::in(['data_received', 'order_closed'])],
            'revenue_basis' => ['nullable', Rule::in(['gross', 'net'])],
            'customer_type' => ['nullable', Rule::in(['all', 'new', 'old'])],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'source_id' => ['nullable', 'integer', 'exists:lead_sources,id'],
            'campaign' => ['nullable', 'string', 'max:150'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'sales_owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'marketing_owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', Rule::in(array_keys(config('telesales.order_statuses')))],
            'perspective' => ['nullable', Rule::in(['sale', 'marketing'])],
        ];
    }
}
