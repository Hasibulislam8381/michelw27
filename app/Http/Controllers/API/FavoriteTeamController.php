<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\FavoriteTeam;
use App\Notifications\UserNotification;
use App\Traits\ApiResponse;

class FavoriteTeamController extends Controller
{
    use ApiResponse;

    /**
     * Toggle favorite team for authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleFavoriteTeam(Request $request)
    {
        $request->validate([
            'team_id'    => 'required|integer',
            'team_name'  => 'required|string|max:255',
            'team_logo'  => 'nullable|string|max:255',
            'is_national' => 'required|boolean',
        ]);

        $userId = Auth::id();
        $user = Auth::user();
        $favorite = FavoriteTeam::where('user_id', $userId)
            ->where('team_id', $request->team_id)
            ->first();

        if ($favorite) {
            $favorite->delete();
            return $this->success([], 'Team removed from favorites', 200);
        } else {
            $team = new FavoriteTeam();
            $team->user_id     = $userId;
            $team->team_id     = $request->team_id;
            $team->team_name   = $request->team_name;
            $team->team_logo   = $request->team_logo;
            $team->is_national = $request->is_national;
            $team->save();
            $user->notify(new UserNotification(
                'Favorite Team Added',
                "{$request->team_name} has been added to your favorite teams.",
                ['team_id' => $request->team_id, 'action' => 'added']
            ));
            return $this->success($team, 'Team added to favorites', 200);
        }
    }

    public function getFavoriteTeams()
    {
        $userId = Auth::id();

        $favorites = FavoriteTeam::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        $nationalTeams = $favorites->where('is_national', true)->values();
        $clubTeams     = $favorites->where('is_national', false)->values();

        return $this->success([
            'national_teams' => $nationalTeams,
            'club_teams'     => $clubTeams,
        ], 'Favorite teams fetched successfully', 200);
    }
}
