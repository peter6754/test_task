<?php

namespace App\Repository;

interface OrderRepositoryInterface
{
    public function findOneWithArticles(int $id): ?array;

    public function createWithArticles(array $order, array $articles): array;

    public function findGroupedCounts(string $groupBy, int $page, int $perPage): array;
}
