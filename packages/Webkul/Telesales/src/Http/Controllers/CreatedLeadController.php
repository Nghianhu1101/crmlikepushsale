<?php

namespace Webkul\Telesales\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Webkul\Telesales\Models\LeadMeta;

class CreatedLeadController extends Controller
{
    public function __invoke(): View
    {
        $records = LeadMeta::query()
            ->with(['lead.person', 'lead.user', 'lead.stage', 'lead.source'])
            ->where('created_by', auth()->guard('user')->id())
            ->latest()
            ->paginate(20);

        return view('telesales::created-leads', compact('records'));
    }
}
