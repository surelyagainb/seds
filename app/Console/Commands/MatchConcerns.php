<?php

namespace App\Console\Commands;

use App\Models\Concern;
use App\Services\OrdinanceMatcher;
use Illuminate\Console\Command;

/** Command that runs the rule-based ordinance matching on submitted concerns. */
class MatchConcerns extends Command
{
    protected $signature = 'concerns:match {--all : Run again for every concern that is not reviewed yet}';

    protected $description = 'Run the rule-based ordinance matching on submitted concerns';

    /**
     * By default, only check concerns that have never been analyzed.
     * With --all, re-check every concern that staff have not reviewed yet.
     */
    public function handle(OrdinanceMatcher $matcher): int
    {
        // Never touch concerns that staff have already reviewed
        $query = Concern::whereNull('reviewed_at');

        if (! $this->option('all')) {
            $query->whereNull('analyzed_at');
        }

        $checkedCount = 0;
        $matchedCount = 0;

        // cursor() streams one concern at a time to keep memory usage low
        foreach ($query->cursor() as $concern) {
            $checkedCount++;

            $suggestionCount = $matcher->matchAndSave($concern);
            if ($suggestionCount > 0) {
                $matchedCount++;
            }
        }

        $this->info("Checked {$checkedCount} concern(s). {$matchedCount} had at least one suggested ordinance.");

        return self::SUCCESS;
    }
}