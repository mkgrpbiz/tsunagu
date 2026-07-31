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
        $projects = Project::query()
            ->select('projects.*')
            ->join('categories', 'categories.id', '=', 'projects.category_id')
            ->with('category')
            ->where('projects.status', ProjectStatus::Published)
            ->orderBy('categories.sort_order')
            ->orderBy('projects.sort_order')
            ->get();

        $categories = $projects->groupBy('category_id')->map(fn ($group) => [
            'category' => $group->first()->category,
            'projects' => $group,
        ])->values();

        return view('agency.projects.index', [
            'categories' => $categories,
        ]);
    }

    public function show(Project $project): View
    {
        abort_unless($project->status === ProjectStatus::Published, 404);

        $agency = Auth::guard('agency')->user();

        $inviteLink = InviteLink::firstOrCreate(
            ['agency_id' => $agency->id, 'project_id' => $project->id],
            ['token' => Str::random(10)],
        );

        return view('agency.projects.show', [
            'project' => $project,
            'inviteUrl' => url('/apply/'.$inviteLink->token),
        ]);
    }
}
