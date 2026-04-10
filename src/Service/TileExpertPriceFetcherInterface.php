<?php

namespace App\Service;

interface TileExpertPriceFetcherInterface
{
    public function fetchPrice(string $factory, string $collection, string $article): ?float;
}
