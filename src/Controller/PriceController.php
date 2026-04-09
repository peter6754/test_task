<?php

namespace App\Controller;

use App\Service\TileExpertPriceFetcher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PriceController extends AbstractController
{
    #[Route('/api/price', name: 'app_api_price', methods: ['GET'])]
    public function getPrice(Request $request, TileExpertPriceFetcher $priceFetcher): JsonResponse
    {
        $factory = trim((string) $request->query->get('factory', ''));
        $collection = trim((string) $request->query->get('collection', ''));
        $article = trim((string) $request->query->get('article', ''));

        if ($factory === '' || $collection === '' || $article === '') {
            return $this->json(
                ['error' => 'Query parameters "factory", "collection" and "article" are required.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $price = $priceFetcher->fetchPrice($factory, $collection, $article);
        } catch (\RuntimeException $exception) {
            return $this->json(
                ['error' => $exception->getMessage()],
                Response::HTTP_BAD_GATEWAY,
            );
        }

        if ($price === null) {
            return $this->json(
                ['error' => 'The requested Tile.Expert product was not found.'],
                Response::HTTP_NOT_FOUND,
            );
        }

        return $this->json([
            'price' => $price,
            'factory' => $factory,
            'collection' => $collection,
            'article' => $article,
        ]);
    }
}
