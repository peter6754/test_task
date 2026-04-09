<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrderController extends AbstractController
{
    #[Route('/api/orders/{id}', name: 'app_api_order_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, OrderRepository $orderRepository): JsonResponse
    {
        $order = $orderRepository->findOneWithArticles($id);

        if ($order === null) {
            return $this->json(
                ['error' => sprintf('Order with id %d was not found.', $id)],
                Response::HTTP_NOT_FOUND,
            );
        }

        return $this->json($order);
    }
}
