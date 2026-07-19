<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\InviteLink;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OshigotoController extends Controller
{
    public function index(Request $request): View
    {
        $ref = (string) $request->query('ref', '');
        $agency = null;

        if (filled($ref)) {
            $agency = Agency::where('oshigoto_token', $ref)->first();
        }

        $projects = Project::query()
            ->select('projects.*')
            ->join('categories', 'categories.id', '=', 'projects.category_id')
            ->with('category')
            ->where('projects.oshigoto_listed', true)
            ->orderBy('categories.sort_order')
            ->orderBy('projects.sort_order')
            ->get();

        $applyUrls = [];
        $offerTexts = [];

        foreach ($projects as $project) {
            $offerTexts[$project->id] = trim(str_replace(
                ['✅【お申し込みはこちら】', '{invite_url}'],
                '',
                (string) $project->recruitment_template
            ));

            if ($agency) {
                $inviteLink = InviteLink::firstOrCreate(
                    ['agency_id' => $agency->id, 'project_id' => $project->id],
                    ['token' => Str::random(10)],
                );

                $applyUrls[$project->id] = $inviteLink->applyUrl();
            }
        }

        return view('public.oshigoto.index', [
            'projectsByCategory' => $projects->groupBy(fn (Project $project) => $project->category->name),
            'applyUrls' => $applyUrls,
            'offerTexts' => $offerTexts,
            'agency' => $agency,
            'ref' => $ref,
        ]);
    }
}
