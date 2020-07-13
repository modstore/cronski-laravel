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

    protected static $processIds = [];

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

        $lookupKey = $this->getCommandKey((string) $event->input);

        // In case there's any error sending this through, report the exception but don't stop execution.
        try {
            self::$processIds[$lookupKey] = $this->cronski->start([
                'key' => $event->command,
                'type' => Cronski::TYPE_COMMAND,
                'start_data' => [
                    'input' => (string) $event->input,
                ],
            ]);
        } catch (\Exception $e) {
            report($e);

            return;
        }
    }

    public function handleCommandFinished(CommandFinished $event)
    {
        if (!$this->shouldHandle($event->command, config('cronski.commands'))) {
            return;
        }

        $lookupKey = $this->getCommandKey((string) $event->input);

        // In case there's any error sending this through, report the exception but don't stop execution.
        try {
            $this->cronski->finish(self::$processIds[$lookupKey]);
        } catch (\Exception $e) {
            report($e);

            return;
        }

        unset(self::$processIds[$lookupKey]);
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
        // Some commands that are always ignored.
        if (in_array($name, [
            'cronski:send-pending-requests',
            'config:clear',
            'migrate',
            'package:discover',
        ])) {
            return false;
        }

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
        $lookupKey = $this->getJobKey($resolvedJob);

        // In case there's any error sending this through, report the exception but don't stop execution.
        try {
            self::$processIds[$lookupKey] = $this->cronski->start([
                'key' => method_exists($resolvedJob, 'displayName')
                    ? $resolvedJob->displayName()
                    : $event->job->resolveName(),
                'type' => Cronski::TYPE_JOB,
                'start_data' => (array) $resolvedJob,
                'tags' => $resolvedJob->tags(),
            ]);
        } catch (\Exception $e) {
            report($e);

            return;
        }
    }

    public function handleJobProcessed(JobProcessed $event)
    {
        if (!$this->shouldHandle($event->job->resolveName(), config('cronski.jobs'))) {
            return;
        }

        $resolvedJob = unserialize($event->job->payload()['data']['command']);
        $lookupKey = $this->getJobKey($resolvedJob);

        // In case there's any error sending this through, report the exception but don't stop execution.
        try {
            $this->cronski->finish(self::$processIds[$lookupKey]);
        } catch (\Exception $e) {
            report($e);

            return;
        }

        unset(self::$processIds[$lookupKey]);
    }

    public function handleJobFailed(JobFailed $event)
    {
        if (!$this->shouldHandle($event->job->resolveName(), config('cronski.jobs'))) {
            return;
        }

        $resolvedJob = unserialize($event->job->payload()['data']['command']);
        $lookupKey = $this->getJobKey($resolvedJob);

        // In case there's any error sending this through, report the exception but don't stop execution.
        try {
            $this->cronski->fail(self::$processIds[$lookupKey], $event->exception->getMessage());
        } catch (\Exception $e) {
            report($e);

            return;
        }

        unset(self::$processIds[$lookupKey]);
    }

    /**
     * Get a key for storing a reference to this command instance locally.
     *
     * @param string $input
     * @return string
     */
    protected function getCommandKey(string $input)
    {
        return md5($input);
    }

    /**
     * Get a key for storing a reference to this job instance locally.
     *
     * @param $job
     * @return string
     */
    protected function getJobKey($job)
    {
        return md5(json_encode((array) $job));
    }
}
