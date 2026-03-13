<?php

namespace App\Scrapers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class ArgenpropScraper
{
    private const BASE_URL   = 'https://www.argenprop.com';
    private const SEARCH_URL = 'https://www.argenprop.com/casas/venta/ciudad/bahia-blanca/ambientes-4-o-mas/precio-hasta-250000-usd/pagina-%d';
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
                Log::warning("Argenprop: page {$page} failed", ['status' => $response->status()]);
                break;
            }

            $crawler  = new Crawler($response->body());
            $listings = $crawler->filter('.listing__item');

            if ($listings->count() === 0) {
                // try generic card selector
                $listings = $crawler->filter('[class*="card-"]');
            }

            if ($listings->count() === 0) {
                break;
            }

            $listings->each(function (Crawler $node) use (&$properties) {
                $property = $this->parseCard($node);
                if ($property) {
                    $properties[] = $property;
                }
            });

            usleep(500000);
        }

        return $properties;
    }

    private function parseCard(Crawler $node): ?array
    {
        try {
            $anchor = $node->filter('a')->first();
            $href   = $anchor->count() ? $anchor->attr('href') : null;
            if (! $href) return null;

            $url = str_starts_with($href, 'http') ? $href : self::BASE_URL . $href;

            // external_id from URL slug
            preg_match('/-(\d+)(?:\.html)?$/', $url, $idMatch);
            $externalId = $idMatch[1] ?? md5($url);

            $title = $this->text($node, 'h2, h3, .card__title, [class*="title"]') ?? 'Sin título';

            $priceRaw = $this->text($node, '.card__price, [class*="price"]');
            $priceUsd = $this->extractUsd($priceRaw);

            $rooms = null;
            $node->filter('.card__detail-item, [class*="feature"], [class*="detail"]')->each(function (Crawler $item) use (&$rooms) {
                $text = $item->text('');
                if (stripos($text, 'dorm') !== false || stripos($text, 'amb') !== false) {
                    preg_match('/\d+/', $text, $m);
                    if (isset($m[0])) $rooms = (int) $m[0];
                }
            });

            $area = null;
            $node->filter('.card__detail-item, [class*="feature"]')->each(function (Crawler $item) use (&$area) {
                $text = $item->text('');
                if (stripos($text, 'm²') !== false || stripos($text, 'm2') !== false) {
                    preg_match('/[\d.,]+/', $text, $m);
                    if (isset($m[0])) $area = (float) str_replace(',', '.', $m[0]);
                }
            });

            $description  = $this->text($node, '.card__info, [class*="description"]');
            $neighborhood = $this->text($node, '.card__address, [class*="location"], [class*="address"]');

            $image = null;
            $img   = $node->filter('img')->first();
            if ($img->count()) {
                $image = $img->attr('data-src') ?? $img->attr('src');
            }

            return [
                'source'       => 'argenprop',
                'external_id'  => $externalId,
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
            Log::debug('Argenprop: parse error', ['error' => $e->getMessage()]);
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
        preg_match('/[\d.,]+/', str_replace('.', '', $raw ?? ''), $m);
        return isset($m[0]) ? (float) str_replace(',', '.', $m[0]) : null;
    }
}
