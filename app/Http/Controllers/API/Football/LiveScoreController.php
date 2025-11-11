<?php

namespace App\Http\Controllers\API\Football;

use App\Http\Controllers\Controller;
use App\Models\FavoriteTeam;
use App\Models\MatchRating;
use App\Models\MatchRatingCaption;
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
    public function communityUserRating(Request $request)
    {
        $fixtureId = $request->query('fixture_id');
        $teamId = $request->query('team_id'); // required

        if (!$fixtureId || !$teamId) {
            return $this->error([], 'Fixture ID and Team ID are required', 400);
        }

        try {
            $client = new \GuzzleHttp\Client();
            $baseUrl = config('services.api_football.base_url');
            $apiKey = config('services.api_football.api_key');

            // 1️⃣ Get Lineups
            $lineupResponse = $client->get($baseUrl . 'fixtures/lineups', [
                'headers' => ['x-apisports-key' => $apiKey],
                'query' => ['fixture' => $fixtureId],
            ]);
            $lineups = json_decode($lineupResponse->getBody(), true)['response'] ?? [];

            // 2️⃣ Get Events
            $eventResponse = $client->get($baseUrl . 'fixtures/events', [
                'headers' => ['x-apisports-key' => $apiKey],
                'query' => ['fixture' => $fixtureId],
            ]);
            $events = json_decode($eventResponse->getBody(), true)['response'] ?? [];

            // 3️⃣ Get all ratings for this fixture + team
            $ratings = MatchRating::where('match_id', $fixtureId)
                ->where('team_id', $teamId)
                ->get();

            if ($ratings->isEmpty()) {
                return $this->error([], 'No ratings found for this fixture/team', 404);
            }

            // 4️⃣ Compute average rating per player/coach & MOM
            $ratingsByEntity = [];
            $momCountByPlayer = [];

            foreach ($ratings as $r) {
                $entityId = $r->entity_id;
                $entityType = $r->entity_type; // 'player' or 'coach'
                if (!isset($ratingsByEntity[$entityType][$entityId])) {
                    $ratingsByEntity[$entityType][$entityId] = [];
                    if ($entityType === 'player') {
                        $momCountByPlayer[$entityId] = 0;
                    }
                }
                $ratingsByEntity[$entityType][$entityId][] = $r->rating;

                if ($entityType === 'player' && $r->is_mom) {
                    $momCountByPlayer[$entityId]++;
                }
            }

            $averageRatings = [];
            foreach ($ratingsByEntity as $entityType => $entities) {
                foreach ($entities as $entityId => $allRatings) {
                    $averageRatings[$entityType][$entityId] = round(array_sum($allRatings) / count($allRatings), 1);
                }
            }

            // 5️⃣ Determine MOM player (most frequent)
            $momPlayerId = array_keys($momCountByPlayer, max($momCountByPlayer))[0] ?? null;
            $momPlayerName = null;

            // 6️⃣ Filter the lineup for this team
            $teamLineup = collect($lineups)->firstWhere('team.id', $teamId);

            $lineupWithRatings = null;
            if ($teamLineup) {
                $mapPlayer = function ($player) use ($averageRatings, $momPlayerId) {
                    $playerId = $player['player']['id'] ?? null;
                    return [
                        'id' => $playerId,
                        'name' => $player['player']['name'] ?? null,
                        'number' => $player['player']['number'] ?? null,
                        'position' => $player['player']['position'] ?? null,
                        'rating' => $averageRatings['player'][$playerId] ?? null,
                        'is_mom' => $playerId == $momPlayerId,
                    ];
                };

                $starters = collect($teamLineup['startXI'] ?? [])->map($mapPlayer);
                $bench = collect($teamLineup['substitutes'] ?? [])->map($mapPlayer);

                $momPlayerName = $starters->firstWhere('is_mom', true)['name']
                    ?? $bench->firstWhere('is_mom', true)['name']
                    ?? null;

                // Coach info inside lineup
                $coachData = $teamLineup['coach'] ?? [];
                $coach = [
                    'id' => $coachData['id'] ?? null,
                    'name' => $coachData['name'] ?? null,
                    'photo' => $coachData['photo'] ?? null,
                    'rating' => $averageRatings['coach'][$coachData['id']] ?? null,
                ];

                $lineupWithRatings = [
                    'team' => $teamLineup['team'],
                    'coach' => $coach,
                    'starters' => $starters,
                    'bench' => $bench,
                ];
            }

            // 7️⃣ Compute score from events
            $homeScore = 0;
            $awayScore = 0;
            foreach ($events as $event) {
                if ($event['type'] === 'Goal') {
                    if ($event['team']['id'] === ($lineups[0]['team']['id'] ?? 0)) {
                        $homeScore++;
                    } else {
                        $awayScore++;
                    }
                }
            }

            $response = [
                'fixture_id' => $fixtureId,
                'team_id' => $teamId,
                'home_team' => $lineups[0]['team']['name'] ?? null,
                'away_team' => $lineups[1]['team']['name'] ?? null,
                'home_score' => $homeScore,
                'away_score' => $awayScore,
                'mom_player_name' => $momPlayerName,
                'lineup' => $lineupWithRatings,
            ];

            return $this->success($response, 'Community average ratings including coach retrieved successfully');
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }


    // public function communityUserRating(Request $request)
    // {
    //     $fixtureId = $request->query('fixture_id');
    //     $teamId = $request->query('team_id'); // required now to aggregate per team

    //     if (!$fixtureId || !$teamId) {
    //         return $this->error([], 'Fixture ID and Team ID are required', 400);
    //     }

    //     try {
    //         $client = new \GuzzleHttp\Client();
    //         $baseUrl = config('services.api_football.base_url');
    //         $apiKey = config('services.api_football.api_key');

    //         // 1️⃣ Get Lineups
    //         $lineupResponse = $client->get($baseUrl . 'fixtures/lineups', [
    //             'headers' => ['x-apisports-key' => $apiKey],
    //             'query' => ['fixture' => $fixtureId],
    //         ]);
    //         $lineups = json_decode($lineupResponse->getBody(), true)['response'] ?? [];

    //         // 2️⃣ Get Events
    //         $eventResponse = $client->get($baseUrl . 'fixtures/events', [
    //             'headers' => ['x-apisports-key' => $apiKey],
    //             'query' => ['fixture' => $fixtureId],
    //         ]);
    //         $events = json_decode($eventResponse->getBody(), true)['response'] ?? [];

    //         // 3️⃣ Get all ratings for this fixture + team
    //         $ratings = MatchRating::where('match_id', $fixtureId)
    //             ->where('team_id', $teamId)
    //             ->get();

    //         if ($ratings->isEmpty()) {
    //             return $this->error([], 'No ratings found for this fixture/team', 404);
    //         }

    //         // 4️⃣ Compute average rating per player
    //         $ratingsByPlayer = [];
    //         $momCountByPlayer = [];

    //         foreach ($ratings as $r) {
    //             $playerId = $r->entity_id;
    //             if (!isset($ratingsByPlayer[$playerId])) {
    //                 $ratingsByPlayer[$playerId] = [];
    //                 $momCountByPlayer[$playerId] = 0;
    //             }
    //             $ratingsByPlayer[$playerId][] = $r->rating;
    //             if ($r->is_mom) {
    //                 $momCountByPlayer[$playerId]++;
    //             }
    //         }

    //         $averageRatings = [];
    //         foreach ($ratingsByPlayer as $playerId => $allRatings) {
    //             $averageRatings[$playerId] = round(array_sum($allRatings) / count($allRatings), 1);
    //         }

    //         // 5️⃣ Determine MOM player (most frequent)
    //         $momPlayerId = array_keys($momCountByPlayer, max($momCountByPlayer))[0] ?? null;
    //         $momPlayerName = null;

    //         // 6️⃣ Filter the lineup for this team
    //         $teamLineup = collect($lineups)->firstWhere('team.id', $teamId);

    //         $lineupWithRatings = null;
    //         if ($teamLineup) {
    //             $mapPlayer = function ($player) use ($averageRatings, $momPlayerId) {
    //                 $playerId = $player['player']['id'] ?? null;
    //                 return [
    //                     'id' => $playerId,
    //                     'name' => $player['player']['name'] ?? null,
    //                     'number' => $player['player']['number'] ?? null,
    //                     'position' => $player['player']['position'] ?? null,
    //                     'rating' => $averageRatings[$playerId] ?? null,
    //                     'is_mom' => $playerId == $momPlayerId,
    //                 ];
    //             };

    //             $starters = collect($teamLineup['startXI'] ?? [])->map($mapPlayer);
    //             $bench = collect($teamLineup['substitutes'] ?? [])->map($mapPlayer);

    //             $momPlayerName = $starters->firstWhere('is_mom', true)['name']
    //                 ?? $bench->firstWhere('is_mom', true)['name']
    //                 ?? null;

    //             $lineupWithRatings = [
    //                 'team' => $teamLineup['team'],
    //                 'starters' => $starters,
    //                 'bench' => $bench,
    //             ];
    //         }

    //         // 7️⃣ Compute score from events
    //         $homeScore = 0;
    //         $awayScore = 0;
    //         foreach ($events as $event) {
    //             if ($event['type'] === 'Goal') {
    //                 if ($event['team']['id'] === ($lineups[0]['team']['id'] ?? 0)) {
    //                     $homeScore++;
    //                 } else {
    //                     $awayScore++;
    //                 }
    //             }
    //         }

    //         $response = [
    //             'fixture_id' => $fixtureId,
    //             'team_id' => $teamId,
    //             'home_team' => $lineups[0]['team']['name'] ?? null,
    //             'away_team' => $lineups[1]['team']['name'] ?? null,
    //             'home_score' => $homeScore,
    //             'away_score' => $awayScore,
    //             'mom_player_name' => $momPlayerName,
    //             'lineup' => $lineupWithRatings,
    //         ];

    //         return $this->success($response, 'Community average ratings retrieved successfully');
    //     } catch (\Exception $e) {
    //         return $this->error([], $e->getMessage(), 500);
    //     }
    // }


}
