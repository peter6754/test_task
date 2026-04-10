<?php

namespace App\Tests\Double;

use App\Repository\OrderRepositoryInterface;

final class OrderRepositoryFake implements OrderRepositoryInterface
{
    public ?array $order = null;
    public ?array $groupedCountsResult = null;
    public ?\InvalidArgumentException $groupedCountsException = null;
    public ?array $createdOrderResult = null;
    public array $findOneWithArticlesCalls = [];
    public array $findGroupedCountsCalls = [];
    public array $createWithArticlesCalls = [];

    public function reset(): void
    {
        $this->order = null;
        $this->groupedCountsResult = null;
        $this->groupedCountsException = null;
        $this->createdOrderResult = null;
        $this->findOneWithArticlesCalls = [];
        $this->findGroupedCountsCalls = [];
        $this->createWithArticlesCalls = [];
    }

    public function findOneWithArticles(int $id): ?array
    {
        $this->findOneWithArticlesCalls[] = $id;

        return $this->order;
    }

    public function createWithArticles(array $order, array $articles): array
    {
        $this->createWithArticlesCalls[] = [
            'order' => $order,
            'articles' => $articles,
        ];

        return $this->createdOrderResult ?? [
            'id' => 1,
            'hash' => 'test-hash',
            'token' => 'test-token',
        ];
    }

    public function findGroupedCounts(string $groupBy, int $page, int $perPage): array
    {
        $this->findGroupedCountsCalls[] = [
            'groupBy' => $groupBy,
            'page' => $page,
            'perPage' => $perPage,
        ];

        if ($this->groupedCountsException !== null) {
            throw $this->groupedCountsException;
        }

        return $this->groupedCountsResult ?? [
            'groupBy' => $groupBy,
            'page' => $page,
            'perPage' => $perPage,
            'totalItems' => 0,
            'totalOrders' => 0,
            'totalPages' => 1,
            'hasNextPage' => false,
            'hasPreviousPage' => false,
            'items' => [],
        ];
    }
}
