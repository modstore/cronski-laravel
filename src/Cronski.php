<?php

namespace Modstore\Cronski;

use Carbon\Carbon;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Arr;

class Cronski
{
    const TYPE_COMMAND = 0;
    const TYPE_JOB = 1;

    protected $client;

    protected $projectUuid;

    protected $token;

    protected $scheduled;

    public function __construct(ClientInterface $client, $projectUuid, $token, bool $scheduled = false)
    {
        $this->client = $client;
        $this->projectUuid = $projectUuid;
        $this->token = $token;
        $this->scheduled = $scheduled;
    }

    /**
     * Whether Cronski is enabled.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->projectUuid !== null;
    }

    /**
     * The api endpoint base path for the configured project.
     *
     * @return string
     */
    protected function getBasePath()
    {
        return sprintf('api/project/%s/', $this->projectUuid);
    }

    /**
     * @param array $data
     * @return string|int - Either the UUID of the new process, or the id of the local row if scheduled.
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function start($data = [])
    {
        $data = array_replace_recursive([
            // Send through the start time so there's no delay due to request time.
            'started_at' => Carbon::now()->toIso8601ZuluString(),
        ], $data);

        // If scheduled, create the record locally only, don't send a request now.
        if ($this->scheduled) {
            $process = Process::create([
                'endpoint' => Process::ENDPOINT_START,
                'data' => $data,
            ]);

            return $process->id;
        }

        return Arr::get($this->request('POST', 'process/start', $data), 'uuid');
    }

    /**
     * @param $processId - Process UUID if not scheduled, or local process id if scheduled.
     * @param array $data
     * @return string|int - Either the UUID of the process, or the id of the local row if scheduled.
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function finish($processId, $data = [])
    {
        $data = array_replace_recursive([
            // Send through the start time so there's no delay due to request time.
            'finished_at' => Carbon::now()->toIso8601ZuluString(),
            // Send through the memory usage in mb.
            'memory' => memory_get_usage(true) / 1024 / 1024,
        ], $data);

        // If scheduled, create the record locally only, don't send a request now.
        if ($this->scheduled) {
            $process = Process::create([
                'endpoint' => Process::ENDPOINT_FINISH,
                'data' => $data,
                'parent_id' => $processId,
            ]);

            return $process->id;
        }

        return Arr::get($this->request('POST', sprintf('process/%s/finish', $processId), $data), 'uuid');
    }

    /**
     * @param $processId - Process UUID if not scheduled, or local process id if scheduled.
     * @param null $message
     * @param array $data
     * @return string|int - Either the UUID of the new process, or the id of the local row if scheduled.
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function fail($processId, $message = null, $data = [])
    {
        $data = array_replace_recursive([
            // Send through the start time so there's no delay due to request time.
            'finished_at' => Carbon::now()->toIso8601ZuluString(),
            // Send through the memory usage in mb.
            'memory' => memory_get_usage(true) / 1024 / 1024,
            'failed_message' => $message,
        ], $data);

        // If scheduled, create the record locally only, don't send a request now.
        if ($this->scheduled) {
            $process = Process::create([
                'endpoint' => Process::ENDPOINT_FAIL,
                'data' => $data,
                'parent_id' => $processId,
            ]);

            return $process->id;
        }

        return Arr::get($this->request('POST', sprintf('process/%s/fail', $processId), $data), 'uuid');
    }

    /**
     * @param string $method
     * @param string $path
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request(string $method, string $path, array $data)
    {
        $response = $this->client->request($method, $this->getBasePath() . $path, [
            'headers' => [
                'Authorization' => sprintf('Bearer %s', $this->token),
                'Accept' => 'application/json',
            ],
            'json' => $data,
        ]);

        return json_decode($response->getBody()->getContents(), true)['data'];
    }
}
