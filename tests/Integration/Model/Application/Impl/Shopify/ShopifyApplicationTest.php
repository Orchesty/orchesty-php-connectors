<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Shopify;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shopify\ShopifyApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Document\Webhook;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\Utils\File\File;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;

/**
 * Class ShopifyApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Shopify
 */
final class ShopifyApplicationTest extends KernelTestCaseAbstract
{

    private const ESHOP_NAME = 'hana1';
    private const PASSWORD   = '079a9710da9264428749be8a148*****';

    /**
     * @var ShopifyApplication
     */
    private ShopifyApplication $application;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shopify\ShopifyApplication
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shopify\ShopifyApplication::getApplicationType
     */
    public function testGetApplicationType(): void
    {
        self::assertEquals(ApplicationTypeEnum::WEBHOOK->value, $this->application->getApplicationType());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shopify\ShopifyApplication::getName
     */
    public function testGetKey(): void
    {
        self::assertEquals('shopify', $this->application->getName());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shopify\ShopifyApplication::getPublicName
     */
    public function testPublicName(): void
    {
        self::assertEquals('Shopify', $this->application->getPublicName());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shopify\ShopifyApplication::getDescription
     */
    public function testGetDescription(): void
    {
        self::assertEquals('Shopify v1', $this->application->getDescription());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shopify\ShopifyApplication::getRequestDto
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shopify\ShopifyApplication::getBaseUrl
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shopify\ShopifyApplication::getPassword
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shopify\ShopifyApplication::getShopName
     *
     * @throws Exception
     */
    public function testGetRequestDto(): void
    {
        $applicationInstall = $this->createApplication();

        $request = $this->application->getRequestDto(
            new ProcessDto(),
            $applicationInstall,
            CurlManager::METHOD_POST,
            '/customers.json',
            File::getContent(__DIR__ . '/data/createCustomer.json'),
        );

        self::assertEquals('https://hana1.myshopify.com/admin/api/2020-01/customers.json', $request->getUri());
        self::assertEquals(
            [
                'Accept'                 => 'application/json',
                'Content-Type'           => 'application/json',
                'X-Shopify-Access-Token' => '079a9710da9264428749be8a148*****',
            ],
            $request->getHeaders(),
        );
        self::assertEquals(File::getContent(__DIR__ . '/data/createCustomer.json'), $request->getBody());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shopify\ShopifyApplication::getFormStack
     *
     * @throws Exception
     */
    public function testGetFormStack(): void
    {
        $forms = $this->application->getFormStack()->getForms();
        foreach ($forms as $form) {
            foreach ($form->getFields() as $field) {
                self::assertContains(
                    $field->getKey(),
                    [
                        BasicApplicationInterface::USER,
                        BasicApplicationInterface::PASSWORD,
                    ],
                );
            }
        }
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shopify\ShopifyApplication::getWebhookSubscriptions
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shopify\ShopifyApplication::getWebhookSubscribeRequestDto
     *
     * @throws Exception
     */
    public function testWebhookSubscribeRequestDto(): void
    {
        $applicationInstall = $this->createApplication();

        $dto = $this->application->getWebhookSubscribeRequestDto(
            $applicationInstall,
            $this->application->getWebhookSubscriptions()[0],
            'https://www.seznam.cz',
        );

        self::assertEquals('https://hana1.myshopify.com/admin/api/2020-01/webhooks.json', $dto->getUri());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shopify\ShopifyApplication::getWebhookUnsubscribeRequestDto
     * @throws CurlException
     * @throws Exception
     */
    public function testGetWebhookUnsubscribeRequestDto(): void
    {
        $applicationInstall = $this->createApplication();
        $dto                = $this->application->getWebhookUnsubscribeRequestDto(
            $applicationInstall,
            (new Webhook())->setWebhookId('759136321675'),
        );

        self::assertEquals('https://hana1.myshopify.com/admin/api/2020-01/webhooks/759136321675.json', $dto->getUri());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shopify\ShopifyApplication::processWebhookSubscribeResponse
     *
     * @throws Exception
     */
    public function testProcessWebhookSubscribeResponse(): void
    {
        $response = $this->application->processWebhookSubscribeResponse(
            new ResponseDto(200, '', File::getContent(__DIR__ . '/data/createWebhookResponse.json'), []),
            new ApplicationInstall(),
        );
        self::assertEquals('1047897672', $response);
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Shopify\ShopifyApplication::processWebhookUnsubscribeResponse
     */
    public function testProcessWebhookUnsubscribeResponse(): void
    {
        $response = $this->application->processWebhookUnsubscribeResponse(
            new ResponseDto(200, '', '', []),
        );
        self::assertEquals(200, $response);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->application = self::getContainer()->get('hbpf.application.shopify');
    }

    /**
     * @return ApplicationInstall
     * @throws Exception
     */
    private function createApplication(): ApplicationInstall
    {
        $applicationInstall = DataProvider::getBasicAppInstall($this->application->getName());

        $applicationInstall->setSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM =>
                    [
                        BasicApplicationInterface::PASSWORD => self::PASSWORD,
                        BasicApplicationInterface::USER     => self::ESHOP_NAME,
                    ],
            ],
        );

        return $applicationInstall;
    }

}
