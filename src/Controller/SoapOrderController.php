<?php

namespace App\Controller;

use App\Service\SoapOrderService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SoapOrderController
{
    #[Route('/soap/orders', name: 'app_soap_orders', methods: ['POST'])]
    public function __invoke(Request $request, SoapOrderService $soapOrderService): Response
    {
        return $soapOrderService->handleCreateOrderRequest($request->getContent());
    }
}
