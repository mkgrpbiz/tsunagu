<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Announcement;
use App\Models\Category;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
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

        $projects = Project::query()
            ->select('projects.*')
            ->join('categories', 'categories.id', '=', 'projects.category_id')
            ->with(['category', 'referrerAgency'])
            ->when($status !== 'all', fn ($query) => $query->where('projects.status', $status))
            ->when($categoryId !== 'all', fn ($query) => $query->where('projects.category_id', $categoryId))
            ->orderByRaw("CASE projects.status WHEN 'published' THEN 1 WHEN 'paused' THEN 2 WHEN 'closed' THEN 3 ELSE 4 END")
            ->orderBy('categories.sort_order')
            ->orderBy('projects.sort_order')
            ->get();

        return view('admin.projects.index', [
            'projects' => $projects,
            'status' => $status,
            'statusCounts' => $statusCounts,
            'totalCount' => Project::count(),
            'categories' => Category::orderBy('sort_order')->get(),
            'categoryId' => $categoryId,
            'categoryCounts' => $categoryCounts,
            'canReorder' => $status === 'all' && $categoryId !== 'all',
        ]);
    }

    public function create(): View
    {
        return view('admin.projects.create', [
            'project' => new Project,
            'categories' => Category::orderBy('sort_order')->get(),
            'statuses' => ProjectStatus::cases(),
            'agencies' => Agency::where('is_collaboration_partner', true)->with('referredBy')->orderBy('name')->get(),
            'clientNames' => Project::whereNotNull('client_name')->distinct()->orderBy('client_name')->pluck('client_name'),
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:projects,id'],
        ]);

        foreach (array_values($data['order']) as $index => $id) {
            Project::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        return response()->json(['status' => 'ok']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['sort_order'] = Project::where('category_id', $data['category_id'])->max('sort_order') + 1;

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('projects', 'public');
        }

        if ($request->hasFile('sales_material')) {
            $data['sales_material_path'] = $request->file('sales_material')->store('projects/sales-materials', 'public');
        }

        $project = Project::create($data);

        Announcement::create([
            'body' => "{$project->name}を{$project->category->name}に追加しました。",
            'is_draft' => true,
        ]);

        return redirect()->route('admin.projects.index')->with('status', '案件を作成しました。');
    }

    public function edit(Project $project): View
    {
        return view('admin.projects.edit', [
            'project' => $project,
            'categories' => Category::orderBy('sort_order')->get(),
            'statuses' => ProjectStatus::cases(),
            'agencies' => Agency::where('is_collaboration_partner', true)->with('referredBy')->orderBy('name')->get(),
            'clientNames' => Project::whereNotNull('client_name')->distinct()->orderBy('client_name')->pluck('client_name'),
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $data = $this->validated($request);

        if ($data['category_id'] != $project->category_id) {
            $data['sort_order'] = Project::where('category_id', $data['category_id'])->max('sort_order') + 1;
        }

        if ($request->hasFile('image')) {
            if ($project->image_path) {
                Storage::disk('public')->delete($project->image_path);
            }
            $data['image_path'] = $request->file('image')->store('projects', 'public');
        }

        if ($request->hasFile('sales_material')) {
            if ($project->sales_material_path) {
                Storage::disk('public')->delete($project->sales_material_path);
            }
            $data['sales_material_path'] = $request->file('sales_material')->store('projects/sales-materials', 'public');
        }

        $project->update($data);

        return redirect()->route('admin.projects.index')->with('status', '案件を更新しました。');
    }

    public function duplicate(Project $project): RedirectResponse
    {
        $new = $project->replicate();
        $new->name = $project->name.'（コピー）';
        $new->status = ProjectStatus::Paused;
        $new->sort_order = Project::where('category_id', $project->category_id)->max('sort_order') + 1;
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

        if ($project->sales_material_path) {
            Storage::disk('public')->delete($project->sales_material_path);
        }

        $project->delete();

        return redirect()->route('admin.projects.index')->with('status', '案件を削除しました。');
    }

    private function validated(Request $request): array
    {
        $requireAtLeastOnePrice = function (string $label) use ($request) {
            return function ($attribute, $value, $fail) use ($request, $label) {
                $mode = $request->input(str_replace('_unit_price', '_price_mode', $attribute));

                if ($mode !== 'fixed') {
                    return;
                }

                $filled = collect($value ?? [])->filter(fn ($v) => $v !== null && $v !== '');

                if ($filled->isEmpty()) {
                    $fail("{$label}を1つ以上入力してください。");
                }
            };
        };

        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'legacy_names' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
            'sales_material' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'is_recurring' => ['nullable', 'boolean'],
            'bulk_link_enabled' => ['nullable', 'boolean'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'referrer_agency_id' => ['nullable', 'exists:agencies,id'],
            'partner_agency_id' => ['nullable', 'exists:agencies,id'],
            'tsunagu_price_mode' => ['required', 'in:fixed,variable'],
            'agency_price_mode' => ['required', 'in:fixed,variable'],
            'tsunagu_unit_price' => ['nullable', 'array', $requireAtLeastOnePrice('TSUNAGU単価')],
            'tsunagu_unit_price.*' => ['nullable', 'integer', 'min:0'],
            'agency_unit_price' => ['nullable', 'array', $requireAtLeastOnePrice('パートナー単価')],
            'agency_unit_price.*' => ['nullable', 'integer', 'min:0'],
            'payment_timing' => ['nullable', 'string', 'max:255'],
            'recruitment_template' => ['nullable', 'string'],
            'line_auto_message' => ['nullable', 'string'],
        ]);

        $data['is_recurring'] = $request->boolean('is_recurring');
        $data['bulk_link_enabled'] = $request->boolean('bulk_link_enabled');

        $tsunaguPrices = collect($data['tsunagu_unit_price'] ?? [])->filter(fn ($v) => $v !== null && $v !== '')->map(fn ($v) => (int) $v)->values()->all();
        $agencyPrices = collect($data['agency_unit_price'] ?? [])->filter(fn ($v) => $v !== null && $v !== '')->map(fn ($v) => (int) $v)->values()->all();

        $data['tsunagu_unit_prices'] = $data['tsunagu_price_mode'] === 'fixed' ? $tsunaguPrices : null;
        $data['agency_unit_prices'] = $data['agency_price_mode'] === 'fixed' ? $agencyPrices : null;
        unset($data['tsunagu_price_mode'], $data['agency_price_mode'], $data['tsunagu_unit_price'], $data['agency_unit_price']);

        return $data;
    }
}
