<?php

namespace App\Scrapers;

use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class BahiaBlancaPropScraper
{
    private const BASE_URL    = 'https://www.bahiablancapropiedades.com';
    private const SITEMAP_URL = 'https://www.bahiablancapropiedades.com/sitemap.xml';
    private const SOURCE      = 'bahiablancaprop';
    private const PRICE_MIN   = 120000;
    private const PRICE_MAX   = 250000;

    /** @var array All Casa-en-Venta Macrocentro URLs found in sitemap this run */
    public array $sitemapUrls = [];

    public function fetch(): array
    {
        $properties = [];

        [$newCandidates, $allSitemapUrls] = $this->fetchCandidateUrls();
        $this->sitemapUrls = $allSitemapUrls;

        foreach ($newCandidates as $candidate) {
            $result = $this->fetchProperty($candidate['url'], $candidate['external_id'], $candidate['lastmod']);
            if ($result) {
                $properties[] = $result;
            }
            usleep(rand(300_000, 500_000));
        }

        return $properties;
    }

    private function fetchCandidateUrls(): array
    {
        $cacheFile = storage_path('app/bahiablancaprop-sitemap.xml');

        // Reuse cached sitemap if less than 24 hours old
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 86400) {
            Log::info('BahiaBlancaProp: using cached sitemap');
            $body = file_get_contents($cacheFile);
        } else {
            Log::info('BahiaBlancaProp: downloading sitemap...');
            // Stream to file — sitemap is ~5MB and slow from AR servers
            $ch = curl_init(self::SITEMAP_URL);
            $fp = fopen($cacheFile, 'w');
            curl_setopt_array($ch, [
                CURLOPT_FILE           => $fp,
                CURLOPT_TIMEOUT        => 300,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; HouseScraper/1.0)',
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error  = curl_error($ch);
            curl_close($ch);
            fclose($fp);

            if ($error || $status < 200 || $status >= 300) {
                Log::warning('BahiaBlancaProp: sitemap fetch failed', ['status' => $status, 'error' => $error]);
                @unlink($cacheFile);
                return [];
            }
            $body = file_get_contents($cacheFile);
        }

        // Parse sitemap with simple regex on raw XML to avoid multibyte delimiter issues
        $candidates = [];

        // Extract all <url> blocks
        preg_match_all('/<url>(.*?)<\/url>/s', $body, $urlBlocks);

        foreach ($urlBlocks[1] as $block) {
            preg_match('/<loc>(.*?)<\/loc>/s', $block, $locMatch);
            preg_match('/<lastmod>(.*?)<\/lastmod>/s', $block, $lastmodMatch);

            $loc     = trim($locMatch[1] ?? '');
            $lastmod = trim($lastmodMatch[1] ?? '') ?: null;

            if (! $loc) continue;

            // Extract path segments without regex delimiter conflicts
            $path     = parse_url($loc, PHP_URL_PATH) ?? '';
            $segments = explode('/', trim($path, '/'));

            // Must be: propiedad / {id} / {slug}
            if (count($segments) < 3 || $segments[0] !== 'propiedad') continue;
            if (! ctype_digit($segments[1])) continue;

            $slug = $segments[2];
            if (stripos($slug, 'Casa-en-Venta') === false) continue;
            if (stripos($slug, 'Macrocentro') === false) continue;

            $candidates[] = [
                'url'         => $loc,
                'external_id' => $segments[1],
                'lastmod'     => $lastmod,
            ];
        }

        // All sitemap URLs (used by command to detect removals)
        $allSitemapUrls = array_column($candidates, 'url');

        // Only return candidates not yet in DB
        $existing     = Property::where('source', self::SOURCE)->pluck('url')->flip()->all();
        $newCandidates = array_values(array_filter($candidates, fn($c) => ! isset($existing[$c['url']])));

        return [$newCandidates, $allSitemapUrls];
    }

    private function fetchProperty(string $url, string $externalId, ?string $lastmod): ?array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; HouseScraper/1.0)'])
                ->get($url);

            if (! $response->ok()) {
                Log::warning('BahiaBlancaProp: property fetch failed', ['url' => $url, 'status' => $response->status()]);
                return null;
            }

            $crawler = new Crawler($response->body());

            // Title
            $title = null;
            foreach (['h1', 'h2'] as $tag) {
                try {
                    $el = $crawler->filter($tag)->first();
                    if ($el->count()) {
                        $title = trim($el->text(''));
                        break;
                    }
                } catch (\Throwable) {}
            }
            $title = $title ?: 'Sin título';

            // Price — in <li> tag containing "U$S" e.g. "U$S 43.000"
            $priceRaw = null;
            try {
                $crawler->filter('li')->each(function (Crawler $node) use (&$priceRaw) {
                    if ($priceRaw !== null) return;
                    $text = trim($node->text(''));
                    if (stripos($text, 'U$S') !== false || stripos($text, 'USD') !== false) {
                        $priceRaw = $text;
                    }
                });
            } catch (\Throwable) {}

            $priceUsd = $this->extractUsd($priceRaw);

            // Skip if outside budget (keep null prices for manual review)
            if ($priceUsd !== null && ($priceUsd < self::PRICE_MIN || $priceUsd > self::PRICE_MAX)) {
                return null;
            }

            // Details section — div.col-sm-4 with text label + <strong> value
            $details = [];
            try {
                $crawler->filter('div.col-sm-4')->each(function (Crawler $node) use (&$details) {
                    $strong = $node->filter('strong')->first();
                    if (! $strong->count()) return;
                    $label = strtolower(trim(preg_replace('/\s+/', ' ', $node->text(''))));
                    $value = trim($strong->text(''));
                    $details[$label] = $value;
                });
            } catch (\Throwable) {}

            // Extract fields from details map
            $bedrooms  = null;
            $bathrooms = null;
            $areaM2    = null;
            foreach ($details as $label => $value) {
                if (stripos($label, 'dormitorio') !== false) {
                    preg_match('/(\d+)/', $value, $m);
                    $bedrooms = isset($m[1]) ? (int) $m[1] : null;
                } elseif (stripos($label, 'ba') !== false && stripos($label, 'o') !== false) {
                    preg_match('/(\d+)/', $value, $m);
                    $bathrooms = isset($m[1]) ? (int) $m[1] : null;
                } elseif (stripos($label, 'cubierta') !== false) {
                    preg_match('/(\d+)/', $value, $m);
                    $areaM2 = isset($m[1]) ? (float) $m[1] : null;
                } elseif ($areaM2 === null && stripos($label, 'total') !== false && stripos($label, 'mts') !== false) {
                    preg_match('/(\d+)/', $value, $m);
                    $areaM2 = isset($m[1]) ? (float) $m[1] : null;
                }
            }

            // Skip if bedrooms known and under minimum
            if ($bedrooms !== null && $bedrooms < 3) {
                return null;
            }

            // Neighborhood — extract from title
            $neighborhood = null;
            if (preg_match('/en Venta en .+?,\s*([^,]+),\s*Bah[íi]a Blanca/i', $title, $m)) {
                $neighborhood = trim($m[1]);
            }

            // Description — find <h6> containing "Descripción", grab next <p>
            $description = null;
            try {
                $crawler->filter('h6')->each(function (Crawler $node) use (&$description, $crawler) {
                    if ($description !== null) return;
                    if (stripos($node->text(''), 'Descripci') !== false) {
                        $next = $node->nextAll()->filter('p')->first();
                        if ($next->count()) {
                            $description = trim($next->text(''));
                        }
                    }
                });
            } catch (\Throwable) {}

            // Image — photos are in div.swiper-slide as style="background-image:url(...)"
            $imageUrl = null;
            try {
                $slide = $crawler->filter('div.swiper-slide')->first();
                if ($slide->count()) {
                    $style = $slide->attr('style') ?? '';
                    if (preg_match('/background-image:\s*url\(([^)]+)\)/i', $style, $m)) {
                        $imageUrl = trim($m[1], " \t\"'");
                    }
                }
            } catch (\Throwable) {}

            // Published at
            $publishedAt = null;
            if ($lastmod) {
                try {
                    $publishedAt = Carbon::parse($lastmod)->toDateTimeString();
                } catch (\Throwable) {}
            }

            return [
                'source'       => self::SOURCE,
                'external_id'  => $externalId,
                'url'          => $url,
                'title'        => $title,
                'price_usd'    => $priceUsd,
                'price_raw'    => $priceRaw,
                'bedrooms'     => $bedrooms,
                'bathrooms'    => $bathrooms,
                'area_m2'      => $areaM2,
                'neighborhood' => $neighborhood,
                'description'  => $description,
                'image_url'    => $imageUrl,
                'published_at' => $publishedAt,
            ];
        } catch (\Throwable $e) {
            Log::debug('BahiaBlancaProp: parse error', ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        }
    }

    private function extractUsd(?string $raw): ?float
    {
        if (! $raw) return null;

        // Only parse if explicitly USD (not ARS pesos)
        if (stripos($raw, 'U$S') === false && stripos($raw, 'USD') === false) {
            return null;
        }

        // Argentine format: dots are thousands separators, comma is decimal
        // e.g. "U$S 325.000" → 325000, "USD 125,000" → 125000
        if (preg_match('/([\d.,]+)/', str_replace(' ', '', $raw), $m)) {
            $number = $m[1];
            // If ends with ,NNN (3 digits after comma) → comma is thousands separator
            if (preg_match('/,\d{3}$/', $number)) {
                $number = str_replace(',', '', $number);
            } else {
                // Dots are thousands separators (Argentine style)
                $number = str_replace('.', '', $number);
                $number = str_replace(',', '.', $number);
            }
            return is_numeric($number) ? (float) $number : null;
        }

        return null;
    }
}
