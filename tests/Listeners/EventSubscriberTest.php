<?php

namespace Modstore\Cronski\Tests\Listeners;

use Modstore\Cronski\Listeners\EventSubscriber;
use Modstore\Cronski\Tests\TestCase;

class EventSubscriberTest extends TestCase
{
    public function shouldHandleDataProvider()
    {
        return [
            'Included job, wildcard match' => [
                'config' => [ // The "jobs" config.
                    'excluded' => [],
                    'included' => [
                        'App\Jobs\*',
                    ],
                ],
                'command' => 'App\Jobs\MyJob',
                'expectedResult' => true,
            ],
            'Included job, exact match' => [
                'config' => [ // The "jobs" config.
                    'excluded' => [],
                    'included' => [
                        'App\Jobs\MyJob',
                    ],
                ],
                'command' => 'App\Jobs\MyJob',
                'expectedResult' => true,
            ],
            'Included, wildcard no match' => [
                'config' => [ // The "commands" config.
                    'excluded' => [],
                    'included' => [
                        'app:*',
                    ],
                ],
                'command' => 'nope:something',
                'expectedResult' => false,
            ],
            'Included, wildcard match' => [
                'config' => [ // The "commands" config.
                    'excluded' => [],
                    'included' => [
                        'app:*',
                    ],
                ],
                'command' => 'app:something',
                'expectedResult' => true,
            ],
            'Included, no exact match' => [
                'config' => [ // The "commands" config.
                    'excluded' => [],
                    'included' => [
                        'app:something',
                    ],
                ],
                'command' => 'app:something-else',
                'expectedResult' => true,
            ],
            'Included, exact match' => [
                'config' => [ // The "commands" config.
                    'excluded' => [],
                    'included' => [
                        'app:something',
                    ],
                ],
                'command' => 'app:something',
                'expectedResult' => true,
            ],

            'Excluded, wildcard no match' => [
                'config' => [ // The "commands" config.
                    'excluded' => [
                        'app:*',
                    ],
                    'included' => [],
                ],
                'command' => 'nope:something',
                'expectedResult' => true,
            ],
            'Excluded, wildcard match' => [
                'config' => [ // The "commands" config.
                    'excluded' => [
                        'app:*',
                    ],
                    'included' => [],
                ],
                'command' => 'app:something',
                'expectedResult' => false,
            ],
            'Excluded, no exact match' => [
                'config' => [ // The "commands" config.
                    'excluded' => [
                        'app:something',
                    ],
                    'included' => [],
                ],
                'command' => 'app:something-else',
                'expectedResult' => false,
            ],
            'Excluded, exact match' => [
                'config' => [ // The "commands" config.
                    'excluded' => [
                        'app:something',
                    ],
                    'included' => [],
                ],
                'command' => 'app:something',
                'expectedResult' => false,
            ],

            'No restriction' => [
                'config' => [ // The "commands" config.
                    'excluded' => [],
                    'included' => [],
                ],
                'command' => 'app:something',
                'expectedResult' => true,
            ],
        ];
    }

    /**
     * @dataProvider shouldHandleDataProvider
     * @param $config
     * @param $command
     * @param $expectedResult
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testShouldHandle($config, $command, $expectedResult)
    {
        $subscriber = app()->make(EventSubscriber::class);

        $result = $subscriber->shouldHandle($command, $config);

        $this->assertSame($expectedResult, $result);
    }
}
