<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $status = $request->query('status');
        $query = Notification::where('user_id', $request->user()->id)
            ->when($q !== '', fn ($where) => $where->where(function ($text) use ($q) {
                $like='%'.$q.'%';
                $text->where('title','like',$like)->orWhere('message','like',$like);
            }))
            ->when($status === 'unread', fn ($where) => $where->whereNull('read_at'))
            ->when($status === 'read', fn ($where) => $where->whereNotNull('read_at'))
            ->latest();
        $notifications = $query->paginate(20);

        return view('partials.notifications', ['notifications' => $notifications,'filters'=>['q'=>$q,'status'=>$status]]);
    }

    public function markRead(Request $request, Notification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->forceFill(['read_at' => now()])->save();

        return back()->with('status', 'Notification marked as read.');
    }

    public function markAllRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('status', 'All notifications marked as read.');
    }
}
