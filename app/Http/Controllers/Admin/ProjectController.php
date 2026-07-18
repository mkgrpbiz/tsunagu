<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Announcement;
use App\Models\Category;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');
        $categoryId = $request->query('category', 'all');

        $statusCounts = Project::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $categoryCounts = Project::selectRaw('category_id, count(*) as count')
            ->groupBy('category_id')
            ->pluck('count', 'category_id');

        $projects = Project::with(['category', 'referrerAgency'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($categoryId !== 'all', fn ($query) => $query->where('category_id', $categoryId))
            ->latest()
            ->get();

        return view('admin.projects.index', [
            'projects' => $projects,
            'status' => $status,
            'statusCounts' => $statusCounts,
            'totalCount' => Project::count(),
            'categories' => Category::orderBy('name')->get(),
            'categoryId' => $categoryId,
            'categoryCounts' => $categoryCounts,
        ]);
    }

    public function create(): View
    {
        return view('admin.projects.create', [
            'project' => new Project,
            'categories' => Category::orderBy('name')->get(),
            'statuses' => ProjectStatus::cases(),
            'agencies' => Agency::where('is_collaboration_partner', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('projects', 'public');
        }

        $project = Project::create($data);

        Announcement::create([
            'body' => "{$project->name}を{$project->category->name}に追加しました。",
        ]);

        return redirect()->route('admin.projects.index')->with('status', '案件を作成しました。');
    }

    public function edit(Project $project): View
    {
        $agencies = Agency::where('is_collaboration_partner', true)->orderBy('name')->get();

        if ($project->referrer_agency_id && ! $agencies->contains('id', $project->referrer_agency_id)) {
            $agencies->push($project->referrerAgency);
        }

        return view('admin.projects.edit', [
            'project' => $project,
            'categories' => Category::orderBy('name')->get(),
            'statuses' => ProjectStatus::cases(),
            'agencies' => $agencies,
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            if ($project->image_path) {
                Storage::disk('public')->delete($project->image_path);
            }
            $data['image_path'] = $request->file('image')->store('projects', 'public');
        }

        $project->update($data);

        return redirect()->route('admin.projects.index')->with('status', '案件を更新しました。');
    }

    public function duplicate(Project $project): RedirectResponse
    {
        $new = $project->replicate();
        $new->name = $project->name.'（コピー）';
        $new->status = ProjectStatus::Paused;
        $new->save();

        return redirect()->route('admin.projects.edit', $new)->with('status', '案件を複製しました。');
    }

    public function destroy(Project $project): RedirectResponse
    {
        if ($project->inquiries()->exists()) {
            return back()->with('error', 'この案件には問い合わせが紐づいているため削除できません。ステータスを「終了」にしてください。');
        }

        if ($project->image_path) {
            Storage::disk('public')->delete($project->image_path);
        }

        $project->delete();

        return redirect()->route('admin.projects.index')->with('status', '案件を削除しました。');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'client_name' => ['nullable', 'string', 'max:255'],
            'referrer_agency_id' => ['nullable', 'exists:agencies,id'],
            'tsunagu_unit_price' => ['required', 'integer', 'min:0'],
            'agency_unit_price' => ['required', 'integer', 'min:0'],
            'payment_timing' => ['nullable', 'string', 'max:255'],
            'recruitment_template' => ['nullable', 'string'],
            'line_auto_message' => ['nullable', 'string'],
        ]);
    }
}
