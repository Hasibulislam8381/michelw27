<?php

// app/Console/Commands/FetchAndStoreTeams.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Models\Team;

class FetchAndStoreTeams extends Command
{
    protected $signature = 'teams:fetch-and-store';
    protected $description = 'Fetch teams from API and store them in the database';

    public function handle()
    {
        $httpRequest = new Client();
        $teamIds = range(1, 32); // or whatever the range is
        $apiKey = config('services.api_football.api_key');

        foreach ($teamIds as $teamId) {
            $response = $httpRequest->get(config('services.api_football.base_url') . 'teams', [
                'headers' => ['x-apisports-key' => $apiKey],
                'query' => ['id' => $teamId],
            ]);

            $data = json_decode($response->getBody(), true);
            if (!empty($data['response'])) {
                $teamData = $data['response'][0];
                Team::updateOrCreate(
                    ['team_id' => $teamData['team']['id']], // Unique identifier (team_id)
                    [
                        'name' => $teamData['team']['name'],
                        'is_national' => $teamData['team']['national'] ?? false,
                        'country' => $teamData['team']['country'],
                    ]
                );
            }
        }

        $this->info('Teams have been successfully fetched and stored.');
    }
}
