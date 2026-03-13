<?php

namespace App\Console\Commands;

use App\Models\Property;
use App\Scrapers\ArgenpropScraper;
use App\Scrapers\BahiaBlancaPropScraper;
use App\Scrapers\MercadoLibreScraper;
use App\Scrapers\ZonapropScraper;
use Illuminate\Console\Command;

class ScrapeProperties extends Command
{
    protected $signature = 'scrape:run {--source= : Run only one source (mercadolibre|zonaprop|argenprop|bahiablancaprop)}';
    protected $description = 'Scrape property listings from all sources and save new ones to the database';

    public function handle(): int
    {
        $onlySource = $this->option('source');

        $scrapers = [
            'mercadolibre'    => MercadoLibreScraper::class,
            'zonaprop'        => ZonapropScraper::class,
            'argenprop'       => ArgenpropScraper::class,
            'bahiablancaprop' => BahiaBlancaPropScraper::class,
        ];

        $totalNew = 0;

        foreach ($scrapers as $name => $class) {
            if ($onlySource && $onlySource !== $name) continue;

            $this->info("Scraping {$name}...");

            try {
                $scraper    = new $class();
                $properties = $scraper->fetch();
                $newCount   = 0;

                $fetchedUrls = collect($properties)->pluck('url')->filter()->flip()->all();

                foreach ($properties as $data) {
                    if (empty($data['url'])) continue;

                    $existing = Property::where('url', $data['url'])->first();

                    if (! $existing) {
                        // New property — record first_seen_at and clear removed_at
                        $data['first_seen_at'] = now();
                        $data['removed_at']    = null;
                        Property::create($data);
                        $newCount++;
                    } elseif ($existing->removed_at) {
                        // Was marked removed but is back — unmark it
                        $existing->update(['removed_at' => null]);
                    }
                }

                // Mark as removed any DB properties from this source not seen in this scrape run
                Property::where('source', $name)
                    ->whereNull('removed_at')
                    ->whereNotIn('url', array_keys($fetchedUrls))
                    ->update(['removed_at' => now()]);

                $this->line("  → {$newCount} new out of " . count($properties) . " fetched");
                $totalNew += $newCount;
            } catch (\Throwable $e) {
                $this->error("  → Failed: " . $e->getMessage());
            }
        }

        $this->info("\nDone. {$totalNew} new properties saved.");

        // Generate the HTML report
        $this->call('report:generate');

        return Command::SUCCESS;
    }
}
