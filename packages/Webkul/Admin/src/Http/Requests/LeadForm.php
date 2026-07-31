<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Attribute\Repositories\AttributeValueRepository;
use Webkul\Contact\Support\PhoneNormalizer;
use Webkul\Core\Contracts\Validations\Decimal;

class LeadForm extends FormRequest
{
    /**
     * @var array
     */
    protected $rules = [];

    /**
     * Create a new form request instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected AttributeValueRepository $attributeValueRepository,
        protected PhoneNormalizer $phoneNormalizer
    ) {}

    /**
     * Prepare the streamlined telesales payload.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('quick_add')) {
            return;
        }

        $phone = $this->phoneNormalizer->normalize(
            data_get($this->input('person', []), 'contact_numbers.0.value')
        );

        $name = trim((string) data_get($this->input('person', []), 'name'));
        $name = $name ?: 'Khách '.substr($phone, -4);

        $products = collect($this->input('products', []))
            ->filter(fn ($product) => ! empty($product['product_id']))
            ->all();

        $this->merge([
            'title' => $name,
            'user_id' => auth()->guard('user')->id(),
            'person' => array_merge($this->input('person', []), [
                'name' => $name,
                'emails' => [],
                'contact_numbers' => [[
                    'value' => $phone,
                    'label' => 'work',
                ]],
                'user_id' => auth()->guard('user')->id(),
            ]),
            'products' => $products,
        ]);
    }

    /**
     * Determine if the product is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        foreach (['leads', 'persons'] as $key => $entityType) {
            $attributes = $this->attributeRepository->scopeQuery(function ($query) use ($entityType) {
                $attributeCodes = $entityType == 'persons'
                    ? array_keys(request('person') ?? [])
                    : array_keys(request()->all());

                $query = $query->whereIn('code', $attributeCodes)
                    ->where('entity_type', $entityType);

                if (request()->has('quick_add')) {
                    $query = $query->where('quick_add', 1);
                }

                return $query;
            })->get();

            foreach ($attributes as $attribute) {
                if ($entityType == 'persons') {
                    $attribute->code = 'person.'.$attribute->code;
                }

                $validations = [];

                if ($attribute->type == 'boolean') {
                    continue;
                } elseif ($attribute->type == 'address') {
                    if (! $attribute->is_required) {
                        continue;
                    }

                    $validations = [
                        $attribute->code.'.address' => 'required',
                        $attribute->code.'.country' => 'required',
                        $attribute->code.'.state' => 'required',
                        $attribute->code.'.city' => 'required',
                        $attribute->code.'.postcode' => 'required',
                    ];
                } elseif ($attribute->type == 'email') {
                    $validations = [
                        $attribute->code => [$attribute->is_required ? 'required' : 'nullable'],
                        $attribute->code.'.*.value' => [$attribute->is_required ? 'required' : 'nullable', 'email'],
                        $attribute->code.'.*.label' => $attribute->is_required ? 'required' : 'nullable',
                    ];
                } elseif ($attribute->type == 'phone') {
                    $validations = [
                        $attribute->code => [$attribute->is_required ? 'required' : 'nullable'],
                        $attribute->code.'.*.value' => [$attribute->is_required ? 'required' : 'nullable'],
                        $attribute->code.'.*.label' => $attribute->is_required ? 'required' : 'nullable',
                    ];
                } else {
                    $validations[$attribute->code] = [$attribute->is_required ? 'required' : 'nullable'];

                    if ($attribute->type == 'text' && $attribute->validation) {
                        array_push($validations[$attribute->code],
                            $attribute->validation == 'decimal'
                            ? new Decimal
                            : $attribute->validation
                        );
                    }

                    if ($attribute->type == 'price') {
                        array_push($validations[$attribute->code], new Decimal);
                    }
                }

                if ($attribute->is_unique) {
                    array_push($validations[in_array($attribute->type, ['email', 'phone'])
                        ? $attribute->code.'.*.value'
                        : $attribute->code
                    ], function ($field, $value, $fail) use ($attribute, $entityType) {
                        if (! $this->attributeValueRepository->isValueUnique(
                            $entityType == 'persons' ? request('person.id') : $this->id,
                            $attribute->entity_type,
                            $attribute,
                            request($field)
                        )
                        ) {
                            $fail('The value has already been taken.');
                        }
                    });
                }

                $this->rules = array_merge($this->rules, $validations);
            }
        }

        $telesalesRules = $this->has('quick_add') ? [] : [
            'person.contact_numbers' => ['required', 'array', 'min:1'],
            'person.contact_numbers.0.value' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! $this->phoneNormalizer->isValid((string) $value)) {
                        $fail('Số điện thoại không hợp lệ.');
                    }
                },
            ],
            'person.name' => ['required', 'string', 'max:255'],
            'person.emails' => ['nullable', 'array'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'lead_source_id' => ['nullable', 'exists:lead_sources,id'],
            'group_id' => ['nullable', 'exists:groups,id'],
        ];

        return [
            ...$this->rules,
            ...$telesalesRules,
            'products' => 'array',
            'products.*.product_id' => 'sometimes|required|exists:products,id',
            'products.*.name' => 'required_with:products.*.product_id',
            'products.*.price' => 'required_with:products.*.product_id',
            'products.*.quantity' => 'required_with:products.*.product_id',
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     */
    public function messages(): array
    {
        return [
            'products.*.product_id.exists' => trans('admin::app.leads.selected-product-not-exist'),
            'products.*.name.required_with' => trans('admin::app.leads.product-name-required'),
            'products.*.price.required_with' => trans('admin::app.leads.product-price-required'),
            'products.*.quantity.required_with' => trans('admin::app.leads.product-quantity-required'),
        ];
    }
}
