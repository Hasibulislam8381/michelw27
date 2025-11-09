<?php

namespace App\Http\Controllers\Api\Football;

use App\Http\Controllers\Controller;
use App\Models\FavoriteTeam;
use App\Models\Team;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    use ApiResponse;

    // Method to get all teams from leagues
    public function getClubTeams()
    {
        $httpRequest = new \GuzzleHttp\Client();
        $search = request()->query('search');

        // List of club leagues
        $leagues = [
            39,   // English Premier League
            140,  // La Liga
            135,  // Serie A
            78,   // Bundesliga
            61,   // Ligue 1
            2     // UEFA Champions League
        ];

        $season = date('Y');
        $allTeams = [];

        try {
            // Fetch all club teams
            foreach ($leagues as $leagueId) {
                $response = $httpRequest->get(config('services.api_football.base_url') . 'teams', [
                    'headers' => [
                        'x-apisports-key' => config('services.api_football.api_key'),
                    ],
                    'query' => [
                        'league' => $leagueId,
                        'season' => $season,
                    ],
                ]);
                $data = json_decode($response->getBody(), true);
                if (!empty($data['response'])) {
                    $allTeams = array_merge($allTeams, $data['response']);
                }
            }

            if ($search) {
                $allTeams = array_filter($allTeams, function ($team) use ($search) {
                    return stripos($team['team']['name'], $search) !== false;
                });
                $allTeams = array_values($allTeams);
            }

            $userId = auth()->id();
            $favoriteTeamIds = FavoriteTeam::where('user_id', $userId)->pluck('team_id')->toArray();
            $allTeams = array_map(function ($team) use ($favoriteTeamIds) {
                unset($team['venue']);
                $team['is_favorite'] = in_array($team['team']['id'], $favoriteTeamIds);
                return $team;
            }, $allTeams);

            return response()->json([
                'status' => true,
                'teams' => $allTeams,
            ]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return $this->error([], $e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }

    public function getNationalTeams(Request $request)
    {
        $search = $request->query('search');
        $page = $request->query('page', 1);
        $limit = 10;
        $start = ($page - 1) * $limit;
        $query = Team::where('is_national', true);
        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $totalTeams = $query->count();

        $nationalTeams = $query->skip($start)->take($limit)->get();
        $userId = auth()->id();
        $favoriteTeamIds = FavoriteTeam::where('user_id', $userId)->pluck('team_id')->toArray();

        // Add favorite status and remove venue info
        $nationalTeams = $nationalTeams->map(function ($team) use ($favoriteTeamIds) {
            $team->is_favorite = in_array($team->team_id, $favoriteTeamIds);
            return $team;
        });

        return response()->json([
            'status' => true,
            'national_teams' => $nationalTeams,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalTeams / $limit),
                'total_teams' => $totalTeams,
            ],
        ]);
    }
}
