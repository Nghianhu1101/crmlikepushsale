<?php

namespace Webkul\Telesales\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Webkul\Activity\Models\Activity;
use Webkul\Lead\Models\Lead;
use Webkul\Telesales\Models\CallHistory;
use Webkul\Telesales\Models\Notification;

class OutcomeController extends Controller
{
    public function store(Lead $lead): RedirectResponse
    {
        $this->authorizeLead($lead);

        $data = request()->validate([
            'result' => ['required', Rule::in(array_keys(config('telesales.call_results')))],
            'note' => ['nullable', 'string', 'max:5000'],
            'callback_at' => ['required_if:result,callback', 'nullable', 'date', 'after:now'],
        ]);

        DB::transaction(function () use ($lead, $data) {
            $userId = auth()->guard('user')->id();

            CallHistory::query()->create([
                'lead_id' => $lead->id,
                'user_id' => $userId,
                'result' => $data['result'],
                'note' => $data['note'] ?? null,
                'callback_at' => $data['callback_at'] ?? null,
            ]);

            $activity = Activity::query()->create([
                'title' => config('telesales.call_results')[$data['result']],
                'type' => $data['result'] === 'callback' ? 'call' : 'note',
                'comment' => $data['note'] ?? config('telesales.call_results')[$data['result']],
                'schedule_from' => $data['callback_at'] ?? null,
                'schedule_to' => $data['callback_at'] ?? null,
                'is_done' => $data['result'] !== 'callback',
                'user_id' => $userId,
            ]);
            $activity->leads()->attach($lead->id);

            if ($data['result'] === 'callback') {
                Notification::query()->create([
                    'user_id' => $userId,
                    'lead_id' => $lead->id,
                    'title' => 'Đến lịch gọi lại',
                    'body' => implode(' · ', array_filter([
                        $lead->person?->name,
                        data_get($lead->person?->contact_numbers, '0.value'),
                        $data['note'] ?? null,
                    ])),
                    'available_at' => $data['callback_at'],
                ]);
            }

            $stageCode = match ($data['result']) {
                'consulting' => 'caring',
                'callback' => 'callback',
                'won' => 'won',
                'wrong_number', 'duplicate', 'no_demand' => 'failed',
                default => null,
            };

            if ($stageCode) {
                $stage = $lead->pipeline->stages()->where('code', $stageCode)->first();

                if ($stage) {
                    $lead->update([
                        'lead_pipeline_stage_id' => $stage->id,
                        'closed_at' => in_array($stageCode, ['won', 'failed']) ? now() : null,
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.leads.view', $lead->id)
            ->with('success', 'Đã lưu kết quả cuộc gọi.');
    }

    private function authorizeLead(Lead $lead): void
    {
        $authorizedIds = bouncer()->getAuthorizedUserIds();

        if ($authorizedIds !== null && ! in_array($lead->user_id, $authorizedIds)) {
            abort(403);
        }
    }
}
