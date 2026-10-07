<?php

namespace App\Http\Controllers\Agency;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\InviteLink;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->withCount(['projects' => fn ($query) => $query->where('status', ProjectStatus::Published)])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (Category $category) => $category->projects_count > 0)
            ->values();

        return view('agency.projects.index', [
            'categories' => $categories,
        ]);
    }

    public function category(Category $category, Request $request): View
    {
        $query = $category->projects()
            ->where('status', ProjectStatus::Published)
            ->orderBy('sort_order');

        $filterOptions = [];

        if ($category->has_job_fields) {
            $published = $category->projects()->where('status', ProjectStatus::Published);

            $filterOptions = [
                'region' => $published->clone()->whereNotNull('region')->distinct()->orderBy('region')->pluck('region'),
                'job_type' => $published->clone()->whereNotNull('job_type')->distinct()->orderBy('job_type')->pluck('job_type'),
                'employment_type' => $published->clone()->whereNotNull('employment_type')->distinct()->orderBy('employment_type')->pluck('employment_type'),
            ];

            foreach (['region', 'job_type', 'employment_type'] as $field) {
                $query->when($request->filled($field), fn ($q) => $q->where($field, $request->query($field)));
            }
        }

        return view('agency.projects.category', [
            'category' => $category,
            'projects' => $query->get(),
            'filterOptions' => $filterOptions,
            'filters' => $request->only(['region', 'job_type', 'employment_type']),
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
