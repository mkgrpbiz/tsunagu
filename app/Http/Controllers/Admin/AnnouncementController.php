<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AnnouncementCategory;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Announcement;
use App\Services\LineMessagingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        return view('admin.announcements.index', [
            'announcements' => Announcement::latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.announcements.create', ['announcement' => new Announcement]);
    }

    public function store(Request $request, LineMessagingService $lineMessaging): RedirectResponse
    {
        $announcement = Announcement::create($this->validated($request));

        if ($announcement->notify_line) {
            $this->sendLineNotifications($announcement, $lineMessaging);
        }

        return redirect()->route('admin.announcements.index')->with('status', 'お知らせを作成しました。');
    }

    public function edit(Announcement $announcement): View
    {
        return view('admin.announcements.edit', ['announcement' => $announcement]);
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $announcement->update($this->validated($request));

        return redirect()->route('admin.announcements.index')->with('status', 'お知らせを更新しました。');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return redirect()->route('admin.announcements.index')->with('status', 'お知らせを削除しました。');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'body' => ['required', 'string', 'max:1000'],
            'category' => ['required', 'in:important,project_info'],
            'notify_line' => ['sometimes', 'boolean'],
            'line_message' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function sendLineNotifications(Announcement $announcement, LineMessagingService $lineMessaging): void
    {
        $message = $announcement->line_message ?: $announcement->body;

        $query = Agency::whereNotNull('line_uid');

        if ($announcement->category === AnnouncementCategory::ProjectInfo) {
            $query->where('line_notify_project_info', true);
        }

        $query->pluck('line_uid')->each(fn (string $lineUid) => $lineMessaging->sendPush($lineUid, $message));
    }
}
