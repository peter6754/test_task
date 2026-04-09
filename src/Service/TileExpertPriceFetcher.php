<?php

namespace App\Service;

final class TileExpertPriceFetcher
{
    private const PRODUCT_URL_TEMPLATE = 'https://tile.expert/it/tile/%s/%s/a/%s';

    public function fetchPrice(string $factory, string $collection, string $article): ?float
    {
        $html = $this->requestProductPage($this->buildProductUrl($factory, $collection, $article));

        if ($html === null) {
            return null;
        }

        return $this->extractPriceFromHtml($html, $this->buildProductPath($factory, $collection, $article));
    }

    public function buildProductUrl(string $factory, string $collection, string $article): string
    {
        return sprintf(
            self::PRODUCT_URL_TEMPLATE,
            rawurlencode($factory),
            rawurlencode($collection),
            rawurlencode($article),
        );
    }

    public function extractPriceFromHtml(string $html, ?string $productPath = null): float
    {
        $price = $productPath === null ? null : $this->findPriceInProductData($html, $productPath);

        if ($price !== null) {
            return $price;
        }

        if ($productPath !== null) {
            if (!$this->containsProductPath($html, $productPath)) {
                throw new \RuntimeException('The Tile.Expert product payload format was not recognized.');
            }

            $price = $this->findPriceInJsonLdOffer($html, $productPath);

            if ($price !== null) {
                return $price;
            }

            throw new \RuntimeException('The Tile.Expert product price could not be parsed.');
        }

        $text = $this->normalizeText($html);
        $price = $this->findPrice($text);

        if ($price === null) {
            throw new \RuntimeException('The Tile.Expert page price could not be parsed.');
        }

        return $price;
    }

    public function buildProductPath(string $factory, string $collection, string $article): string
    {
        return sprintf(
            '/it/tile/%s/%s/a/%s',
            rawurlencode($factory),
            rawurlencode($collection),
            rawurlencode($article),
        );
    }

    private function requestProductPage(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 15,
                'ignore_errors' => true,
                'header' => implode("\r\n", [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: it-IT,it;q=0.9,en;q=0.8',
                    'Connection: close',
                    'User-Agent: Mozilla/5.0 (compatible; TileExpertPriceBot/1.0; +https://tile.expert)',
                ]),
            ],
        ]);

        $html = @file_get_contents($url, false, $context);
        $statusCode = $this->extractStatusCode($http_response_header ?? []);

        if ($html === false) {
            throw new \RuntimeException('The Tile.Expert page could not be fetched.');
        }

        if ($statusCode === 404) {
            return null;
        }

        if ($statusCode >= 400) {
            throw new \RuntimeException(sprintf('Tile.Expert returned HTTP %d.', $statusCode));
        }

        return $html;
    }

    private function extractStatusCode(array $headers): int
    {
        foreach ($headers as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $matches) === 1) {
                return (int) $matches[1];
            }
        }

        return 0;
    }

    private function normalizeText(string $html): string
    {
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', ' ', $html) ?? $html;
        $html = preg_replace('#<style\b[^>]*>.*?</style>#is', ' ', $html) ?? $html;
        $html = preg_replace('#<noscript\b[^>]*>.*?</noscript>#is', ' ', $html) ?? $html;

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    private function containsProductPath(string $html, string $productPath): bool
    {
        $escapedProductPath = str_replace('/', '\/', $productPath);

        return str_contains($html, $productPath) || str_contains($html, $escapedProductPath);
    }

    private function findPriceInProductData(string $html, string $productPath): ?float
    {
        $escapedProductPath = str_replace('/', '\/', $productPath);

        foreach ([
            '"ssLink":"'.$escapedProductPath.'"',
            '"hrefSlider":"'.$escapedProductPath.'"',
            '"url":"https://tile.expert'.$productPath.'"',
        ] as $needle) {
            $position = strpos($html, $needle);

            if ($position === false) {
                continue;
            }

            $searchArea = substr($html, max(0, $position - 4000), 4200 + strlen($needle));

            if (preg_match_all(
                '/"discountsAmount"\s*:\s*\[\s*\{\s*"priceFl"\s*:\s*(?<price>\d+(?:\.\d+)?).*?"startAmountRaw"\s*:\s*0/su',
                $searchArea,
                $matches,
            ) > 0) {
                return $this->normalizePrice(end($matches['price']));
            }

            if (preg_match_all('/"prc"\s*:\s*"(?<price>\d+(?:[.,]\d+)?)"/su', $searchArea, $matches) > 0) {
                return $this->normalizePrice(end($matches['price']));
            }
        }

        return null;
    }

    private function findPriceInJsonLdOffer(string $html, string $productPath): ?float
    {
        $escapedProductUrl = preg_quote('https://tile.expert'.$productPath, '/');

        if (preg_match(
            '/"offers"\s*:\s*\{\s*"@type"\s*:\s*"Offer".*?"priceCurrency"\s*:\s*"EUR".*?"price"\s*:\s*"(?<price>\d+(?:\.\d+)?)".*?"url"\s*:\s*"'.$escapedProductUrl.'"/su',
            $html,
            $matches,
        ) === 1) {
            return $this->normalizePrice($matches['price']);
        }

        return null;
    }

    private function findPrice(string $text): ?float
    {
        foreach ([
            '/\bIVA\b.*?(\d+(?:[.,]\d{2}))/isu',
            '/\bVAT\b.*?(\d+(?:[.,]\d{2}))/isu',
        ] as $pattern) {
            if (preg_match($pattern, $text, $matches) === 1) {
                return $this->normalizePrice($matches[1]);
            }
        }

        if (preg_match_all('/(?<!\d)(\d+(?:[.,]\d{2}))(?!\d)/u', $text, $matches) > 0) {
            return $this->normalizePrice($matches[1][0]);
        }

        return null;
    }

    private function normalizePrice(string $price): float
    {
        return round((float) str_replace(',', '.', $price), 2);
    }
}
