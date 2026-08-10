<?php

namespace Webkul\Telesales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Webkul\Telesales\Services\TelesalesAccessService;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->guard('user')->user();

        return $user && app(TelesalesAccessService::class)->isAdmin($user);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->boolean('status'),
            'receives_data' => $this->boolean('receives_data'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'status' => ['required', 'boolean'],
            'receives_data' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập họ tên nhân viên.',
            'email.required' => 'Vui lòng nhập email đăng nhập.',
            'email.email' => 'Email đăng nhập không hợp lệ.',
            'email.unique' => 'Email này đã được sử dụng.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'role_id.required' => 'Vui lòng chọn vai trò.',
            'role_id.exists' => 'Vai trò đã chọn không tồn tại.',
        ];
    }
}
