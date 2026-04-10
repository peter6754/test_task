<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

final class OrderRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function findOneWithArticles(int $id): ?array
    {
        $order = $this->connection->fetchAssociative(
            'SELECT * FROM orders WHERE id = :id LIMIT 1',
            ['id' => $id],
            ['id' => ParameterType::INTEGER],
        );

        if ($order === false) {
            return null;
        }

        $order['articles'] = $this->connection->fetchAllAssociative(
            'SELECT * FROM orders_article WHERE orders_id = :id ORDER BY id ASC',
            ['id' => $id],
            ['id' => ParameterType::INTEGER],
        );

        return $order;
    }

    public function createWithArticles(array $order, array $articles): array
    {
        return $this->connection->transactional(function (Connection $connection) use ($order, $articles): array {
            $connection->insert('orders', $order);

            $orderId = (int) $connection->lastInsertId();

            foreach ($articles as $article) {
                $article['orders_id'] = $orderId;
                $connection->insert('orders_article', $article);
            }

            return [
                'id' => $orderId,
                'hash' => (string) $order['hash'],
                'token' => (string) $order['token'],
            ];
        });
    }

    public function findGroupedCounts(string $groupBy, int $page, int $perPage): array
    {
        $format = match ($groupBy) {
            'year' => '%Y',
            'month' => '%Y-%m',
            'day' => '%Y-%m-%d',
            default => throw new \InvalidArgumentException('Unsupported groupBy value. Use day, month or year.'),
        };

        $groupExpression = sprintf("DATE_FORMAT(create_date, '%s')", $format);
        $offset = ($page - 1) * $perPage;

        $groups = $this->connection->fetchAllAssociative(
            sprintf(
                'SELECT %1$s AS group_value, COUNT(*) AS order_count
                 FROM orders
                 GROUP BY group_value
                 ORDER BY group_value DESC
                 LIMIT :limit OFFSET :offset',
                $groupExpression,
            ),
            [
                'limit' => $perPage,
                'offset' => $offset,
            ],
            [
                'limit' => ParameterType::INTEGER,
                'offset' => ParameterType::INTEGER,
            ],
        );

        $totalGroups = (int) $this->connection->fetchOne(
            sprintf(
                'SELECT COUNT(*) FROM (
                    SELECT %1$s AS group_value
                    FROM orders
                    GROUP BY group_value
                ) grouped_orders',
                $groupExpression,
            ),
        );

        $totalOrders = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM orders');
        $totalPages = max(1, (int) ceil($totalGroups / $perPage));

        return [
            'groupBy' => $groupBy,
            'page' => $page,
            'perPage' => $perPage,
            'totalItems' => $totalGroups,
            'totalOrders' => $totalOrders,
            'totalPages' => $totalPages,
            'hasNextPage' => $page < $totalPages,
            'hasPreviousPage' => $page > 1,
            'items' => array_map(static fn (array $group): array => [
                'groupValue' => $group['group_value'],
                'count' => (int) $group['order_count'],
            ], $groups),
        ];
    }
}
