<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class OpenApiDocumentationTest extends WebTestCase
{
    public function testDocumentationContainsCustomControllerRoutes(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/docs.jsonopenapi');

        self::assertResponseIsSuccessful();

        $documentation = $this->json($client);
        self::assertArrayHasKey('/api/orders/grouped', $documentation['paths']);
        self::assertArrayHasKey('/api/orders/{id}', $documentation['paths']);
        self::assertArrayHasKey('/api/price', $documentation['paths']);
        self::assertArrayHasKey('/soap/orders', $documentation['paths']);

        self::assertSame('getGroupedOrders', $documentation['paths']['/api/orders/grouped']['get']['operationId']);
        self::assertSame('getOrder', $documentation['paths']['/api/orders/{id}']['get']['operationId']);
        self::assertSame('getTileExpertPrice', $documentation['paths']['/api/price']['get']['operationId']);
        self::assertSame('createSoapOrder', $documentation['paths']['/soap/orders']['post']['operationId']);
    }

    private function json(KernelBrowser $client): array
    {
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        return $payload;
    }
}
