<?php

namespace Modules\Automation\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Automation\Entities\JourneyExecution;
use Modules\Automation\Services\JourneyEngine;

class RunJourneyExecution implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 50;

    public function __construct(public int $executionId)
    {
    }

    public function handle(JourneyEngine $engine): void
    {
        $execution = JourneyExecution::find($this->executionId);
        if (! $execution) {
            return;
        }

        $engine->processExecution($execution);
    }
}
