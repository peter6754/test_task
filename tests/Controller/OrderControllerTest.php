<?php

namespace App\Tests\Controller;

use App\Tests\Double\OrderRepositoryFake;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class OrderControllerTest extends WebTestCase
{
    public function testShowReturnsOrderWithArticles(): void
    {
        $client = static::createClient();
        $orderRepository = $this->orderRepository();
        $orderRepository->order = [
            'id' => 42,
            'name' => 'Bathroom order',
            'articles' => [
                ['id' => 1, 'orders_id' => 42, 'amount' => '3.50'],
            ],
        ];

        $client->request('GET', '/api/orders/42');

        self::assertResponseIsSuccessful();
        self::assertSame($orderRepository->order, $this->json($client));
        self::assertSame([42], $orderRepository->findOneWithArticlesCalls);
    }

    public function testShowReturnsNotFoundForMissingOrder(): void
    {
        $client = static::createClient();
        $orderRepository = $this->orderRepository();

        $client->request('GET', '/api/orders/404');

        self::assertResponseStatusCodeSame(404);
        self::assertSame(
            ['error' => 'Order with id 404 was not found.'],
            $this->json($client),
        );
        self::assertSame([404], $orderRepository->findOneWithArticlesCalls);
    }

    public function testGroupedReturnsPaginatedGroups(): void
    {
        $client = static::createClient();
        $orderRepository = $this->orderRepository();
        $orderRepository->groupedCountsResult = [
            'groupBy' => 'day',
            'page' => 2,
            'perPage' => 3,
            'totalItems' => 8,
            'totalOrders' => 25,
            'totalPages' => 3,
            'hasNextPage' => true,
            'hasPreviousPage' => true,
            'items' => [
                ['groupValue' => '2026-04-10', 'count' => 4],
            ],
        ];

        $client->request('GET', '/api/orders/grouped?group_by=day&page=2&per_page=3');

        self::assertResponseIsSuccessful();
        self::assertSame($orderRepository->groupedCountsResult, $this->json($client));
        self::assertSame(
            [['groupBy' => 'day', 'page' => 2, 'perPage' => 3]],
            $orderRepository->findGroupedCountsCalls,
        );
    }

    public function testGroupedReturnsBadRequestForUnsupportedGroupBy(): void
    {
        $client = static::createClient();
        $orderRepository = $this->orderRepository();
        $orderRepository->groupedCountsException = new \InvalidArgumentException('Unsupported groupBy value. Use day, month or year.');

        $client->request('GET', '/api/orders/grouped?groupBy=week');

        self::assertResponseStatusCodeSame(400);
        self::assertSame(
            ['error' => 'Unsupported groupBy value. Use day, month or year.'],
            $this->json($client),
        );
        self::assertSame(
            [['groupBy' => 'week', 'page' => 1, 'perPage' => 20]],
            $orderRepository->findGroupedCountsCalls,
        );
    }

    private function orderRepository(): OrderRepositoryFake
    {
        $orderRepository = static::getContainer()->get(OrderRepositoryFake::class);
        self::assertInstanceOf(OrderRepositoryFake::class, $orderRepository);
        $orderRepository->reset();

        return $orderRepository;
    }

    private function json(KernelBrowser $client): array
    {
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        return $payload;
    }
}
