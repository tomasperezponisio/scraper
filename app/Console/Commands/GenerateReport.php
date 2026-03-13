<?php

namespace App\Console\Commands;

use App\Models\Property;
use Illuminate\Console\Command;

class GenerateReport extends Command
{
    protected $signature   = 'report:generate';
    protected $description = 'Generate the HTML report of all scraped properties';

    public function handle(): int
    {
        $properties = Property::whereNull('removed_at')
            ->orderByDesc('first_seen_at')
            ->get();

        $newToday = Property::whereNull('removed_at')
            ->whereDate('first_seen_at', today())
            ->orderByDesc('first_seen_at')
            ->get();

        $removed = Property::whereNotNull('removed_at')
            ->orderByDesc('removed_at')
            ->get();

        $html = view('report', [
            'properties'  => $properties,
            'newToday'    => $newToday,
            'removed'     => $removed,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ])->render();

        $outputPath = public_path('report.html');
        file_put_contents($outputPath, $html);

        // Also copy to docs/ for GitHub Pages
        $docsPath = base_path('docs/index.html');
        if (is_dir(dirname($docsPath))) {
            file_put_contents($docsPath, $html);
        }

        $this->info("Report generated at: {$outputPath}");

        return Command::SUCCESS;
    }
}
