<?php

namespace App\Scrapers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MercadoLibreScraper
{
    // MLA1459 = Casas, TUxBUEJBSFVFejU4NQ== = Bahía Blanca
    private const SEARCH_URL = 'https://api.mercadolibre.com/sites/MLA/search';
    private const CATEGORY   = 'MLA1459';
    private const CITY_ID    = 'TUxBQ0JBSDcxNDQ';   // Bahía Blanca city
    private const MAX_USD    = 250000;
    private const MIN_ROOMS  = 3;

    public function fetch(): array
    {
        $properties = [];
        $offset = 0;
        $limit  = 50;

        do {
            // ML API filters: PRICE in USD, BEDROOMS, city via q
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; HouseScraper/1.0)'])
                ->get(self::SEARCH_URL, [
                    'category' => self::CATEGORY,
                    'city'     => self::CITY_ID,
                    'price'    => '*-' . self::MAX_USD,
                    'CURRENCY_ID' => 'USD',
                    'BEDROOMS' => self::MIN_ROOMS . '-*',
                    'offset'   => $offset,
                    'limit'    => $limit,
                ]);

            if (! $response->ok()) {
                Log::warning('MercadoLibre: request failed', ['status' => $response->status()]);
                break;
            }

            $data    = $response->json();
            $results = $data['results'] ?? [];

            foreach ($results as $item) {
                $properties[] = $this->normalize($item);
            }

            $offset += $limit;
            $total   = $data['paging']['total'] ?? 0;

        } while ($offset < $total && $offset < 500); // cap at 500

        return $properties;
    }

    private function normalize(array $item): array
    {
        $attributes = collect($item['attributes'] ?? []);

        $bedrooms = (int) ($attributes->firstWhere('id', 'BEDROOMS')['value_name'] ?? 0);
        $bathrooms = (int) ($attributes->firstWhere('id', 'FULL_BATHROOMS')['value_name'] ?? 0);
        $area     = (float) ($attributes->firstWhere('id', 'TOTAL_AREA')['value_name']
            ?? $attributes->firstWhere('id', 'COVERED_AREA')['value_name'] ?? 0);

        $priceUsd = null;
        if (isset($item['price'])) {
            $currency = $item['currency_id'] ?? 'ARS';
            $priceUsd = $currency === 'USD' ? (float) $item['price'] : null;
        }

        return [
            'source'      => 'mercadolibre',
            'external_id' => (string) $item['id'],
            'url'         => $item['permalink'] ?? '',
            'title'       => $item['title'] ?? '',
            'price_usd'   => $priceUsd,
            'price_raw'   => isset($item['price']) ? ($item['currency_id'] . ' ' . number_format($item['price'])) : null,
            'bedrooms'    => $bedrooms ?: null,
            'bathrooms'   => $bathrooms ?: null,
            'area_m2'     => $area ?: null,
            'neighborhood' => $item['location']['city']['name'] ?? null,
            'description' => $item['title'] ?? null,
            'image_url'   => $item['thumbnail'] ?? null,
            'published_at' => isset($item['date_created'])
                ? \Carbon\Carbon::parse($item['date_created'])->toDateTimeString()
                : null,
        ];
    }
}
