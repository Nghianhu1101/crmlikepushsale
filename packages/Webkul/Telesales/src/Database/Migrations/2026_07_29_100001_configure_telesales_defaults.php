<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['Nhập trực tiếp', 'Website', 'Facebook', 'Messenger'] as $source) {
            if (! DB::table('lead_sources')->where('name', $source)->exists()) {
                DB::table('lead_sources')->insert([
                    'name' => $source,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $pipeline = DB::table('lead_pipelines')
            ->where('is_default', true)
            ->first() ?: DB::table('lead_pipelines')->first();

        if (! $pipeline) {
            return;
        }

        $stages = [
            ['code' => 'new', 'name' => 'Data mới', 'probability' => 0],
            ['code' => 'unassigned', 'name' => 'Chưa phân bổ', 'probability' => 0],
            ['code' => 'caring', 'name' => 'Đang chăm sóc', 'probability' => 40],
            ['code' => 'callback', 'name' => 'Hẹn gọi lại', 'probability' => 60],
            ['code' => 'won', 'name' => 'Đã chốt', 'probability' => 100],
            ['code' => 'failed', 'name' => 'Không thành công', 'probability' => 0],
        ];

        $nextSortOrder = (int) DB::table('lead_pipeline_stages')
            ->where('lead_pipeline_id', $pipeline->id)
            ->max('sort_order') + 1;

        foreach ($stages as $stage) {
            $current = DB::table('lead_pipeline_stages')
                ->where('lead_pipeline_id', $pipeline->id)
                ->where('code', $stage['code'])
                ->first();

            if ($current) {
                DB::table('lead_pipeline_stages')
                    ->where('id', $current->id)
                    ->update([
                        'name' => $stage['name'],
                        'probability' => $stage['probability'],
                    ]);
            } else {
                DB::table('lead_pipeline_stages')->insert(array_merge($stage, [
                    'sort_order' => $nextSortOrder++,
                    'lead_pipeline_id' => $pipeline->id,
                ]));
            }
        }
    }

    public function down(): void
    {
        // Configuration data is intentionally preserved.
    }
};
