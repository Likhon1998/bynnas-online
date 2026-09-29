<?php

namespace App\Http\Controllers;

use App\Notifications\StaffAlert;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

/** The signed-in staff member's own in-app alerts. */
class StaffNotificationController extends Controller
{
    public function index(Request $request)
    {
        $kind = $request->query('kind');
        $query = $this->query();
        if ($kind && array_key_exists($kind, StaffAlert::KINDS)) {
            $query->where('data', 'like', '%"kind":"'.$kind.'"%');
        }

        return view('notifications.index', [
            'notifications' => $query->paginate(30)->withQueryString(),
            'unread' => $this->query()->whereNull('read_at')->count(),
            'kind' => $kind,
        ]);
    }

    public function feed()
    {
        $items = $this->query()->limit(12)->get()->map(fn (DatabaseNotification $n) => $this->present($n));

        return response()->json([
            'unread' => $this->query()->whereNull('read_at')->count(),
            'items' => $items->values(),
        ]);
    }

    public function read(Request $request, string $notification)
    {
        $item = $this->query()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        $url = $item->data['url'] ?? null;

        return $url && str_starts_with($url, url('/')) ? redirect()->to($url) : back();
    }

    public function readAll(Request $request)
    {
        $this->query()->whereNull('read_at')->update(['read_at' => now()]);

        return $request->expectsJson()
            ? response()->json(['ok' => true])
            : back()->with('success', 'All notifications marked as read.');
    }

    public function present(DatabaseNotification $n): array
    {
        $data = $n->data;

        return [
            'id' => $n->id,
            'kind' => $data['kind'] ?? 'order',
            'kind_label' => StaffAlert::KINDS[$data['kind'] ?? ''] ?? 'Alert',
            'title' => $data['title'] ?? 'Notification',
            'body' => $data['body'] ?? '',
            'url' => $data['url'] ?? null,
            'read_url' => route('notifications.read', $n->id),
            'at' => $n->created_at?->diffForHumans(),
            'is_new' => $n->read_at === null,
        ];
    }

    private function query()
    {
        return Auth::user()->notifications()->where('type', StaffAlert::class);
    }
}
