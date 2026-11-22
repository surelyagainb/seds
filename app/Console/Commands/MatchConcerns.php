<?php

namespace App\Console\Commands;

use App\Models\Concern;
use App\Services\OrdinanceMatcher;
use Illuminate\Console\Command;

/** Command that runs the ordinance matching on submitted concerns. */
class MatchConcerns extends Command
{
    protected $signature = 'concerns:match {--all : Run again for every concern that is not reviewed yet}';

    protected $description = 'Run the rule-based ordinance matching on submitted concerns';

    /** By default only concerns that were never analyzed; with --all, every concern not yet reviewed. */
    public function handle(OrdinanceMatcher $matcher): int
    {
        $query = Concern::whereNull('reviewed_at');

        if (! $this->option('all')) $query->whereNull('analyzed_at');

        $done = 0;
        $withMatches = 0;

        foreach ($query->cursor() as $concern) {
            $done++;
            if ($matcher->matchAndSave($concern) > 0) $withMatches++;
        }

        $this->info("Checked {$done} concern(s). {$withMatches} had at least one suggested ordinance.");

        return self::SUCCESS;
    }
}
