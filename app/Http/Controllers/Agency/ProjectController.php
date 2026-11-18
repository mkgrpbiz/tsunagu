<?php

namespace App\Http\Controllers\Agency;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Industry;
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

    /**
     * 個別求人とは別に作った大枠募集（キャリア相談の無料相談案件）の業種。
     * 件数で埋もれさせたくないため、業種一覧では常に先頭固定にする。
     */
    private const PINNED_INDUSTRY_NAME = '就職・転職・キャリア相談';

    private const JOB_SEARCH_FIELDS = [
        'name',
        'job_description',
        'work_location',
        'annual_income',
        'salary_benefits',
        'holidays',
        'qualifications',
        'age_requirement',
        'employment_type',
        'working_hours',
    ];

    public function category(Category $category, Request $request): View
    {
        if ($category->has_job_fields && trim((string) $request->query('keyword')) === '') {
            $industries = Industry::query()
                ->withCount(['projects' => fn ($query) => $query->where('category_id', $category->id)->where('status', ProjectStatus::Published)])
                ->orderByRaw('CASE WHEN name = ? THEN 0 WHEN name = ? THEN 2 ELSE 1 END', [self::PINNED_INDUSTRY_NAME, 'その他'])
                ->orderByDesc('projects_count')
                ->get()
                ->filter(fn (Industry $industry) => $industry->projects_count > 0 || $industry->name === self::PINNED_INDUSTRY_NAME)
                ->values();

            // 大枠募集（無料相談）と個別求人は小カテゴリーとして別物なので、見出しを分けて表示する
            $industryGroups = collect([
                ['label' => '無料相談', 'industries' => $industries->filter(fn (Industry $i) => $i->name === self::PINNED_INDUSTRY_NAME)->values()],
                ['label' => '個別求人', 'industries' => $industries->reject(fn (Industry $i) => $i->name === self::PINNED_INDUSTRY_NAME)->values()],
            ])->filter(fn (array $group) => $group['industries']->isNotEmpty())->values();

            return view('agency.projects.industries', [
                'category' => $category,
                'industryGroups' => $industryGroups,
            ]);
        }

        return $this->listProjects($category, null, $request);
    }

    public function industry(Category $category, Industry $industry, Request $request): View
    {
        return $this->listProjects($category, $industry, $request);
    }

    private function listProjects(Category $category, ?Industry $industry, Request $request): View
    {
        $query = $category->projects()
            ->where('status', ProjectStatus::Published)
            ->when($industry, fn ($q) => $q->where('industry_id', $industry->id))
            ->orderBy('sort_order');

        $keyword = $category->has_job_fields ? trim((string) $request->query('keyword')) : '';

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                foreach (self::JOB_SEARCH_FIELDS as $field) {
                    $q->orWhere($field, 'like', '%'.$keyword.'%');
                }
            });
        }

        $projects = $query->paginate(50)->withQueryString();

        $agency = Auth::guard('agency')->user();
        $inviteUrls = $projects->getCollection()->mapWithKeys(function (Project $project) use ($agency) {
            $inviteLink = InviteLink::firstOrCreate(
                ['agency_id' => $agency->id, 'project_id' => $project->id],
                ['token' => Str::random(10)],
            );

            return [$project->id => url('/apply/'.$inviteLink->token)];
        });

        $view = $industry ? 'agency.projects.industry' : 'agency.projects.category';

        return view($view, [
            'category' => $category,
            'industry' => $industry,
            'projects' => $projects,
            'keyword' => $keyword,
            'inviteUrls' => $inviteUrls,
        ]);
    }
}
