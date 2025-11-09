<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MatchRating;
use App\Models\MatchRatingCaption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;

class MatchRatingController extends Controller
{
    use ApiResponse;
    public function store(Request $request)
    {
        $request->validate([
            'match_id' => 'required|integer',
            'team_id' => 'required|integer',
            'caption' => 'required|string|max:1000',
            'ratings' => 'required|array|min:1',
            'ratings.*.entity_type' => 'required|in:coach,player',
            'ratings.*.entity_id' => 'required|integer',
            'ratings.*.name' => 'required|string|max:255',
            'ratings.*.photo' => 'nullable|string',
            'ratings.*.rating' => 'required|numeric|min:0|max:10',
            'ratings.*.is_mom' => 'boolean',
        ]);

        DB::beginTransaction();

        try {
            $matchId = $request->match_id;
            $teamId = $request->team_id;
            $userId = Auth::id();

            // Check if user already rated this match/team
            $existingCaption = MatchRatingCaption::where('match_id', $matchId)
                ->where('team_id', $teamId)
                ->where('rated_by', $userId)
                ->first();

            if ($existingCaption) {
                return $this->error([], 'You have already rated this match.', 422);
            }

            // Create caption
            $caption = MatchRatingCaption::create([
                'match_id' => $matchId,
                'team_id' => $teamId,
                'rated_by' => $userId,
                'caption' => $request->caption,
                'submitted_at' => now(),
            ]);

            $ratingsData = [];

            foreach ($request->ratings as $r) {
                $ratingsData[] = [
                    'caption_id' => $caption->id,
                    'match_id' => $matchId,
                    'team_id' => $teamId,
                    'entity_type' => $r['entity_type'],
                    'entity_id' => $r['entity_id'],
                    'name' => $r['name'],
                    'photo' => $r['photo'] ?? null,
                    'rating' => $r['rating'],
                    'is_mom' => $r['is_mom'] ?? false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            MatchRating::insert($ratingsData);

            DB::commit();

            return $this->success([
                'caption' => $caption,
                'ratings' => $ratingsData,
            ], 'Ratings submitted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error([], $e->getMessage(), 500);
        }
    }
}
