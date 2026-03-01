<?php
namespace App\Services;

use App\Models\Branch;
use App\Models\Hour;
use App\Models\Learner;
use App\Models\LearnerDetail;
use Illuminate\Support\Facades\Auth;
use App\Models\Library;
use App\Models\LibraryTransaction;
use App\Models\PlanType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LibraryConfigurationService
{
   
    public function configure(array $validated,int $libraryId,$existingBranch,int $branchCount)
    {
        DB::beginTransaction();

        try {

           
            
            $validated['library_id']  = getLibraryId();
            $validated['display_name'] = $validated['display_name'] ?? $validated['name'];

           $floorses= $validated['floors'];
           $plans= $validated['plans'];

            $hour  = $validated['hour'];
            $seats = $validated['seats'];
            unset($validated['hour'], $validated['seats']);

            /* =========================
            FLOOR VALIDATION
            ========================= */
            $floors = collect($floorses)
                ->filter(fn ($floor) =>
                    filled($floor['name']) ||
                    filled($floor['from']) ||
                    filled($floor['to'])
                )
                ->values()
                ->toArray();

            $totalFloorSeats = 0;

            foreach ($floors as $index => $floor) {
                 //  If from/to is filled, name is required
                if ((filled($floor['from']) || filled($floor['to'])) && empty($floor['name'])) {
                    throw new \Exception(
                        "Floor name is required when seat range is provided (Row ".($index + 1).")"
                    );
                }

                if (empty($floor['from']) || empty($floor['to'])) {
                    throw new \Exception(
                        'Seat range is required for each floor.'
                    );
                }

                if ($floor['to'] < $floor['from']) {
                    throw new \Exception(
                        'Seat To must be greater than or equal to Seat From.'
                    );
                }

                $totalFloorSeats += ($floor['to'] - $floor['from']) + 1;
            }

            if ($totalFloorSeats > $seats) {
                throw new \Exception(
                    "Total floor seats ({$totalFloorSeats}) cannot exceed branch seats ({$seats})"
                );
            }

            /* =========================
            CREATE BRANCH
            ========================= */
            $branchData = collect($validated)->except([
                'plans',
                'monthdays',
                'floors',
            ])->toArray();

            $branch = $existingBranch ?? new Branch();
             $branch->fill($branchData);
            $branch->library_id = getLibraryId();
            
           if (!empty($validated['logo'])) {
                $branch->logo = $validated['logo'];
            }

            if (!empty($validated['features'])) {
                $branch->features = json_encode($validated['features']);
            }

            $branch->google_map = $validated['google_map'] ?? null;
            $branch->slug = $slug;
            $branch->save();

            /* =========================
            HOURS
            ========================= */
             Hour::updateOrCreate(
                [
                    'branch_id'  => $branch->id,
                    'library_id' => getLibraryId(),
                ],
                [
                    'hour'  => $hour,
                    'seats' => $seats,
                ]
            );

            /* =========================
            PLANS
            ========================= */
           if ($existingBranch || $branchCount == 0){
            
                    // DELETE REMOVED PLANS
                Plan::where('library_id', getLibraryId())
                    ->whereNotIn('name', $plans)
                    ->delete();

                $baseMonthDays = null;
                foreach ($plans as $plan) {
                    [$num, $type] = explode(' ', $plan);
                    if ((int)$num === 1 && strtoupper($type) === 'MONTH') {
                        $baseMonthDays = $request->monthdays ?: null;
                        break;
                    }
                }

                foreach ($plans as $plan) {
                    [$num, $type] = explode(' ', $plan);

                    Plan::updateOrCreate(
                        [
                            'library_id' => getLibraryId(),
                            'name'       => $plan,
                        ],
                        [
                            'plan_id'   => (int)$num,
                            'type'      => strtoupper($type),
                            'monthdays' => strtoupper($type) === 'MONTH'
                                ? $baseMonthDays
                                : null,
                        ]
                    );
                }
            }

            /* =========================
            FLOORS
            ========================= */
            Floor::where('branch_id', $branch->id)->delete();

            foreach ($floors as $index => $floor) {
                Floor::create([
                    'branch_id'   => $branch->id,
                    'name'        => $floor['name'],
                    'floor_no'    => $index + 1,
                    'from_seat'   => (int)$floor['from'],
                    'to_seat'     => (int)$floor['to'],
                    
                ]);
            }

            /* =========================
            LIBRARY IMAGES
            ========================= */
            if ($request->hasFile('library_images')) {
                foreach ($request->file('library_images') as $image) {
                    $image->store('uploads/library_images', 'public');
                }
            }
            Library::where('id',getLibraryId())->update([
                'current_branch'=> $branch->id
            ]);

            DB::commit();

            return response()->json([
            'status'   => true,
            'redirect' => route('library.home'),
                'message'  => 'Branch added successfully.'
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
