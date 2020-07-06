<?php

namespace Modstore\Cronski;

use Carbon\Carbon;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Arr;

class Cronski
{
    protected $client;

    protected $projectUuid;

    protected $token;

    public function __construct(ClientInterface $client, $projectUuid, $token)
    {
        $this->client = $client;
        $this->projectUuid = $projectUuid;
        $this->token = $token;
    }

    public function start($data = [])
    {
        $data = array_replace_recursive([
            // Send through the start time so there's no delay due to request time.
            'started_at' => Carbon::now()->toIso8601ZuluString(),
        ], $data);

        $response = $this->request(
            'POST',
            sprintf('api/project/%s/process/start', $this->projectUuid),
            $data
        );

        return Arr::get(json_decode($response->getBody()->getContents(), true), 'data.uuid');
    }

    public function finish($processUuid, $data = [])
    {
        $data = array_replace_recursive([
            // Send through the start time so there's no delay due to request time.
            'finished_at' => Carbon::now()->toIso8601ZuluString(),
        ], $data);

        $response = $this->request(
            'POST',
            sprintf('api/project/%s/process/%s/finish', $this->projectUuid, $processUuid),
            $data
        );

        return Arr::get(json_decode($response->getBody()->getContents(), true), 'data.uuid');
    }

    public function fail($data = [])
    {
        $data = array_replace_recursive([
            // Send through the start time so there's no delay due to request time.
            'finished_at' => Carbon::now()->toIso8601ZuluString(),
        ], $data);

        return $this->request('POST', sprintf('api/project/%s/process/fail', $this->projectUuid), $data);
    }

    protected function request(string $method, string $url, array $data)
    {
        return $this->client->request($method, $url, [
            'headers' => [
                'Authorization' => sprintf('Bearer %s', $this->token),
                'Accept' => 'application/json',
            ],
            'json' => $data,
        ]);
    }
}
