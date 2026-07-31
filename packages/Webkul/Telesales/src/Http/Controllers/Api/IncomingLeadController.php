<?php

namespace Webkul\Telesales\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Webkul\Telesales\Http\Requests\IncomingLeadRequest;
use Webkul\Telesales\Services\IncomingLeadService;

class IncomingLeadController extends Controller
{
    public function __invoke(
        IncomingLeadRequest $request,
        IncomingLeadService $service
    ): JsonResponse {
        $result = $service->create($request->validated());

        if ($result['duplicate']) {
            return response()->json([
                'status' => 'duplicate',
                'message' => 'Data đã tồn tại.',
                'data' => [
                    'lead_id' => $result['lead']?->id,
                    'person_id' => $result['person_id'],
                    'owner' => $result['owner'],
                    'stage' => $result['stage'],
                    'last_data_at' => $result['last_data_at'],
                ],
            ], 409);
        }

        Log::info('Incoming lead accepted', [
            'lead_id' => $result['lead']->id,
            'status' => $result['status'],
            'source' => $request->input('source'),
            'campaign' => $request->input('campaign'),
        ]);

        return response()->json([
            'status' => $result['status'],
            'message' => $result['status'] === 'unassigned'
                ? 'Data đã tạo nhưng chưa có sale hoạt động.'
                : 'Data đã được tạo và phân sale.',
            'data' => [
                'lead_id' => $result['lead']->id,
                'person_id' => $result['person']->id,
                'assigned_user_id' => $result['lead']->user_id,
                'stage' => $result['lead']->stage->name,
            ],
        ], 201);
    }
}
