<?php

namespace App\Service;

use App\Repository\OrderRepositoryInterface;
use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Symfony\Component\HttpFoundation\Response;

final class SoapOrderService
{
    private const XML_CONTENT_TYPE = 'text/xml; charset=UTF-8';

    public function __construct(private OrderRepositoryInterface $orderRepository)
    {
    }

    public function handleCreateOrderRequest(string $requestContent): Response
    {
        try {
            $result = $this->createOrder($requestContent);
        } catch (\InvalidArgumentException $exception) {
            return $this->faultResponse($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $exception) {
            return $this->faultResponse(
                sprintf('Order could not be created: %s', $exception->getMessage()),
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }

        return $this->xmlResponse($this->createOrderResponseXml($result), Response::HTTP_OK);
    }

    private function createOrder(string $requestContent): array
    {
        $orderNode = $this->extractOrderNode($requestContent);

        $name = $this->childValue($orderNode, 'name');
        $payType = $this->childValue($orderNode, 'payType', 'pay_type');
        $locale = $this->childValue($orderNode, 'locale');

        if ($name === null || $payType === null || $locale === null) {
            throw new \InvalidArgumentException('Fields "name", "payType" and "locale" are required.');
        }

        $articlesParent = $this->firstChild($orderNode, 'articles');
        $articleNodes = $this->children($articlesParent ?? $orderNode, 'article');

        if ($articleNodes === []) {
            throw new \InvalidArgumentException('At least one article is required.');
        }

        $currency = $this->childValue($orderNode, 'currency') ?? 'EUR';
        $measure = $this->childValue($orderNode, 'measure') ?? 'm';
        $orderRow = [
            'hash' => md5(bin2hex(random_bytes(16))),
            'token' => $this->childValue($orderNode, 'token') ?? bin2hex(random_bytes(32)),
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
            $value = $this->childValue($orderNode, $source);

            if ($value !== null) {
                $orderRow[$target] = $value;
            }
        }

        $articleRows = [];

        foreach ($articleNodes as $index => $articleNode) {
            $articleRows[] = $this->buildArticleRow($articleNode, $index, $currency, $measure);
        }

        return $this->orderRepository->createWithArticles($orderRow, $articleRows);
    }

    private function extractOrderNode(string $requestContent): DOMElement
    {
        $content = trim($requestContent);

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

        return $this->firstChild($operation, 'order') ?? $operation;
    }

    private function buildArticleRow(DOMElement $articleNode, int $index, string $currency, string $measure): array
    {
        $amount = $this->childValue($articleNode, 'amount');
        $price = $this->childValue($articleNode, 'price');
        $weight = $this->childValue($articleNode, 'weight');
        $packagingCount = $this->childValue($articleNode, 'packagingCount', 'packaging_count');
        $pallet = $this->childValue($articleNode, 'pallet');
        $packaging = $this->childValue($articleNode, 'packaging');

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
            'currency' => $this->childValue($articleNode, 'currency') ?? $currency,
            'measure' => $this->childValue($articleNode, 'measure') ?? $measure,
            'weight' => (float) $weight,
            'packaging_count' => (float) $packagingCount,
            'pallet' => (float) $pallet,
            'packaging' => (float) $packaging,
        ];

        $articleId = $this->childValue($articleNode, 'articleId', 'article_id');
        $priceEur = $this->childValue($articleNode, 'priceEur', 'price_eur');
        $multiplePallet = $this->childValue($articleNode, 'multiplePallet', 'multiple_pallet');
        $deliveryTimeMin = $this->childValue($articleNode, 'deliveryTimeMin', 'delivery_time_min');
        $deliveryTimeMax = $this->childValue($articleNode, 'deliveryTimeMax', 'delivery_time_max');
        $swimmingPool = $this->childValue($articleNode, 'swimmingPool', 'swimming_pool');

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

        return $articleRow;
    }

    private function childValue(DOMElement $parent, string ...$names): ?string
    {
        foreach ($names as $name) {
            $child = $this->firstChild($parent, $name);

            if ($child === null) {
                continue;
            }

            $value = trim($child->textContent);

            return $value === '' ? null : $value;
        }

        return null;
    }

    private function firstChild(DOMElement $parent, string $name): ?DOMElement
    {
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === $name) {
                return $child;
            }
        }

        return null;
    }

    private function children(DOMElement $parent, string $name): array
    {
        $nodes = [];

        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === $name) {
                $nodes[] = $child;
            }
        }

        return $nodes;
    }

    private function createOrderResponseXml(array $result): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soap:Body>'
            . '<CreateOrderResponse>'
            . '<created>true</created>'
            . '<orderId>'.$result['id'].'</orderId>'
            . '<hash>'.$this->escapeXml((string) $result['hash']).'</hash>'
            . '<token>'.$this->escapeXml((string) $result['token']).'</token>'
            . '</CreateOrderResponse>'
            . '</soap:Body>'
            . '</soap:Envelope>';
    }

    private function faultResponse(string $message, int $status): Response
    {
        $faultCode = $status >= 500 ? 'soap:Server' : 'soap:Client';
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soap:Body>'
            . '<soap:Fault>'
            . '<faultcode>'.$faultCode.'</faultcode>'
            . '<faultstring>'.$this->escapeXml($message).'</faultstring>'
            . '</soap:Fault>'
            . '</soap:Body>'
            . '</soap:Envelope>';

        return $this->xmlResponse($xml, $status);
    }

    private function xmlResponse(string $xml, int $status): Response
    {
        return new Response($xml, $status, ['Content-Type' => self::XML_CONTENT_TYPE]);
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
