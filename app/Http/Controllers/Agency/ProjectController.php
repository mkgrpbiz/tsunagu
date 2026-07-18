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
    public function index(): View
    {
        $agency = Auth::guard('agency')->user();

        $projects = Project::with('category')
            ->where('status', ProjectStatus::Published)
            ->orderBy('category_id')
            ->latest()
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

        return view('agency.projects.index', [
            'projectsByCategory' => $projects->groupBy(fn (Project $project) => $project->category->name),
            'inviteData' => $inviteData,
            'oshigotoUrl' => url('/oshigoto?ref='.$agency->referral_code),
        ]);
    }
}
