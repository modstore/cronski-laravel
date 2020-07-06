<?php

namespace Modstore\Cronski\Listeners;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Arr;
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
        // Add the listeners if Cronski is enabled, and enabled for use on commands.
        if (!$this->cronski->isEnabled() && config('cronski.commands.enabled', true)) {
            return;
        }

        $events->listen(
            CommandStarting::class,
            sprintf('%s@handleCommandStarting', self::class)
        );

        $events->listen(
            CommandFinished::class,
            sprintf('%s@handleCommandFinished', self::class)
        );
    }

    public function handleCommandStarting(CommandStarting $event)
    {
        if (!$this->shouldHandle($event->command, config('cronski.commands'))) {
            return;
        }

        self::$processUuid = $this->cronski->start([
            'key' => $event->command,
        ]);
    }

    public function handleCommandFinished(CommandFinished $event)
    {
        if (!$this->shouldHandle($event->command, config('cronski.commands'))) {
            return;
        }

        $this->cronski->finish(self::$processUuid);
    }

    /**
     * Whether this particular command should be handled by Cronski.
     *
     * @param string $command
     * @param array $config
     * @return bool
     */
    public function shouldHandle(string $command, array $config)
    {
        // No restriction.
        if (count(Arr::get($config, 'excluded', [])) === 0 && count(Arr::get($config, 'included', [])) === 0) {
            return true;
        }

        // Check if command is in the "included" array.
        if (count(Arr::get($config, 'included', [])) > 0) {
            $isMatch = collect($config['included'])->filter(function ($item) use ($command) {
                return preg_match(sprintf('/%s/', strtr($item, '*', '.+')), $command) === 1;
            })->count() > 0;

            if ($isMatch) {
                return true;
            }
        }

        // By this point, either it's excluded or it's allowed.
        if (count(Arr::get($config, 'excluded', [])) > 0) {
            return collect($config['excluded'])->filter(function ($item) use ($command) {
                return preg_match(sprintf('/%s/', strtr($item, '*', '.+')), $command) === 1;
            })->count() === 0;
        }

        return false;
    }
}
