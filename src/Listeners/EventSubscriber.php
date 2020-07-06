<?php

namespace Modstore\Cronski\Listeners;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Modstore\Cronski\Cronski;

class EventSubscriber
{
    protected $cronski;

    protected static $processUuid;

    public function __construct(Cronski $cronski)
    {
        $this->cronski = $cronski;
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            CommandStarting::class,
            sprintf('%s@handleCommandStarting', self::class)
        );

        $events->listen(
            CommandFinished::class,
            sprintf('%s@handleCommandFinished', self::class)
        );
    }

    /**
     * Handle user login events.
     */
    public function handleCommandStarting(CommandStarting $event)
    {
        self::$processUuid = $this->cronski->start([
            'key' => $event->command,
        ]);
    }

    /**
     * Handle user logout events.
     */
    public function handleCommandFinished(CommandFinished $event)
    {
        $this->cronski->finish(self::$processUuid);
    }
}
