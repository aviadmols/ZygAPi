<?php

namespace App\Http\Controllers;

use App\Models\CustomEndpointLog;
use App\Models\TaggingRuleLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaggingRuleLogController extends Controller
{
    /**
     * Dashboard: list of webhook/tagging invocations and custom endpoint calls.
     */
    public function index(Request $request): View
    {
        $query = TaggingRuleLog::with('taggingRule')
            ->latest();

        if ($request->filled('rule_id')) {
            $query->where('tagging_rule_id', $request->rule_id);
        }
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        $logs = $query->paginate(50);

        $customEndpointLogsQuery = CustomEndpointLog::with('customEndpoint.store')
            ->latest();

        if ($request->filled('endpoint_id')) {
            $customEndpointLogsQuery->where('custom_endpoint_id', $request->endpoint_id);
        }
        if ($request->filled('endpoint_order_id')) {
            $customEndpointLogsQuery->where('request_input->order_id', $request->endpoint_order_id);
        }

        $customEndpointLogs = $customEndpointLogsQuery->paginate(50, ['*'], 'endpoint_page');

        return view('tagging-rule-logs.index', compact('logs', 'customEndpointLogs'));
    }
}
