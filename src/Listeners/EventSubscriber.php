<?php

namespace Modstore\Cronski\Listeners;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
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
        if ($this->cronski->isEnabled() && config('cronski.commands.enabled', true)) {
            $events->listen(
                CommandStarting::class,
                sprintf('%s@handleCommandStarting', self::class)
            );

            $events->listen(
                CommandFinished::class,
                sprintf('%s@handleCommandFinished', self::class)
            );;
        }

        // Add the listeners if Cronski is enabled, and enabled for use on jobs.
        if ($this->cronski->isEnabled() && config('cronski.jobs.enabled', true)) {
            $events->listen(
                JobProcessing::class,
                sprintf('%s@handleJobProcessing', self::class)
            );

            $events->listen(
                JobProcessed::class,
                sprintf('%s@handleJobProcessed', self::class)
            );

            $events->listen(
                JobFailed::class,
                sprintf('%s@handleJobFailed', self::class)
            );
        }
    }

    public function handleCommandStarting(CommandStarting $event)
    {
        if (!$this->shouldHandle($event->command, config('cronski.commands'))) {
            return;
        }

        self::$processUuid = $this->cronski->start([
            'key' => $event->command,
            'type' => Cronski::TYPE_COMMAND,
            'start_data' => [
                'input' => (string) $event->input,
            ],
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
     * @param string $name
     * @param array $config
     * @return bool
     */
    public function shouldHandle(string $name, array $config)
    {
        // No restriction.
        if (count(Arr::get($config, 'excluded', [])) === 0 && count(Arr::get($config, 'included', [])) === 0) {
            return true;
        }

        // Check if command is in the "included" array.
        if (count(Arr::get($config, 'included', [])) > 0) {
            $isMatch = collect($config['included'])->filter(function ($item) use ($name) {
                $pattern = sprintf('/%s/', str_replace('\\*', '.+', preg_quote($item)));

                return preg_match($pattern, $name) === 1;
            })->count() > 0;

            if ($isMatch) {
                return true;
            }
        }

        // By this point, either it's excluded or it's allowed.
        if (count(Arr::get($config, 'excluded', [])) > 0) {
            return collect($config['excluded'])->filter(function ($item) use ($name) {
                $pattern = sprintf('/%s/', str_replace('\\*', '.+', preg_quote($item)));

                return preg_match($pattern, $name) === 1;
            })->count() === 0;
        }

        return false;
    }

    public function handleJobProcessing(JobProcessing $event)
    {
        if (!$this->shouldHandle($event->job->resolveName(), config('cronski.jobs'))) {
            return;
        }

        $resolvedJob = unserialize($event->job->payload()['data']['command']);

        self::$processUuid = $this->cronski->start([
            'key' => $event->job->resolveName(),
            'type' => Cronski::TYPE_JOB,
            'start_data' => (array) $resolvedJob,
        ]);
    }

    public function handleJobProcessed(JobProcessed $event)
    {
        if (!$this->shouldHandle($event->job->resolveName(), config('cronski.jobs'))) {
            return;
        }

        $this->cronski->finish(self::$processUuid);
    }

    public function handleJobFailed(JobFailed $event)
    {
        if (!$this->shouldHandle($event->job->resolveName(), config('cronski.jobs'))) {
            return;
        }

        $this->cronski->fail(self::$processUuid, $event->exception->getMessage());
    }
}
