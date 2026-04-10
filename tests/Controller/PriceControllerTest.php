<?php

namespace App\Tests\Controller;

use App\Tests\Double\TileExpertPriceFetcherFake;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PriceControllerTest extends WebTestCase
{
    public function testGetPriceRequiresQueryParameters(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/price');

        self::assertResponseStatusCodeSame(400);
        self::assertSame(
            ['error' => 'Query parameters "factory", "collection" and "article" are required.'],
            $this->json($client),
        );
    }

    public function testGetPriceReturnsFetchedPrice(): void
    {
        $client = static::createClient();
        $priceFetcher = $this->priceFetcher();
        $priceFetcher->price = 19.95;

        $client->request('GET', '/api/price?factory=Ragno&collection=Woodline&article=ABC-123');

        self::assertResponseIsSuccessful();
        self::assertSame(
            [
                'price' => 19.95,
                'factory' => 'Ragno',
                'collection' => 'Woodline',
                'article' => 'ABC-123',
            ],
            $this->json($client),
        );
        self::assertSame(
            [['factory' => 'Ragno', 'collection' => 'Woodline', 'article' => 'ABC-123']],
            $priceFetcher->requests,
        );
    }

    public function testGetPriceReturnsNotFoundWhenProductIsMissing(): void
    {
        $client = static::createClient();
        $priceFetcher = $this->priceFetcher();

        $client->request('GET', '/api/price?factory=Ragno&collection=Woodline&article=missing');

        self::assertResponseStatusCodeSame(404);
        self::assertSame(
            ['error' => 'The requested Tile.Expert product was not found.'],
            $this->json($client),
        );
        self::assertSame(
            [['factory' => 'Ragno', 'collection' => 'Woodline', 'article' => 'missing']],
            $priceFetcher->requests,
        );
    }

    public function testGetPriceReturnsBadGatewayWhenFetcherFails(): void
    {
        $client = static::createClient();
        $priceFetcher = $this->priceFetcher();
        $priceFetcher->exception = new \RuntimeException('Tile.Expert returned HTTP 503.');

        $client->request('GET', '/api/price?factory=Ragno&collection=Woodline&article=ABC-123');

        self::assertResponseStatusCodeSame(502);
        self::assertSame(
            ['error' => 'Tile.Expert returned HTTP 503.'],
            $this->json($client),
        );
        self::assertSame(
            [['factory' => 'Ragno', 'collection' => 'Woodline', 'article' => 'ABC-123']],
            $priceFetcher->requests,
        );
    }

    private function priceFetcher(): TileExpertPriceFetcherFake
    {
        $priceFetcher = static::getContainer()->get(TileExpertPriceFetcherFake::class);
        self::assertInstanceOf(TileExpertPriceFetcherFake::class, $priceFetcher);
        $priceFetcher->reset();

        return $priceFetcher;
    }

    private function json(KernelBrowser $client): array
    {
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        return $payload;
    }
}
