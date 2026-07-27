<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SharePoyUser;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SharePoyReportController extends Controller
{
    public function index(Request $request): View
    {
        $userId = trim((string) $request->query('user_id'));
        $sharePoyUser = null;
        $depositRecords = collect();

        if ($userId !== '') {
            $sharePoyUser = SharePoyUser::where('sharepoy_user_id', $userId)->first();

            if ($sharePoyUser) {
                $depositRecords = $sharePoyUser->depositRecords()->with('inquiry')->latest('deposit_date')->get();
            }
        }

        return view('admin.sharepoy_reports.index', [
            'userId' => $userId,
            'sharePoyUser' => $sharePoyUser,
            'depositRecords' => $depositRecords,
        ]);
    }
}
