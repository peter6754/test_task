<?php

namespace App\Controller;

use App\Repository\OrderRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrderController extends AbstractController
{
    #[Route('/api/orders/grouped', name: 'app_api_order_grouped', methods: ['GET'])]
    public function grouped(Request $request, OrderRepositoryInterface $orderRepository): JsonResponse
    {
        $groupBy = (string) ($request->query->get('groupBy') ?? $request->query->get('group_by') ?? 'month');
        $page = max(1, $request->query->getInt('page', 1));
        $perPage = max(1, min(100, $request->query->getInt('perPage', $request->query->getInt('per_page', 20))));

        try {
            $result = $orderRepository->findGroupedCounts($groupBy, $page, $perPage);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(
                ['error' => $exception->getMessage()],
                Response::HTTP_BAD_REQUEST,
            );
        }

        return $this->json($result);
    }

    #[Route('/api/orders/{id}', name: 'app_api_order_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, OrderRepositoryInterface $orderRepository): JsonResponse
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
