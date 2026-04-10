<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response as OpenApiResponse;
use ApiPlatform\OpenApi\OpenApi;

final class ApplicationOpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated)
    {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $paths = $openApi->getPaths();

        $paths->addPath('/api/orders/grouped', new PathItem(get: new Operation(
            operationId: 'getGroupedOrders',
            tags: ['Orders'],
            responses: [
                '200' => $this->jsonResponse('Orders grouped by day, month or year.', $this->groupedOrdersSchema(), [
                    'groupBy' => 'month',
                    'page' => 1,
                    'perPage' => 20,
                    'totalItems' => 1,
                    'totalOrders' => 12,
                    'totalPages' => 1,
                    'hasNextPage' => false,
                    'hasPreviousPage' => false,
                    'items' => [
                        ['groupValue' => '2026-04', 'count' => 12],
                    ],
                ]),
                '400' => $this->jsonResponse('Unsupported grouping value.', $this->errorSchema(), [
                    'error' => 'Unsupported groupBy value. Use day, month or year.',
                ]),
            ],
            summary: 'List grouped order counts',
            description: 'Returns order counts grouped by day, month or year with pagination metadata.',
            parameters: [
                new Parameter('groupBy', 'query', 'Grouping interval. The group_by alias is also accepted.', false, schema: [
                    'type' => 'string',
                    'enum' => ['day', 'month', 'year'],
                    'default' => 'month',
                ], example: 'month'),
                new Parameter('page', 'query', 'Page number. Values below 1 are normalized to 1.', false, schema: [
                    'type' => 'integer',
                    'minimum' => 1,
                    'default' => 1,
                ], example: 1),
                new Parameter('perPage', 'query', 'Items per page. The per_page alias is also accepted.', false, schema: [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 100,
                    'default' => 20,
                ], example: 20),
            ],
        )));

        $paths->addPath('/api/orders/{id}', new PathItem(get: new Operation(
            operationId: 'getOrder',
            tags: ['Orders'],
            responses: [
                '200' => $this->jsonResponse('Order with its articles.', $this->orderSchema(), [
                    'id' => 42,
                    'name' => 'Bathroom order',
                    'articles' => [
                        ['id' => 1, 'orders_id' => 42, 'amount' => 3.5],
                    ],
                ]),
                '404' => $this->jsonResponse('Order was not found.', $this->errorSchema(), [
                    'error' => 'Order with id 42 was not found.',
                ]),
            ],
            summary: 'Get an order',
            description: 'Returns one order and its related articles by numeric order id.',
            parameters: [
                new Parameter('id', 'path', 'Numeric order id.', true, schema: [
                    'type' => 'integer',
                    'minimum' => 1,
                ], example: 42),
            ],
        )));

        $paths->addPath('/api/price', new PathItem(get: new Operation(
            operationId: 'getTileExpertPrice',
            tags: ['Prices'],
            responses: [
                '200' => $this->jsonResponse('Tile.Expert price result.', $this->priceSchema(), [
                    'price' => 19.95,
                    'factory' => 'Ragno',
                    'collection' => 'Woodline',
                    'article' => 'ABC-123',
                ]),
                '400' => $this->jsonResponse('Required query parameters are missing.', $this->errorSchema(), [
                    'error' => 'Query parameters "factory", "collection" and "article" are required.',
                ]),
                '404' => $this->jsonResponse('Tile.Expert product was not found.', $this->errorSchema(), [
                    'error' => 'The requested Tile.Expert product was not found.',
                ]),
                '502' => $this->jsonResponse('Tile.Expert could not be reached or parsed.', $this->errorSchema(), [
                    'error' => 'Tile.Expert returned HTTP 503.',
                ]),
            ],
            summary: 'Get a Tile.Expert product price',
            description: 'Fetches the current Tile.Expert product price for a factory, collection and article.',
            parameters: [
                new Parameter('factory', 'query', 'Tile factory slug or name.', true, schema: ['type' => 'string'], example: 'Ragno'),
                new Parameter('collection', 'query', 'Tile collection slug or name.', true, schema: ['type' => 'string'], example: 'Woodline'),
                new Parameter('article', 'query', 'Tile article code.', true, schema: ['type' => 'string'], example: 'ABC-123'),
            ],
        )));

        $paths->addPath('/soap/orders', new PathItem(post: new Operation(
            operationId: 'createSoapOrder',
            tags: ['SOAP Orders'],
            responses: [
                '200' => $this->xmlResponse('SOAP order was created.', $this->soapCreateOrderResponseExample()),
                '400' => $this->xmlResponse('SOAP client fault.', $this->soapFaultExample('soap:Client', 'SOAP request body is empty.')),
                '500' => $this->xmlResponse('SOAP server fault.', $this->soapFaultExample('soap:Server', 'Order could not be created.')),
            ],
            summary: 'Create an order from SOAP XML',
            description: 'Creates an order from a CreateOrderRequest SOAP envelope and returns a SOAP response.',
            requestBody: new RequestBody(
                description: 'CreateOrderRequest SOAP envelope.',
                content: new \ArrayObject([
                    'text/xml' => new MediaType(
                        schema: $this->schema(['type' => 'string']),
                        example: $this->soapCreateOrderRequestExample(),
                    ),
                ]),
                required: true,
            ),
        )));

        return $openApi;
    }

    private function jsonResponse(string $description, array $schema, array $example): OpenApiResponse
    {
        return new OpenApiResponse(
            description: $description,
            content: new \ArrayObject([
                'application/json' => new MediaType(
                    schema: $this->schema($schema),
                    example: $example,
                ),
            ]),
        );
    }

    private function xmlResponse(string $description, string $example): OpenApiResponse
    {
        return new OpenApiResponse(
            description: $description,
            content: new \ArrayObject([
                'text/xml' => new MediaType(
                    schema: $this->schema(['type' => 'string']),
                    example: $example,
                ),
            ]),
        );
    }

    private function schema(array $schema): \ArrayObject
    {
        return new \ArrayObject($schema);
    }

    private function errorSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['error'],
            'properties' => [
                'error' => ['type' => 'string'],
            ],
        ];
    }

    private function groupedOrdersSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['groupBy', 'page', 'perPage', 'totalItems', 'totalOrders', 'totalPages', 'hasNextPage', 'hasPreviousPage', 'items'],
            'properties' => [
                'groupBy' => ['type' => 'string', 'enum' => ['day', 'month', 'year']],
                'page' => ['type' => 'integer', 'minimum' => 1],
                'perPage' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                'totalItems' => ['type' => 'integer', 'minimum' => 0],
                'totalOrders' => ['type' => 'integer', 'minimum' => 0],
                'totalPages' => ['type' => 'integer', 'minimum' => 1],
                'hasNextPage' => ['type' => 'boolean'],
                'hasPreviousPage' => ['type' => 'boolean'],
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'required' => ['groupValue', 'count'],
                        'properties' => [
                            'groupValue' => ['type' => 'string', 'examples' => ['2026-04']],
                            'count' => ['type' => 'integer', 'minimum' => 0],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function orderSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['articles'],
            'additionalProperties' => true,
            'properties' => [
                'id' => ['type' => 'integer'],
                'articles' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => true,
                    ],
                ],
            ],
        ];
    }

    private function priceSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['price', 'factory', 'collection', 'article'],
            'properties' => [
                'price' => ['type' => 'number', 'format' => 'float'],
                'factory' => ['type' => 'string'],
                'collection' => ['type' => 'string'],
                'article' => ['type' => 'string'],
            ],
        ];
    }

    private function soapCreateOrderRequestExample(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soap:Body>'
            . '<CreateOrderRequest>'
            . '<order>'
            . '<name>Sample order</name>'
            . '<payType>2</payType>'
            . '<locale>en</locale>'
            . '<articles>'
            . '<article>'
            . '<amount>2.5</amount>'
            . '<price>10.75</price>'
            . '<weight>12.3</weight>'
            . '<packagingCount>4</packagingCount>'
            . '<pallet>1</pallet>'
            . '<packaging>2</packaging>'
            . '</article>'
            . '</articles>'
            . '</order>'
            . '</CreateOrderRequest>'
            . '</soap:Body>'
            . '</soap:Envelope>';
    }

    private function soapCreateOrderResponseExample(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soap:Body>'
            . '<CreateOrderResponse>'
            . '<created>true</created>'
            . '<orderId>77</orderId>'
            . '<hash>test-hash</hash>'
            . '<token>test-token</token>'
            . '</CreateOrderResponse>'
            . '</soap:Body>'
            . '</soap:Envelope>';
    }

    private function soapFaultExample(string $faultCode, string $faultString): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soap:Body>'
            . '<soap:Fault>'
            . '<faultcode>'.$faultCode.'</faultcode>'
            . '<faultstring>'.$faultString.'</faultstring>'
            . '</soap:Fault>'
            . '</soap:Body>'
            . '</soap:Envelope>';
    }
}
