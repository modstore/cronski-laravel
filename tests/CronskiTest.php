<?php

namespace Modstore\Cronski\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Str;
use Modstore\Cronski\Cronski;
use Modstore\Cronski\Process;
use Ramsey\Uuid\Uuid;

class CronskiTest extends TestCase
{
    public function testStartScheduled()
    {
        $projectUuid = (string) Uuid::uuid4();
        $token = Str::random();
        $client = $this->mock(Client::class);
        $client->shouldNotReceive('request');

        $cronski = new Cronski($client, $projectUuid, $token, true);

        $result = $cronski->start();

        $this->assertIsNumeric($result);

        // Confirm that a row was stored in the db.
        $this->assertSame(1, Process::count());
    }

    public function testStartNotScheduled()
    {
        $newProcessUuid = (string) Uuid::uuid4();

        $projectUuid = (string) Uuid::uuid4();
        $token = Str::random();
        $client = $this->mock(Client::class);
        $client->shouldReceive('request')->once()
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'data' => [
                    'uuid' => $newProcessUuid,
                ],
            ])));

        $cronski = new Cronski($client, $projectUuid, $token, false);

        $result = $cronski->start();

        $this->assertSame($newProcessUuid, $result);

        $this->assertSame(0, Process::count());
    }
}
