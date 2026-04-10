<?php

namespace App\Tests\Double;

use App\Service\TileExpertPriceFetcherInterface;

final class TileExpertPriceFetcherFake implements TileExpertPriceFetcherInterface
{
    public ?float $price = null;
    public ?\RuntimeException $exception = null;
    public array $requests = [];

    public function reset(): void
    {
        $this->price = null;
        $this->exception = null;
        $this->requests = [];
    }

    public function fetchPrice(string $factory, string $collection, string $article): ?float
    {
        $this->requests[] = [
            'factory' => $factory,
            'collection' => $collection,
            'article' => $article,
        ];

        if ($this->exception !== null) {
            throw $this->exception;
        }

        return $this->price;
    }
}
