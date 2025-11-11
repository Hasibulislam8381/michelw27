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
            'home_team_name' => 'required|string|max:255',
            'away_team_name' => 'required|string|max:255',
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
                'home_team_name' => $request->home_team_name,
                'away_team_name' => $request->away_team_name,
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
    public function feed()
    {
        $userId = Auth::id();

        $captions = MatchRatingCaption::with([
            'user:id,name',
            'ratings' => function ($q) {
                $q->where('is_mom', true);
            },
            'reactions' // load reactions relation
        ])->latest()->get();

        $feed = $captions->map(function ($caption) use ($userId) {
            $mom = $caption->ratings->first();

            $isFollowed = \DB::table('follows')
                ->where('follower_id', $userId)
                ->where('following_id', $caption->rated_by)
                ->exists();

            // Count reactions by type
            $likesCount = $caption->reactions->where('type', 'like')->count();
            $dislikesCount = $caption->reactions->where('type', 'dislike')->count();
            $commentCount = $caption->comments->count();
            return [
                'caption_id' => $caption->id,
                'user_id' => $caption->rated_by,
                'user_name' => $caption->user->name ?? 'Unknown',
                'home_team_name' => $caption->home_team_name,
                'away_team_name' => $caption->away_team_name,
                'team_id' => $caption->team_id,
                'caption' => $caption->caption,
                'mom_player_name' => $mom->name ?? null,
                'is_followed' => $isFollowed ? 1 : 0,
                'likes_count' => $likesCount,
                'dislikes_count' => $dislikesCount,
                'comment_count' => $commentCount

            ];
        });

        $grouped = [
            'followed' => $feed->where('is_followed', 1)->values(),
            'not_followed' => $feed->where('is_followed', 0)->values(),
        ];

        return $this->success($grouped, 'Feed fetched successfully.', 200);
    }
    public function myRating()
    {
        $userId = Auth::id();
        $latestCaptions = MatchRatingCaption::where('rated_by', $userId)
            ->latest('submitted_at')
            ->get();

        if ($latestCaptions->isEmpty()) {
            return $this->error([], 'No ratings found for this user.', 404);
        }

        foreach ($latestCaptions as $caption) {
            $teamId = $caption->team_id;
            $team = \App\Models\Team::find($teamId);
            if (!$team) {
                $latestCaption = $caption;
                break;
            }
        }

        if (!$latestCaption) {
            return $this->error([], 'No ratings found for this user.', 404);
        }

        $matchId = $latestCaption->match_id;
        $teamId = $latestCaption->team_id;

        // Total how many users rated same match/team
        $totalUserCount = MatchRatingCaption::where('match_id', $matchId)
            ->where('team_id', $teamId)
            ->distinct('rated_by')
            ->count('rated_by');

        // All ratings for that match/team
        $ratings = MatchRating::where('match_id', $matchId)
            ->where('team_id', $teamId)
            ->get();

        if ($ratings->isEmpty()) {
            return $this->error([], 'No ratings found for this match/team.', 404);
        }

        // Overall team average rating
        $averageRating = round($ratings->avg('rating'), 2);

        // Average rating per player/coach
        $averageByEntity = $ratings->groupBy('entity_id')->map(function ($items) {
            return [
                'entity_id' => $items->first()->entity_id,
                'name' => $items->first()->name,
                'photo' => $items->first()->photo,
                'entity_type' => $items->first()->entity_type,
                'average_rating' => round($items->avg('rating'), 2),
            ];
        });

        // Find top-rated player only (exclude coaches)
        $topRatedPlayer = $averageByEntity
            ->where('entity_type', 'player')
            ->sortByDesc('average_rating')
            ->first();

        //  Find the most voted MOM (appears most times as is_mom = true)
        $momPlayer = MatchRating::where('match_id', $matchId)
            ->where('team_id', $teamId)
            ->where('is_mom', true)
            ->select('entity_id', 'name', 'photo', 'entity_type', \DB::raw('COUNT(*) as total_votes'))
            ->groupBy('entity_id', 'name', 'photo', 'entity_type')
            ->orderByDesc('total_votes')
            ->first();

        // Find Top Players (weighted by rating + MOM votes)
        $momVoteCounts = MatchRating::where('match_id', $matchId)
            ->where('team_id', $teamId)
            ->where('is_mom', true)
            ->select('entity_id', \DB::raw('COUNT(*) as total_mom_votes'))
            ->groupBy('entity_id')
            ->pluck('total_mom_votes', 'entity_id');

        $topPlayers = collect($averageByEntity)
            ->where('entity_type', 'player')
            ->map(function ($player) use ($momVoteCounts) {
                $player['mom_votes'] = $momVoteCounts[$player['entity_id']] ?? 0;
                // Weighted score (average_rating * 2 + MOM votes)
                $player['score'] = ($player['average_rating'] * 2) + $player['mom_votes'];
                return $player;
            })
            ->sortByDesc('score')
            ->take(4) // 🔥 Top 4 players
            ->values();

        // Prepare response
        $response = [
            'latest_match' => [
                'match_id' => $matchId,
                'team_id' => $teamId,
                'home_team_name' => $latestCaption->home_team_name,
                'away_team_name' => $latestCaption->away_team_name,
                'user_caption' => $latestCaption->caption,
                'submitted_at' => $latestCaption->submitted_at,
            ],
            'total_users_rated' => $totalUserCount,
            'average_team_rating' => $averageRating,
            'top_rated_player' => $topRatedPlayer ? [
                'entity_id' => $topRatedPlayer['entity_id'],
                'name' => $topRatedPlayer['name'],
                'photo' => $topRatedPlayer['photo'],
                'entity_type' => $topRatedPlayer['entity_type'],
                'average_rating' => $topRatedPlayer['average_rating'],
            ] : null,
            'mom_player' => $momPlayer ? [
                'entity_id' => $momPlayer->entity_id,
                'name' => $momPlayer->name,
                'photo' => $momPlayer->photo,
                'entity_type' => $momPlayer->entity_type,
                'total_votes' => $momPlayer->total_votes,
            ] : null,
            'top_players' => $topPlayers,
        ];

        return $this->success($response, 'Latest match summary with top-rated, MOM & top players fetched successfully.');
    }
    public function myNationalRating()
    {
        $userId = Auth::id();

        // 🔹 Get user's latest rated captions in descending order
        $latestCaptions = MatchRatingCaption::where('rated_by', $userId)
            ->latest('submitted_at')
            ->get();

        if ($latestCaptions->isEmpty()) {
            return $this->error([], 'No ratings found for this user.', 404);
        }

        $nationalCaption = null;

        // 🔹 Loop through ratings until we find one that belongs to a "national" team
        foreach ($latestCaptions as $caption) {
            $teamId = $caption->team_id;
            $team = \App\Models\Team::find($teamId);

            // 👉 If team not found in teams table, consider it "national"
            if ($team) {
                $nationalCaption = $caption;
                break;
            }
        }

        if (!$nationalCaption) {
            return $this->error([], 'No national match ratings found.', 404);
        }

        $matchId = $nationalCaption->match_id;
        $teamId = $nationalCaption->team_id;

        // 🔹 Total unique users who rated this match/team
        $totalUserCount = MatchRatingCaption::where('match_id', $matchId)
            ->where('team_id', $teamId)
            ->distinct('rated_by')
            ->count('rated_by');

        // 🔹 All ratings
        $ratings = MatchRating::where('match_id', $matchId)
            ->where('team_id', $teamId)
            ->get();

        if ($ratings->isEmpty()) {
            return $this->error([], 'No ratings found for this match/team.', 404);
        }

        // 🔹 Overall average rating
        $averageRating = round($ratings->avg('rating'), 2);

        // 🔹 Average rating per entity
        $averageByEntity = $ratings->groupBy('entity_id')->map(function ($items) {
            return [
                'entity_id' => $items->first()->entity_id,
                'name' => $items->first()->name,
                'photo' => $items->first()->photo,
                'entity_type' => $items->first()->entity_type,
                'average_rating' => round($items->avg('rating'), 2),
            ];
        });

        // 🔹 Top-rated player (exclude coaches)
        $topRatedPlayer = $averageByEntity
            ->where('entity_type', 'player')
            ->sortByDesc('average_rating')
            ->first();

        // 🔹 MOM player
        $momPlayer = MatchRating::where('match_id', $matchId)
            ->where('team_id', $teamId)
            ->where('is_mom', true)
            ->select('entity_id', 'name', 'photo', 'entity_type', \DB::raw('COUNT(*) as total_votes'))
            ->groupBy('entity_id', 'name', 'photo', 'entity_type')
            ->orderByDesc('total_votes')
            ->first();

        // 🔹 MOM vote counts
        $momVoteCounts = MatchRating::where('match_id', $matchId)
            ->where('team_id', $teamId)
            ->where('is_mom', true)
            ->select('entity_id', \DB::raw('COUNT(*) as total_mom_votes'))
            ->groupBy('entity_id')
            ->pluck('total_mom_votes', 'entity_id');

        // 🔹 Top players (weighted by rating + MOM votes)
        $topPlayers = collect($averageByEntity)
            ->where('entity_type', 'player')
            ->map(function ($player) use ($momVoteCounts) {
                $player['mom_votes'] = $momVoteCounts[$player['entity_id']] ?? 0;
                $player['score'] = ($player['average_rating'] * 2) + $player['mom_votes'];
                return $player;
            })
            ->sortByDesc('score')
            ->take(4)
            ->values();

        // 🔹 Prepare final response
        $response = [
            'latest_match' => [
                'match_id' => $matchId,
                'team_id' => $teamId,
                'home_team_name' => $nationalCaption->home_team_name,
                'away_team_name' => $nationalCaption->away_team_name,
                'user_caption' => $nationalCaption->caption,
                'submitted_at' => $nationalCaption->submitted_at,
            ],
            'total_users_rated' => $totalUserCount,
            'average_team_rating' => $averageRating,
            'top_rated_player' => $topRatedPlayer ? [
                'entity_id' => $topRatedPlayer['entity_id'],
                'name' => $topRatedPlayer['name'],
                'photo' => $topRatedPlayer['photo'],
                'entity_type' => $topRatedPlayer['entity_type'],
                'average_rating' => $topRatedPlayer['average_rating'],
            ] : null,
            'mom_player' => $momPlayer ? [
                'entity_id' => $momPlayer->entity_id,
                'name' => $momPlayer->name,
                'photo' => $momPlayer->photo,
                'entity_type' => $momPlayer->entity_type,
                'total_votes' => $momPlayer->total_votes,
            ] : null,
            'top_players' => $topPlayers,
            'team_type' => 'national',
        ];

        return $this->success($response, 'Latest national match summary with top-rated, MOM & top players fetched successfully.');
    }
}
