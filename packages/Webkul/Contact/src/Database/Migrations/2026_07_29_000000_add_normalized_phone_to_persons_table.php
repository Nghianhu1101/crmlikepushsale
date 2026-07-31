<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Contact\Support\PhoneNormalizer;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('persons', function (Blueprint $table) {
            $table->string('normalized_phone', 32)->nullable()->after('contact_numbers');
            $table->unique('normalized_phone');
        });

        $normalizer = app(PhoneNormalizer::class);
        $claimedNumbers = [];

        DB::table('persons')
            ->select(['id', 'contact_numbers'])
            ->orderBy('id')
            ->chunkById(100, function ($persons) use ($normalizer, &$claimedNumbers) {
                foreach ($persons as $person) {
                    $numbers = json_decode($person->contact_numbers ?: '[]', true);
                    $phone = $numbers[0]['value'] ?? $numbers[0] ?? null;
                    $normalizedPhone = $normalizer->normalize($phone);

                    if ($normalizedPhone === '' || isset($claimedNumbers[$normalizedPhone])) {
                        continue;
                    }

                    DB::table('persons')
                        ->where('id', $person->id)
                        ->update(['normalized_phone' => $normalizedPhone]);

                    $claimedNumbers[$normalizedPhone] = true;
                }
            });
    }

    public function down(): void
    {
        Schema::table('persons', function (Blueprint $table) {
            $table->dropUnique(['normalized_phone']);
            $table->dropColumn('normalized_phone');
        });
    }
};
