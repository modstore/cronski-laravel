<?php

namespace Modstore\Cronski\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modstore\Cronski\Cronski;
use Modstore\Cronski\Process;

class SendPendingRequestsCommand extends Command
{
    const MAX_ITEMS = 50;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronski:send-pending-requests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send the pending requests through to Cronski.';

    /**
     * Execute the console command.
     *
     * @param Cronski $cronski
     * @return int
     */
    public function handle(Cronski $cronski)
    {
        if (!config('cronski.project') || !config('cronski.scheduled')) {
            throw new \Exception('You must have your project uuid set and "scheduled" set to true in the cronski config to use this command.');
        }

        $total = Process::whereNull('status')->count();

        $this->line(sprintf('<info>%d</info> rows to process.', $total));

        if ($total === 0) {
            return 0;
        }

        // Process "start".
        Process::where('endpoint', Process::ENDPOINT_START)
            ->whereNull('status')
            ->orderBy('id')
            ->chunk(self::MAX_ITEMS, function (Collection $processes) use ($cronski) {
                $data = $cronski->request('POST', sprintf('process-multi/start'), [
                    'items' => $processes->map(function (Process $process) {
                        return array_merge($process->data, ['reference' => $process->id]);
                    })->toArray(),
                ]);

                $processes = $processes->keyBy('id');

                DB::transaction(function () use ($processes, $data) {
                    // Save the process_uuid against all the completed processes, so the "finish/fail" can get it.
                    foreach ($data as $newProcess) {
                        /** @var Process $process */
                        $process = $processes->get($newProcess['reference']);
                        $process->update([
                            'status' => Process::STATUS_COMPLETE,
                            'process_uuid' => $newProcess['uuid'],
                        ]);
                    }
                });

                $this->line(sprintf('Sent %d "start" batch', count($data)));
            });

        // Process "finish".
        Process::with('parentProcess')
            ->where('endpoint', Process::ENDPOINT_FINISH)
            ->whereNull('status')
            ->whereHas('parentProcess', function ($query) {
                return $query->whereNotNull('process_uuid');
            })
            ->orderBy('id')
            ->chunk(self::MAX_ITEMS, function (Collection $processes) use ($cronski) {
                $data = $cronski->request('POST', sprintf('process-multi/finish'), [
                    'items' => $processes->map(function (Process $process) {
                        return array_merge($process->data, [
                            'process_uuid' => $process->parentProcess->process_uuid,
                            'reference' => $process->id,
                        ]);
                    })->toArray(),
                ]);

                // Update the status for the completed processes.
                Process::whereIn('id', collect($data)->pluck('reference'))->update([
                    'status' => Process::STATUS_COMPLETE,
                ]);

                $this->line(sprintf('Sent %d "finish" batch', count($data)));
            });

        // Process "fail".
        Process::with('parentProcess')
            ->where('endpoint', Process::ENDPOINT_FAIL)
            ->whereNull('status')
            ->whereHas('parentProcess', function ($query) {
                return $query->whereNotNull('process_uuid');
            })
            ->orderBy('id')
            ->chunk(self::MAX_ITEMS, function (Collection $processes) use ($cronski) {
                $data = $cronski->request('POST', sprintf('process-multi/fail'), [
                    'items' => $processes->map(function (Process $process) {
                        return array_merge($process->data, [
                            'process_uuid' => $process->parentProcess->process_uuid,
                            'reference' => $process->id,
                        ]);
                    })->toArray(),
                ]);

                // Update the status for the completed processes.
                Process::whereIn('id', collect($data)->pluck('reference'))->update([
                    'status' => Process::STATUS_COMPLETE,
                ]);

                $this->line(sprintf('Sent %d "fail" batch', count($data)));
            });

        // Delete data older than 24 hours.
        Process::where('status', Process::STATUS_COMPLETE)
            ->where('created_at', '<', Carbon::now()->subDay())
            ->delete();

        return 0;
    }
}
