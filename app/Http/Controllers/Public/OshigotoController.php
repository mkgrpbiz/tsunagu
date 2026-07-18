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
            $agencyId = (int) preg_replace('/\D/', '', $ref);
            $agency = Agency::find($agencyId);
        }

        $projects = Project::where('oshigoto_listed', true)
            ->with('category')
            ->orderBy('category_id')
            ->orderBy('id')
            ->get();

        $applyUrls = [];

        if ($agency) {
            foreach ($projects as $project) {
                $inviteLink = InviteLink::firstOrCreate(
                    ['agency_id' => $agency->id, 'project_id' => $project->id],
                    ['token' => Str::random(10)],
                );

                $applyUrls[$project->id] = url('/apply/'.$inviteLink->token);
            }
        }

        return view('public.oshigoto.index', [
            'projectsByCategory' => $projects->groupBy(fn (Project $project) => $project->category->name),
            'applyUrls' => $applyUrls,
            'agency' => $agency,
            'ref' => $ref,
        ]);
    }
}
