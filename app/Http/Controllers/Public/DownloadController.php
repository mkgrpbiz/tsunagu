<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\SalesMaterial;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function salesMaterial(SalesMaterial $salesMaterial): StreamedResponse
    {
        abort_unless($salesMaterial->file_path, 404);

        return Storage::disk('public')->download(
            $salesMaterial->file_path,
            $salesMaterial->original_filename ?: $salesMaterial->title.'.pdf'
        );
    }

    public function projectSalesMaterial(Project $project): StreamedResponse
    {
        abort_unless($project->sales_material_path, 404);

        return Storage::disk('public')->download(
            $project->sales_material_path,
            $project->sales_material_original_filename ?: $project->name.'.pdf'
        );
    }
}
