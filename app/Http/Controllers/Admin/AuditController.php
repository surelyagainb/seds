<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/** Admin page for the audit trail and history, plus the CSV export. */
class AuditController extends Controller
{
    private const DAYS = 30;

    /** Which tab is open: "history" or "audit" (default). */
    private function tab(Request $request): string
    {
        return $request->query('tab') === 'history' ? 'history' : 'audit';
    }

    /** The log rows for a tab, newest first, limited to the last 30 days. */
    private function query(string $tab)
    {
        return AuditLog::where('type', $tab)
            ->where('created_at', '>=', now()->subDays(self::DAYS))
            ->latest()->latest('id');
    }

    /** Show the log list, 15 rows per page. */
    public function index(Request $request)
    {
        $tab = $this->tab($request);
        $q = trim((string) $request->query('q'));
        $user = trim((string) $request->query('user'));
        $logs = $this->query($tab)
            ->when($q !== '', fn ($query) => $query->where(function ($where) use ($q) {
                $like = '%'.$q.'%';
                $where->where('event','like',$like)->orWhere('detail','like',$like)->orWhere('actor_name','like',$like);
            }))
            ->when($user !== '', fn ($query) => $query->where('actor_name','like','%'.$user.'%'))
            ->paginate(15)->withQueryString();

        return view('admin.audit', [
            'tab' => $tab,
            'logs' => $logs,
            'days' => self::DAYS,
            'filters' => ['q'=>$q,'user'=>$user],
        ]);
    }

    /** Download the same log as a CSV file (and record that the export happened). */
    public function export(Request $request)
    {
        $tab = $this->tab($request);

        AuditLogger::activity('Audit log exported', ucfirst($tab === 'audit' ? 'audit trail' : 'history'));

        $q = trim((string) $request->query('q'));
        $user = trim((string) $request->query('user'));
        $rows = $this->query($tab)
            ->when($q !== '', fn ($query) => $query->where(function ($where) use ($q) {
                $like='%'.$q.'%';
                $where->where('event','like',$like)->orWhere('detail','like',$like)->orWhere('actor_name','like',$like);
            }))
            ->when($user !== '', fn ($query) => $query->where('actor_name','like','%'.$user.'%'))
            ->get();
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
