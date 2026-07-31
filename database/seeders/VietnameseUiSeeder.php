<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VietnameseUiSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::transaction(function () use ($now): void {
            DB::table('core_config')->updateOrInsert(
                ['code' => 'general.general.locale_settings.locale'],
                [
                    'value' => 'vi',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            foreach ($this->systemAttributeTranslations() as $entityType => $attributes) {
                foreach ($attributes as $code => $translationKey) {
                    DB::table('attributes')
                        ->where('entity_type', $entityType)
                        ->where('code', $code)
                        ->where('is_user_defined', false)
                        ->update([
                            'name' => trans($translationKey, [], 'vi'),
                            'updated_at' => $now,
                        ]);
                }
            }

            DB::table('roles')
                ->where('name', 'Administrator')
                ->where('permission_type', 'all')
                ->update([
                    'name' => trans('installer::app.seeders.user.role.administrator', [], 'vi'),
                    'description' => trans('installer::app.seeders.user.role.administrator-role', [], 'vi'),
                    'updated_at' => $now,
                ]);

            $this->translateSystemEmailTemplates($now);
        });
    }

    private function translateSystemEmailTemplates($now): void
    {
        $templates = [
            'Activity created' => [
                'name' => trans('installer::app.seeders.email.activity-created', [], 'vi'),
                'message' => trans('installer::app.seeders.email.new-activity', [], 'vi'),
            ],
            'Activity modified' => [
                'name' => trans('installer::app.seeders.email.activity-modified', [], 'vi'),
                'message' => trans('installer::app.seeders.email.new-activity-modified', [], 'vi'),
            ],
        ];

        foreach ($templates as $englishName => $template) {
            DB::table('email_templates')
                ->where('name', $englishName)
                ->update([
                    'name' => $template['name'],
                    'subject' => $template['name'].': {%activities.title%}',
                    'content' => $this->activityEmailContent($template['message']),
                    'updated_at' => $now,
                ]);
        }
    }

    private function activityEmailContent(string $message): string
    {
        $title = trans('installer::app.seeders.email.title', [], 'vi');
        $type = trans('installer::app.seeders.email.type', [], 'vi');
        $date = trans('installer::app.seeders.email.date', [], 'vi');
        $participants = trans('installer::app.seeders.email.participants', [], 'vi');

        return <<<HTML
<p style="font-size: 16px; color: #5e5e5e;">{$message}:</p>
<p><strong style="font-size: 16px;">Chi tiết</strong></p>
<table style="height: 97px; width: 952px;">
    <tbody>
        <tr>
            <td style="width: 116.953px; color: #546e7a; font-size: 16px;">{$title}</td>
            <td style="width: 770.047px; font-size: 16px;">{%activities.title%}</td>
        </tr>
        <tr>
            <td style="width: 116.953px; color: #546e7a; font-size: 16px;">{$type}</td>
            <td style="width: 770.047px; font-size: 16px;">{%activities.type%}</td>
        </tr>
        <tr>
            <td style="width: 116.953px; color: #546e7a; font-size: 16px;">{$date}</td>
            <td style="width: 770.047px; font-size: 16px;">{%activities.schedule_from%} đến {%activities.schedule_to%}</td>
        </tr>
        <tr>
            <td style="width: 116.953px; color: #546e7a; font-size: 16px; vertical-align: text-top;">{$participants}</td>
            <td style="width: 770.047px; font-size: 16px;">{%activities.participants%}</td>
        </tr>
    </tbody>
</table>
HTML;
    }

    private function systemAttributeTranslations(): array
    {
        return [
            'leads' => [
                'title' => 'installer::app.seeders.attributes.leads.title',
                'description' => 'installer::app.seeders.attributes.leads.description',
                'lead_value' => 'installer::app.seeders.attributes.leads.lead-value',
                'lead_source_id' => 'installer::app.seeders.attributes.leads.source',
                'lead_type_id' => 'installer::app.seeders.attributes.leads.type',
                'user_id' => 'installer::app.seeders.attributes.leads.sales-owner',
                'expected_close_date' => 'installer::app.seeders.attributes.leads.expected-close-date',
                'lead_pipeline_id' => 'installer::app.seeders.attributes.leads.pipeline',
                'lead_pipeline_stage_id' => 'installer::app.seeders.attributes.leads.stage',
            ],
            'persons' => [
                'name' => 'installer::app.seeders.attributes.persons.name',
                'emails' => 'installer::app.seeders.attributes.persons.emails',
                'contact_numbers' => 'installer::app.seeders.attributes.persons.contact-numbers',
                'job_title' => 'installer::app.seeders.attributes.persons.job-title',
                'user_id' => 'installer::app.seeders.attributes.persons.sales-owner',
                'organization_id' => 'installer::app.seeders.attributes.persons.organization',
            ],
            'organizations' => [
                'name' => 'installer::app.seeders.attributes.organizations.name',
                'address' => 'installer::app.seeders.attributes.organizations.address',
                'user_id' => 'installer::app.seeders.attributes.organizations.sales-owner',
            ],
            'products' => [
                'name' => 'installer::app.seeders.attributes.products.name',
                'description' => 'installer::app.seeders.attributes.products.description',
                'sku' => 'installer::app.seeders.attributes.products.sku',
                'quantity' => 'installer::app.seeders.attributes.products.quantity',
                'price' => 'installer::app.seeders.attributes.products.price',
            ],
            'quotes' => [
                'user_id' => 'installer::app.seeders.attributes.quotes.sales-owner',
                'subject' => 'installer::app.seeders.attributes.quotes.subject',
                'description' => 'installer::app.seeders.attributes.quotes.description',
                'billing_address' => 'installer::app.seeders.attributes.quotes.billing-address',
                'shipping_address' => 'installer::app.seeders.attributes.quotes.shipping-address',
                'discount_percent' => 'installer::app.seeders.attributes.quotes.discount-percent',
                'discount_amount' => 'installer::app.seeders.attributes.quotes.discount-amount',
                'tax_amount' => 'installer::app.seeders.attributes.quotes.tax-amount',
                'adjustment_amount' => 'installer::app.seeders.attributes.quotes.adjustment-amount',
                'sub_total' => 'installer::app.seeders.attributes.quotes.sub-total',
                'grand_total' => 'installer::app.seeders.attributes.quotes.grand-total',
                'expired_at' => 'installer::app.seeders.attributes.quotes.expired-at',
                'person_id' => 'installer::app.seeders.attributes.quotes.person',
            ],
            'warehouses' => [
                'name' => 'installer::app.seeders.attributes.warehouses.name',
                'description' => 'installer::app.seeders.attributes.warehouses.description',
                'contact_name' => 'installer::app.seeders.attributes.warehouses.contact-name',
                'contact_emails' => 'installer::app.seeders.attributes.warehouses.contact-emails',
                'contact_numbers' => 'installer::app.seeders.attributes.warehouses.contact-numbers',
                'contact_address' => 'installer::app.seeders.attributes.warehouses.contact-address',
            ],
        ];
    }
}
