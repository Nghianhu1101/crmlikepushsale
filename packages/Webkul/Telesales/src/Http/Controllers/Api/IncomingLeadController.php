<?php

namespace Webkul\Telesales\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Webkul\Telesales\Http\Requests\IncomingLeadRequest;
use Webkul\Telesales\Models\SourceConnection;
use Webkul\Telesales\Services\IncomingLeadService;

class IncomingLeadController extends Controller
{
    public function __invoke(
        IncomingLeadRequest $request,
        IncomingLeadService $service
    ): JsonResponse {
        /** @var SourceConnection|null $connection */
        $connection = $request->attributes->get('telesales_source_connection');
        $input = $request->validated();

        if ($connection) {
            $input['source_id'] = $connection->source_id;
            $input['group_id'] = $connection->group_id;
            $input['campaign'] = $connection->campaign ?: ($input['campaign'] ?? null);
            $input['incoming_source_id'] = $connection->id;
        }

        $result = $service->create($input, $connection?->marketing_owner_id);

        if ($connection) {
            $connection->increment('received_count');
            $connection->update(['last_received_at' => now()]);
        }

        if ($result['duplicate']) {
            $connection?->increment('duplicate_count');

            return response()->json([
                'status' => 'duplicate',
                'message' => 'Data đã tồn tại.',
                'data' => [
                    'lead_id' => $result['lead']?->id,
                    'person_id' => $result['person_id'],
                    'owner' => $result['owner'],
                    'stage' => $result['stage'],
                    'last_data_at' => $result['last_data_at'],
                    'source_connection' => $connection?->name,
                ],
            ], 409);
        }

        Log::info('Incoming lead accepted', [
            'lead_id' => $result['lead']->id,
            'status' => $result['status'],
            'source_connection_id' => $connection?->id,
            'source' => $connection?->source_id ?: $request->input('source'),
            'campaign' => $connection?->campaign ?: $request->input('campaign'),
        ]);

        return response()->json([
            'status' => $result['status'],
            'message' => $result['status'] === 'unassigned'
                ? 'Data đã tạo nhưng chưa có nhân sự nhận.'
                : ($result['customer_type'] === 'old'
                    ? 'Khách hàng cũ đã được chuyển cho bộ phận CSKH.'
                    : 'Data đã được tạo và phân sale.'),
            'data' => [
                'lead_id' => $result['lead']->id,
                'person_id' => $result['person']->id,
                'assigned_user_id' => $result['lead']->user_id,
                'stage' => $result['lead']->stage->name,
                'source_connection' => $connection?->name,
                'customer_type' => $result['customer_type'],
                'assigned_department' => $result['customer_type'] === 'old' ? 'customer_care' : 'sales',
            ],
        ], 201);
    }
}
