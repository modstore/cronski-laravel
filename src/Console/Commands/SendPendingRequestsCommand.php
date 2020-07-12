<?php

namespace Modstore\Cronski\Console\Commands;

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
        if (!config('cronski.scheduled')) {
            $this->error('You must set "scheduled" to true in the cronski config to use this command.');

            return 1;
        }

        $total = Process::count();

        $this->line(sprintf('<info>%d</info> rows to process.', $total));

        if ($total === 0) {
            return 0;
        }

        // Process "start".
        Process::where('endpoint', Process::ENDPOINT_START)
            ->orderBy('id')
            ->chunk(self::MAX_ITEMS, function (Collection $processes) use ($cronski) {
                $data = $cronski->request('POST', sprintf('process-multi/start'), [
                    'items' => $processes->map(function (Process $process) {
                        return array_merge($process->data, ['reference' => $process->id]);
                    })->toArray(),
                ]);

                DB::transaction(function () use ($data) {
                    foreach ($data as $newProcess) {
                        // Set the new uuid against any processes that have this process referenced as a parent_id.
                        Process::where('parent_id', $newProcess['reference'])
                            ->update(['process_uuid' => $newProcess['uuid']]);
                    }

                    // Delete the successful rows.
                    Process::whereIn('id', collect($data)->pluck('reference'))->delete();
                });
            });

        // Process "finish".
        Process::where('endpoint', Process::ENDPOINT_FINISH)
            ->orderBy('id')
            ->chunk(self::MAX_ITEMS, function (Collection $processes) use ($cronski) {
                $data = $cronski->request('POST', sprintf('process-multi/finish'), [
                    'items' => $processes->map(function (Process $process) {
                        return array_merge($process->data, [
                            'process_uuid' => $process->process_uuid,
                            'reference' => $process->id,
                        ]);
                    })->toArray(),
                ]);

                // Delete the successful rows.
                Process::whereIn('id', collect($data)->pluck('reference'))->delete();
            });

        // Process "fail".
        Process::where('endpoint', Process::ENDPOINT_FAIL)
            ->orderBy('id')
            ->chunk(self::MAX_ITEMS, function (Collection $processes) use ($cronski) {
                $data = $cronski->request('POST', sprintf('process-multi/fail'), [
                    'items' => $processes->map(function (Process $process) {
                        return array_merge($process->data, [
                            'process_uuid' => $process->process_uuid,
                            'reference' => $process->id,
                        ]);
                    })->toArray(),
                ]);

                // Delete the successful rows.
                Process::whereIn('id', collect($data)->pluck('reference'))->delete();
            });

        return 0;
    }
}
