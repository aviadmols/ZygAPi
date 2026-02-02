<?php

namespace App\Http\Controllers;

use App\Models\TaggingRuleLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaggingRuleLogController extends Controller
{
    /**
     * Dashboard: list of webhook/tagging invocations (timestamp, order number, tags).
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

        return view('tagging-rule-logs.index', compact('logs'));
    }
}
