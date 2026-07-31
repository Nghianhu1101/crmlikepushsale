<x-admin::layouts>
    <x-slot:title>
        Tạo Lead telesale
    </x-slot>

    {!! view_render_event('admin.leads.create.form.before') !!}

    <x-admin::form :action="route('admin.leads.store')">
        <div class="flex flex-col gap-4">
            <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    <x-admin::breadcrumbs name="leads.create" />

                    <div class="text-xl font-bold dark:text-white">
                        Tạo Lead telesale
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    @if (bouncer()->hasPermission('leads.view'))
                        <a
                            href="{{ route('admin.telesales.created-leads.index') }}"
                            class="secondary-button"
                        >
                            Data tôi đã nhập
                        </a>
                    @endif

                    @if (bouncer()->hasPermission('settings.user.groups'))
                        <a
                            href="{{ route('admin.telesales.groups.index') }}"
                            class="secondary-button"
                        >
                            Cấu hình phân data
                        </a>
                    @endif

                    <button
                        type="submit"
                        class="primary-button"
                    >
                        Lưu
                    </button>
                </div>
            </div>

            @if (request('stage_id'))
                <input
                    type="hidden"
                    name="lead_pipeline_stage_id"
                    value="{{ request('stage_id') }}"
                />
            @endif

            @if (request('pipeline_id'))
                <input
                    type="hidden"
                    name="lead_pipeline_id"
                    value="{{ request('pipeline_id') }}"
                />
            @endif

            <div class="box-shadow rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="grid max-w-4xl grid-cols-2 gap-4 max-md:grid-cols-1">
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            Số điện thoại
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="person[contact_numbers][0][value]"
                            id="phone"
                            :value="old('person.contact_numbers.0.value')"
                            rules="required|min:8|max:15"
                            label="Số điện thoại"
                            placeholder="Nhập số điện thoại"
                            autocomplete="tel"
                        />

                        <input
                            type="hidden"
                            name="person[contact_numbers][0][label]"
                            value="work"
                        />

                        <x-admin::form.control-group.error control-name="person[contact_numbers][0][value]" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            Tên khách
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="person[name]"
                            id="person_name"
                            :value="old('person.name')"
                            label="Tên khách"
                            placeholder="Để trống sẽ tự tạo: Khách + 4 số cuối"
                            autocomplete="name"
                        />

                        <x-admin::form.control-group.error control-name="person[name]" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            Nguồn khách
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            name="lead_source_id"
                            id="lead_source_id"
                            :value="old('lead_source_id', $defaultSourceId)"
                            label="Nguồn khách"
                        >
                            <option value="">Chọn nguồn khách</option>

                            @foreach ($sources as $source)
                                <option value="{{ $source->id }}">
                                    {{ $source->name }}
                                </option>
                            @endforeach
                        </x-admin::form.control-group.control>

                        <x-admin::form.control-group.error control-name="lead_source_id" />
                    </x-admin::form.control-group>

                    @if (
                        $groups->isNotEmpty()
                        && bouncer()->hasPermission('leads.create')
                    )
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                Nhóm sale nhận data
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                name="group_id"
                                id="group_id"
                                :value="old('group_id')"
                                label="Nhóm sale nhận data"
                            >
                                <option value="">Dùng nhóm mặc định</option>

                                @foreach ($groups as $configuration)
                                    <option value="{{ $configuration->group_id }}">
                                        {{ $configuration->group->name }}
                                    </option>
                                @endforeach
                            </x-admin::form.control-group.control>

                            <x-admin::form.control-group.error control-name="group_id" />
                        </x-admin::form.control-group>
                    @endif

                    <x-admin::form.control-group class="col-span-2 max-md:col-span-1">
                        <x-admin::form.control-group.label>
                            Nội dung khách để lại
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="textarea"
                            name="description"
                            id="description"
                            rows="4"
                            :value="old('description')"
                            label="Nội dung khách để lại"
                            placeholder="Ví dụ: Tôi cần tư vấn sản phẩm"
                        />

                        <x-admin::form.control-group.error control-name="description" />
                    </x-admin::form.control-group>
                </div>

                <div class="mt-6">
                    <div class="mb-3">
                        <p class="text-base font-semibold dark:text-white">
                            Sản phẩm
                        </p>

                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Không bắt buộc. Bấm “Thêm” nếu Lead quan tâm sản phẩm cụ thể.
                        </p>
                    </div>

                    @include('admin::leads.common.products')
                </div>
            </div>
        </div>
    </x-admin::form>

    {!! view_render_event('admin.leads.create.form.after') !!}
</x-admin::layouts>
