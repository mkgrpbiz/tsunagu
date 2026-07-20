<?php

namespace App\Http\Controllers\Agency;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\InviteLink;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProjectController extends Controller
{
    private const OSHIGOTO_TEMPLATE = <<<'TEXT'
💰【スマホだけで参加できる案件まとめ】
知らないと普通に損してるかも？

今募集中の案件をまとめました🙆‍♂️

━━━━━━━━━━━━━

🔥 最大50,000円案件あり
🔥 スマホだけで参加OK案件あり
🔥 在宅・モニター・求人あり
🔥 初心者OK案件多数

━━━━━━━━━━━━━

「自分にできる案件が
なかなか見つからない…」

そんな方でも一覧で見れるので、
気になる案件だけ確認でOK😊

👇【募集中案件一覧】
{invite_url}
TEXT;

    public function index(): View
    {
        $agency = Auth::guard('agency')->user();

        $projects = Project::query()
            ->select('projects.*')
            ->join('categories', 'categories.id', '=', 'projects.category_id')
            ->with('category')
            ->where('projects.status', ProjectStatus::Published)
            ->orderBy('categories.sort_order')
            ->orderBy('projects.sort_order')
            ->get();

        $inviteData = $projects->mapWithKeys(function (Project $project) use ($agency) {
            $inviteLink = InviteLink::firstOrCreate(
                ['agency_id' => $agency->id, 'project_id' => $project->id],
                ['token' => Str::random(10)],
            );

            $inviteUrl = url('/apply/'.$inviteLink->token);

            return [$project->id => [
                'url' => $inviteUrl,
                'template' => str_replace('{invite_url}', $inviteUrl, (string) $project->recruitment_template),
            ]];
        });

        $oshigotoUrl = url('/oshigoto?ref='.$agency->getOrCreateOshigotoToken());

        return view('agency.projects.index', [
            'projectsByCategory' => $projects->groupBy(fn (Project $project) => $project->category->name),
            'inviteData' => $inviteData,
            'oshigotoUrl' => $oshigotoUrl,
            'oshigotoTemplate' => str_replace('{invite_url}', $oshigotoUrl, self::OSHIGOTO_TEMPLATE),
        ]);
    }
}
