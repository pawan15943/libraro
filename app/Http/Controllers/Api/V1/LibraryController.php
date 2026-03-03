<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Library;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;

class LibraryController extends Controller
{
     /*
    |--------------------------------------------------------------------------
    | Branch Dropdown API
    |--------------------------------------------------------------------------
    */

  public function branches()
   {
      $library = auth('library_api')->user();

      $branches = Branch::where('library_id', $library->id)
         ->where('status', 1)
         ->select('id', 'name')
         ->get();

      if ($branches->isEmpty()) {
         return response()->json([
               'status' => false,
               'message' => 'No active branches found',
               'data' => []
         ], 404);
      }

      // Ensure current_branch exists in active branches
      $activeBranchId = $branches->pluck('id')->contains($library->current_branch)? $library->current_branch
               : $branches->first()->id;

      return response()->json([
         'status' => true,
         'data' => [
               'active_branch_id' => $activeBranchId,
               'branches' => $branches
         ]
      ]);
   }

    /*
    |--------------------------------------------------------------------------
    | Dashboard API
    |--------------------------------------------------------------------------
    */

    public function dashboard(Request $request, DashboardService $service)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'type'      => 'nullable|in:daily,monthly'
        ]);

        $library = auth('library_api')->user();

        $branches = Branch::where('library_id', $library->id)
         ->where('status', 1)
         ->select('id', 'name')
         ->get();

        // 🔐 Security: ensure branch belongs to library
        $branch =  $branches->pluck('id')->contains($library->current_branch)? $library->current_branch
               : $branches->first()->id;

        $type = $validated['type'] ?? 'daily';

        $data = $service->getDashboardData($branch, $type);

        return response()->json([
            'status' => true,
            'code' => 200,
            'data' => $data
        ]);
    }
       
}
