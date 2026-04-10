<?php

namespace App\Tests\Controller;

use App\Tests\Double\OrderRepositoryFake;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SoapOrderControllerTest extends WebTestCase
{
    public function testCreateOrderReturnsSoapSuccessResponse(): void
    {
        $client = static::createClient();
        $orderRepository = $this->orderRepository();
        $orderRepository->createdOrderResult = [
            'id' => 77,
            'hash' => 'hash&value',
            'token' => 'token<value>',
        ];

        $client->request('POST', '/soap/orders', [], [], ['CONTENT_TYPE' => 'text/xml'], $this->soapCreateOrderRequest());

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'text/xml; charset=UTF-8');

        $content = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('<created>true</created>', $content);
        self::assertStringContainsString('<orderId>77</orderId>', $content);
        self::assertStringContainsString('<hash>hash&amp;value</hash>', $content);
        self::assertStringContainsString('<token>token&lt;value&gt;</token>', $content);

        self::assertCount(1, $orderRepository->createWithArticlesCalls);
        self::assertSame('Sample order', $orderRepository->createWithArticlesCalls[0]['order']['name']);
        self::assertSame(2, $orderRepository->createWithArticlesCalls[0]['order']['pay_type']);
        self::assertSame('en', $orderRepository->createWithArticlesCalls[0]['order']['locale']);
        self::assertSame('EUR', $orderRepository->createWithArticlesCalls[0]['order']['currency']);

        $article = $orderRepository->createWithArticlesCalls[0]['articles'][0];
        self::assertSame(123, $article['article_id']);
        self::assertSame(2.5, $article['amount']);
        self::assertSame(10.75, $article['price']);
        self::assertSame(1, $article['swimming_pool']);
    }

    public function testCreateOrderReturnsSoapFaultForEmptyBody(): void
    {
        $client = static::createClient();
        $orderRepository = $this->orderRepository();

        $client->request('POST', '/soap/orders', [], [], ['CONTENT_TYPE' => 'text/xml'], '');

        self::assertResponseStatusCodeSame(400);
        self::assertResponseHeaderSame('Content-Type', 'text/xml; charset=UTF-8');

        $content = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('<faultcode>soap:Client</faultcode>', $content);
        self::assertStringContainsString('<faultstring>SOAP request body is empty.</faultstring>', $content);
        self::assertSame([], $orderRepository->createWithArticlesCalls);
    }

    private function orderRepository(): OrderRepositoryFake
    {
        $orderRepository = static::getContainer()->get(OrderRepositoryFake::class);
        self::assertInstanceOf(OrderRepositoryFake::class, $orderRepository);
        $orderRepository->reset();

        return $orderRepository;
    }

    private function soapCreateOrderRequest(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <CreateOrderRequest>
            <order>
                <name>Sample order</name>
                <payType>2</payType>
                <locale>en</locale>
                <currency>EUR</currency>
                <measure>m2</measure>
                <articles>
                    <article>
                        <articleId>123</articleId>
                        <amount>2.5</amount>
                        <price>10.75</price>
                        <weight>12.3</weight>
                        <packagingCount>4</packagingCount>
                        <pallet>1</pallet>
                        <packaging>2</packaging>
                        <swimmingPool>true</swimmingPool>
                    </article>
                </articles>
            </order>
        </CreateOrderRequest>
    </soap:Body>
</soap:Envelope>
XML;
    }
}
