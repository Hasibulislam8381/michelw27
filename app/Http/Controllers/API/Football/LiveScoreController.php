<?php

namespace App\Http\Controllers\API\Football;

use App\Http\Controllers\Controller;
use App\Models\FavoriteTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Traits\ApiResponse;

class LiveScoreController extends Controller
{
    use ApiResponse;
    public function liveFixtures(Request $request)
    {

        try {
            $client = new \GuzzleHttp\Client();
            $url = config('services.api_football.base_url') . 'fixtures';

            $query = [];
            // Optional date filter
            if ($request->has('date') && !empty($request->date)) {
                $query['date'] = $request->date; // format: YYYY-MM-DD
            } else {
                $query['live'] = 'all'; // fetch all live matches if no date
            }

            $response = $client->get($url, [
                'headers' => [
                    'x-apisports-key' => config('services.api_football.api_key'),
                ],
                'query' => $query,
            ]);

            $data = json_decode($response->getBody(), true);

            $fixtures = $data['response'] ?? [];
            $userId = Auth::id();

            // Fetch the user's favorite teams
            $favoriteTeamIds = FavoriteTeam::where('user_id', $userId)->pluck('team_id')->toArray();

            // Define priority leagues
            $priorityLeagues = [
                2,   // Champions League
                39,  // England - Premier League
                140, // Spain - La Liga
                78,  // Germany - Bundesliga
                135, // Italy - Serie A
                61,  // France - Ligue 1
            ];

            // 1. Separate favorite team fixtures
            $favoriteTeamFixtures = collect($fixtures)
                ->filter(function ($fixture) use ($favoriteTeamIds) {
                    $homeTeam = $fixture['teams']['home'] ?? [];
                    $awayTeam = $fixture['teams']['away'] ?? [];
                    return in_array($homeTeam['id'], $favoriteTeamIds) || in_array($awayTeam['id'], $favoriteTeamIds);
                })
                ->map(function ($fixture) use ($favoriteTeamIds) {
                    $homeTeam = $fixture['teams']['home'] ?? [];
                    $awayTeam = $fixture['teams']['away'] ?? [];
                    if (in_array($homeTeam['id'], $favoriteTeamIds)) {
                        $fixture['is_favorite'] = true;
                        $fixture['favorite_team'] = $homeTeam['name'];
                    } elseif (in_array($awayTeam['id'], $favoriteTeamIds)) {
                        $fixture['is_favorite'] = true;
                        $fixture['favorite_team'] = $awayTeam['name'];
                    } else {
                        $fixture['is_favorite'] = false;
                        $fixture['favorite_team'] = null;
                    }
                    return $fixture;
                })
                ->values();

            // 2. Separate priority league fixtures (without checking for favorite teams)
            $priorityLeagueFixtures = collect($fixtures)
                ->filter(function ($fixture) use ($priorityLeagues) {
                    $leagueId = $fixture['league']['id'] ?? null;
                    return in_array($leagueId, $priorityLeagues);
                })
                ->sortBy(function ($fixture) use ($priorityLeagues) {
                    $leagueId = $fixture['league']['id'] ?? null;
                    return array_search($leagueId, $priorityLeagues);
                })
                ->values();

            // 3. Merge favorite team fixtures and priority league fixtures
            $allFixtures = $favoriteTeamFixtures
                ->merge($priorityLeagueFixtures);

            return response()->json([
                'status' => true,
                'fixtures' => $allFixtures,
            ]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], $e->getCode());
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getEventsByFixture(Request $request)
    {
        $fixtureId = $request->query('fixture_id');

        if (!$fixtureId) {
            return $this->error([], 'Fixture ID is required', 400);
        }

        try {
            $client = new \GuzzleHttp\Client();
            $url = config('services.api_football.base_url') . 'fixtures/events';

            $response = $client->get($url, [
                'headers' => [
                    'x-apisports-key' => config('services.api_football.api_key'),
                ],
                'query' => [
                    'fixture' => $fixtureId,
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            return $this->success(
                $data['response'] ?? [],
                'Fixture events retrieved successfully',
                200
            );
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return $this->error([], $e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }
    public function getStatisticsByFixture(Request $request)
    {
        $fixtureId = $request->query('fixture_id');

        if (!$fixtureId) {
            return $this->error([], 'Fixture ID is required', 400);
        }

        try {
            $client = new \GuzzleHttp\Client();
            $url = config('services.api_football.base_url') . 'fixtures/statistics';

            $response = $client->get($url, [
                'headers' => [
                    'x-apisports-key' => config('services.api_football.api_key'),
                ],
                'query' => [
                    'fixture' => $fixtureId,
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            return $this->success(
                $data['response'] ?? [],
                'Fixture statistics retrieved successfully',
                200
            );
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return $this->error([], $e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }
    public function getLineUpsByFixture(Request $request)
    {
        $fixtureId = $request->query('fixture_id');

        if (!$fixtureId) {
            return $this->error([], 'Fixture ID is required', 400);
        }

        try {
            $client = new \GuzzleHttp\Client();
            $url = config('services.api_football.base_url') . 'fixtures/lineups';

            $response = $client->get($url, [
                'headers' => [
                    'x-apisports-key' => config('services.api_football.api_key'),
                ],
                'query' => [
                    'fixture' => $fixtureId,
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            return $this->success(
                $data['response'] ?? [],
                'Lineups retrieved successfully',
                200
            );
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return $this->error([], $e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }
}
