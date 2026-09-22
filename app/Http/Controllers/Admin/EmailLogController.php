<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = EmailLog::query()->latest('id');

        if ($status = $request->string('status')->trim()->toString()) {
            $query->where('status', $status);
        }

        if ($from = $request->date('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->date('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($inner) use ($search) {
                $inner->where('to_email', 'like', '%'.$search.'%')
                    ->orWhere('from_email', 'like', '%'.$search.'%')
                    ->orWhere('subject', 'like', '%'.$search.'%')
                    ->orWhere('mailable', 'like', '%'.$search.'%');
            });
        }

        return view('admin.email-logs.index', [
            'logs' => $query->paginate(500)->withQueryString(),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
                'from' => $request->string('from')->toString(),
                'to' => $request->string('to')->toString(),
            ],
            'summary' => [
                'all' => EmailLog::query()->count(),
                'sent' => EmailLog::query()->where('status', 'sent')->count(),
                'failed' => EmailLog::query()->where('status', 'failed')->count(),
                'today' => EmailLog::query()->whereDate('created_at', today())->count(),
            ],
        ]);
    }

    public function show(EmailLog $emailLog): View
    {
        return view('admin.email-logs.show', [
            'log' => $emailLog,
        ]);
    }
}
