<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    private const DAYS = 30;

    private function tab(Request $request): string
    {
        return $request->query('tab') === 'history' ? 'history' : 'audit';
    }

    private function query(string $tab)
    {
        return AuditLog::where('type', $tab)
            ->where('created_at', '>=', now()->subDays(self::DAYS))
            ->latest()->latest('id');
    }

    public function index(Request $request)
    {
        $tab = $this->tab($request);
        $logs = $this->query($tab)->paginate(15)->withQueryString();

        return view('admin.audit', ['tab' => $tab, 'logs' => $logs, 'days' => self::DAYS]);
    }

    public function export(Request $request)
    {
        $tab = $this->tab($request);

        AuditLogger::activity('Audit log exported', ucfirst($tab === 'audit' ? 'audit trail' : 'history'));

        $rows = $this->query($tab)->get();
        $filename = ($tab === 'audit' ? 'audit-trail' : 'history').'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Event', 'Detail', 'By', 'When', 'IP address']);

            foreach ($rows as $r) {
                fputcsv($out, array_map([$this, 'safeCell'], [
                    $r->event, $r->detail, $r->actor_name, $r->created_at->format('Y-m-d H:i:s'), $r->ip_address,
                ]));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** Stop spreadsheet apps from running a cell as a formula. */
    private function safeCell($value)
    {
        $value = (string) $value;

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@'], true) ? "'".$value : $value;
    }
}
