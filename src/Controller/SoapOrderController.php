<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SoapOrderController
{
    #[Route('/soap/orders', name: 'app_soap_orders', methods: ['POST'])]
    public function __invoke(Request $request, OrderRepository $orderRepository): Response
    {
        $fault = static function (string $message, int $status): Response {
            $faultCode = $status >= 500 ? 'soap:Server' : 'soap:Client';
            $xml = '<?xml version="1.0" encoding="UTF-8"?>'
                . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
                . '<soap:Body>'
                . '<soap:Fault>'
                . '<faultcode>'.$faultCode.'</faultcode>'
                . '<faultstring>'.htmlspecialchars($message, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</faultstring>'
                . '</soap:Fault>'
                . '</soap:Body>'
                . '</soap:Envelope>';

            return new Response($xml, $status, ['Content-Type' => 'text/xml; charset=UTF-8']);
        };

        $childValue = static function (DOMElement $parent, string ...$names): ?string {
            foreach ($names as $name) {
                foreach ($parent->childNodes as $child) {
                    if (!$child instanceof DOMElement || $child->localName !== $name) {
                        continue;
                    }

                    $value = trim($child->textContent);

                    return $value === '' ? null : $value;
                }
            }

            return null;
        };

        $children = static function (DOMElement $parent, string $name): array {
            $nodes = [];

            foreach ($parent->childNodes as $child) {
                if ($child instanceof DOMElement && $child->localName === $name) {
                    $nodes[] = $child;
                }
            }

            return $nodes;
        };

        try {
            $content = trim($request->getContent());

            if ($content === '') {
                throw new \InvalidArgumentException('SOAP request body is empty.');
            }

            $document = new DOMDocument();
            $previous = libxml_use_internal_errors(true);
            $loaded = $document->loadXML($content);
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            if ($loaded === false) {
                throw new \InvalidArgumentException('Invalid SOAP XML.');
            }

            $xpath = new DOMXPath($document);
            $operation = $xpath->query('/*[local-name()="Envelope"]/*[local-name()="Body"]/*[local-name()="CreateOrderRequest"]')->item(0);

            if (!$operation instanceof DOMElement) {
                throw new \InvalidArgumentException('SOAP operation must be CreateOrderRequest.');
            }

            $orderNode = null;

            foreach ($operation->childNodes as $child) {
                if ($child instanceof DOMElement && $child->localName === 'order') {
                    $orderNode = $child;
                    break;
                }
            }

            $orderNode ??= $operation;

            $name = $childValue($orderNode, 'name');
            $payType = $childValue($orderNode, 'payType', 'pay_type');
            $locale = $childValue($orderNode, 'locale');

            if ($name === null || $payType === null || $locale === null) {
                throw new \InvalidArgumentException('Fields "name", "payType" and "locale" are required.');
            }

            $articlesParent = null;

            foreach ($orderNode->childNodes as $child) {
                if ($child instanceof DOMElement && $child->localName === 'articles') {
                    $articlesParent = $child;
                    break;
                }
            }

            $articleNodes = $children($articlesParent ?? $orderNode, 'article');

            if ($articleNodes === []) {
                throw new \InvalidArgumentException('At least one article is required.');
            }

            $currency = $childValue($orderNode, 'currency') ?? 'EUR';
            $measure = $childValue($orderNode, 'measure') ?? 'm';
            $orderRow = [
                'hash' => md5(bin2hex(random_bytes(16))),
                'token' => $childValue($orderNode, 'token') ?? bin2hex(random_bytes(32)),
                'status' => 1,
                'vat_type' => 0,
                'pay_type' => (int) $payType,
                'locale' => $locale,
                'currency' => $currency,
                'measure' => $measure,
                'name' => $name,
                'create_date' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
                'step' => 1,
            ];

            foreach ([
                'number' => 'number',
                'email' => 'email',
                'description' => 'description',
                'clientName' => 'client_name',
                'client_name' => 'client_name',
                'clientSurname' => 'client_surname',
                'client_surname' => 'client_surname',
                'companyName' => 'company_name',
                'company_name' => 'company_name',
            ] as $source => $target) {
                $value = $childValue($orderNode, $source);

                if ($value !== null) {
                    $orderRow[$target] = $value;
                }
            }

            $articleRows = [];

            foreach ($articleNodes as $index => $articleNode) {
                $amount = $childValue($articleNode, 'amount');
                $price = $childValue($articleNode, 'price');
                $weight = $childValue($articleNode, 'weight');
                $packagingCount = $childValue($articleNode, 'packagingCount', 'packaging_count');
                $pallet = $childValue($articleNode, 'pallet');
                $packaging = $childValue($articleNode, 'packaging');

                foreach ([
                    'amount' => $amount,
                    'price' => $price,
                    'weight' => $weight,
                    'packagingCount' => $packagingCount,
                    'pallet' => $pallet,
                    'packaging' => $packaging,
                ] as $field => $value) {
                    if ($value === null || !is_numeric($value)) {
                        throw new \InvalidArgumentException(
                            sprintf('Article %d field "%s" is required and must be numeric.', $index + 1, $field),
                        );
                    }
                }

                $articleRow = [
                    'amount' => (float) $amount,
                    'price' => (float) $price,
                    'currency' => $childValue($articleNode, 'currency') ?? $currency,
                    'measure' => $childValue($articleNode, 'measure') ?? $measure,
                    'weight' => (float) $weight,
                    'packaging_count' => (float) $packagingCount,
                    'pallet' => (float) $pallet,
                    'packaging' => (float) $packaging,
                ];

                $articleId = $childValue($articleNode, 'articleId', 'article_id');
                $priceEur = $childValue($articleNode, 'priceEur', 'price_eur');
                $multiplePallet = $childValue($articleNode, 'multiplePallet', 'multiple_pallet');
                $deliveryTimeMin = $childValue($articleNode, 'deliveryTimeMin', 'delivery_time_min');
                $deliveryTimeMax = $childValue($articleNode, 'deliveryTimeMax', 'delivery_time_max');
                $swimmingPool = $childValue($articleNode, 'swimmingPool', 'swimming_pool');

                if ($articleId !== null) {
                    $articleRow['article_id'] = (int) $articleId;
                }

                if ($priceEur !== null) {
                    $articleRow['price_eur'] = (float) $priceEur;
                }

                if ($multiplePallet !== null) {
                    $articleRow['multiple_pallet'] = (int) $multiplePallet;
                }

                if ($deliveryTimeMin !== null) {
                    $articleRow['delivery_time_min'] = $deliveryTimeMin;
                }

                if ($deliveryTimeMax !== null) {
                    $articleRow['delivery_time_max'] = $deliveryTimeMax;
                }

                if ($swimmingPool !== null) {
                    $articleRow['swimming_pool'] = in_array(strtolower($swimmingPool), ['1', 'true', 'yes'], true) ? 1 : 0;
                }

                $articleRows[] = $articleRow;
            }

            $result = $orderRepository->createWithArticles($orderRow, $articleRows);
        } catch (\InvalidArgumentException $exception) {
            return $fault($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $exception) {
            return $fault(sprintf('Order could not be created: %s', $exception->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soap:Body>'
            . '<CreateOrderResponse>'
            . '<created>true</created>'
            . '<orderId>'.$result['id'].'</orderId>'
            . '<hash>'.htmlspecialchars($result['hash'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</hash>'
            . '<token>'.htmlspecialchars($result['token'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</token>'
            . '</CreateOrderResponse>'
            . '</soap:Body>'
            . '</soap:Envelope>';

        return new Response($xml, Response::HTTP_OK, ['Content-Type' => 'text/xml; charset=UTF-8']);
    }
}
