<?php

namespace App\Scrapers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class ZonapropScraper
{
    private const BASE_URL   = 'https://www.zonaprop.com.ar';
    private const SEARCH_URL = 'https://www.zonaprop.com.ar/casas-venta-bahia-blanca-mas-de-3-dormitorios-hasta-250000-dolar-pagina-%d.html';
    private const MAX_PAGES  = 5;

    public function fetch(): array
    {
        $properties = [];

        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $url      = sprintf(self::SEARCH_URL, $page);
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; HouseScraper/1.0)'])
                ->get($url);

            if (! $response->ok()) {
                Log::warning("Zonaprop: page {$page} failed", ['status' => $response->status()]);
                break;
            }

            $crawler  = new Crawler($response->body());
            $listings = $crawler->filter('[data-id]');

            if ($listings->count() === 0) {
                break; // no more results
            }

            $listings->each(function (Crawler $node) use (&$properties) {
                $property = $this->parseCard($node);
                if ($property) {
                    $properties[] = $property;
                }
            });

            // polite delay between pages
            usleep(500000);
        }

        return $properties;
    }

    private function parseCard(Crawler $node): ?array
    {
        try {
            $anchor  = $node->filter('a')->first();
            $href    = $anchor->count() ? $anchor->attr('href') : null;
            if (! $href) return null;

            $url = str_starts_with($href, 'http') ? $href : self::BASE_URL . $href;

            $title = $this->text($node, 'h2, h3, [class*="title"]');
            if (! $title) {
                $title = $node->attr('data-title') ?? 'Sin título';
            }

            $priceRaw = $this->text($node, '[class*="price"], [class*="Price"]');
            $priceUsd = $this->extractUsd($priceRaw);

            $rooms = $this->extractNumber($this->text($node, '[class*="room"], [class*="Room"]'));
            $area  = $this->extractNumber($this->text($node, '[class*="surface"], [class*="Surface"], [class*="area"]'));

            $description = $this->text($node, '[class*="description"], [class*="Description"]');

            $image = null;
            $img   = $node->filter('img')->first();
            if ($img->count()) {
                $image = $img->attr('data-src') ?? $img->attr('src');
            }

            $neighborhood = $this->text($node, '[class*="location"], [class*="Location"], [class*="address"]');

            return [
                'source'       => 'zonaprop',
                'external_id'  => $node->attr('data-id'),
                'url'          => $url,
                'title'        => $title,
                'price_usd'    => $priceUsd,
                'price_raw'    => $priceRaw,
                'bedrooms'     => $rooms,
                'bathrooms'    => null,
                'area_m2'      => $area,
                'neighborhood' => $neighborhood,
                'description'  => $description,
                'image_url'    => $image,
                'published_at' => null,
            ];
        } catch (\Throwable $e) {
            Log::debug('Zonaprop: parse error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function text(Crawler $node, string $selector): ?string
    {
        try {
            $found = $node->filter($selector)->first();
            return $found->count() ? trim($found->text('')) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function extractUsd(?string $raw): ?float
    {
        if (! $raw) return null;
        if (stripos($raw, 'USD') !== false || str_contains($raw, 'U$S') || str_contains($raw, '$')) {
            preg_match('/[\d.,]+/', str_replace('.', '', $raw), $m);
            return isset($m[0]) ? (float) str_replace(',', '.', $m[0]) : null;
        }
        return null;
    }

    private function extractNumber(?string $text): ?int
    {
        if (! $text) return null;
        preg_match('/\d+/', $text, $m);
        return isset($m[0]) ? (int) $m[0] : null;
    }
}
