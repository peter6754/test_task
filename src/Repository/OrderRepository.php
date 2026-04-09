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
}
