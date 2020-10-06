<?php

namespace Modstore\Cronski\Tests\Console\Commands;

use Config;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Modstore\Cronski\Cronski;
use Modstore\Cronski\Process;
use Modstore\Cronski\Tests\TestCase;
use Ramsey\Uuid\Uuid;

class SendPendingRequestsCommandTest extends TestCase
{
    public function testProcessPending()
    {
        Config::set('cronski.project', 'test');

        $startProcess = Process::create([
            'endpoint' => Process::ENDPOINT_START,
            'data' => [
                'key' => 'command:one',
            ],
        ]);

        $finishProcess = Process::create([
            'endpoint' => Process::ENDPOINT_FINISH,
            'parent_id' => $startProcess->id,
            'data' => [],
        ]);

        $failProcess = Process::create([
            'endpoint' => Process::ENDPOINT_FAIL,
            'parent_id' => $startProcess->id,
            'data' => [],
        ]);

        $projectUuid = (string) Uuid::uuid4();
        $token = Str::random();
        $client = $this->mock(Client::class);
        $client->shouldReceive('request')->times(3)
            ->andReturnUsing(function ($method, $url, $data) use ($failProcess, $finishProcess, $startProcess) {
                preg_match('/\/([a-z]+)$/', $url, $matches);

                switch ($matches[1]) {
                    case Process::ENDPOINT_START:
                        $process = $startProcess;

                        break;
                    case Process::ENDPOINT_FINISH:
                        $process = $finishProcess;

                        break;
                    default:
                        $process = $failProcess;

                        break;
                }

                return new Response(200, ['Content-Type' => 'application/json'], json_encode([
                    // An array of created processes.
                    'data' => [
                        [
                            'uuid' => (string) Uuid::uuid4(),
                            // Our local reference id that was sent through so new process can be matched up.
                            'reference' => $process->id,
                        ],
                    ],
                ]));
            });

        $this->app->instance(Cronski::class, new Cronski($client, $projectUuid, $token, true));

        Artisan::call('cronski:send-pending-requests');

        // Ensure all rows are the correct status now they've been processed.
        $this->assertSame(3, Process::where('status', Process::STATUS_COMPLETE)->count());
    }
}
