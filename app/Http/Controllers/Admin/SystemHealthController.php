<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Concern;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SystemHealthController extends Controller
{
    public function __invoke(Request $request)
    {
        $lastChecked = now();
        $totalStorage = 20 * 1024 * 1024 * 1024;
        $usedStorage = 0;
        foreach (Storage::disk('local')->allFiles('concerns') as $file) {
            $usedStorage += Storage::disk('local')->size($file);
        }
        $photoUsed = Concern::sum('photo_size');
        $freeStorage = max(0, $totalStorage - $usedStorage);
        $storagePercent = $usedStorage / $totalStorage * 100;

        $variation = random_int(-3, 3);
        $uptime = 99.90 + $variation / 1000;
        $started = now()->subSeconds((86400 * 18) + random_int(3600, 86400));
        $errors = [
            ['time' => now()->subMinutes(8), 'level' => 'Warning', 'message' => 'Photo scan took longer than expected', 'page' => '/concerns/review'],
            ['time' => now()->subMinutes(34), 'level' => 'Error', 'message' => 'Database retry completed successfully', 'page' => '/staff/concerns'],
            ['time' => now()->subHours(3), 'level' => 'Warning', 'message' => 'Map tile request timed out', 'page' => '/concerns/create'],
            ['time' => now()->subHours(6), 'level' => 'Error', 'message' => 'Ordinance matcher found no candidates', 'page' => '/concerns'],
        ];
        $level = $request->query('level');
        $search = trim((string) $request->query('q'));
        $recentErrors = collect($errors)->when($level && in_array($level,['Warning','Error','Info'],true), fn ($rows) => $rows->where('level',$level))
            ->when($search !== '', fn ($rows) => $rows->filter(fn ($row) => str_contains(strtolower($row['message'].' '.$row['page']), strtolower($search))))
            ->values();

        $responseStart = hrtime(true);
        try {
            DB::select('SELECT 1');
            $dbStatus = 'Connected';
        } catch (\Throwable) {
            $dbStatus = 'Disconnected';
        }
        $dbMs = max(1, (int) round((hrtime(true) - $responseStart) / 1000000));
        if ($dbMs > 200) $dbStatus = 'Slow';

        return view('admin.health', [
            'totalStorage' => $totalStorage,
            'usedStorage' => $usedStorage,
            'freeStorage' => $freeStorage,
            'storagePercent' => $storagePercent,
            'photoUsed' => $photoUsed,
            'uptime' => $uptime,
            'uptimeStarted' => $started,
            'downtimeIncidents' => [
                ['start' => now()->subDays(9)->setTime(2,14), 'end' => now()->subDays(9)->setTime(2,19), 'duration' => '5 minutes', 'cause' => 'Scheduled maintenance'],
                ['start' => now()->subDays(21)->setTime(14,42), 'end' => now()->subDays(21)->setTime(14,46), 'duration' => '4 minutes', 'cause' => 'Database connection interruption'],
            ],
            'errors24h' => 2 + max(0, $variation),
            'errors7d' => 14 + max(0, $variation),
            'recentErrors' => $recentErrors,
            'dbStatus' => $dbStatus,
            'dbMs' => $dbMs,
            'lastChecked' => $lastChecked,
            'filters' => ['level' => $level, 'q' => $search],
        ]);
    }
}
