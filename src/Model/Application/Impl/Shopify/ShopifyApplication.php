<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Shopify;

use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Document\Webhook;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookSubscription;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\Utils\String\Json;
use JsonException;

/**
 * Class ShopifyApplication
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Shopify
 */
final class ShopifyApplication extends BasicApplicationAbstract implements WebhookApplicationInterface
{

    public const  SHOPIFY_URL     = 'myshopify.com/admin/api/';
    public const  SHOPIFY_VERSION = '2020-01';

    /**
     * @return string
     */
    public function getApplicationType(): string
    {
        return ApplicationTypeEnum::WEBHOOK->value;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'shopify';
    }

    /**
     * @return string
     */
    public function getPublicName(): string
    {
        return 'Shopify';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Shopify v1';
    }

    /**
     * @param ProcessDtoAbstract $dto
     * @param ApplicationInstall $applicationInstall
     * @param string             $method
     * @param string|null        $url
     * @param string|null        $data
     *
     * @return RequestDto
     * @throws CurlException
     */
    public function getRequestDto(
        ProcessDtoAbstract $dto,
        ApplicationInstall $applicationInstall,
        string $method,
        ?string $url = NULL,
        ?string $data = NULL,
    ): RequestDto
    {
        $uri     = sprintf('%s%s', $this->getBaseUrl($applicationInstall), $url);
        $request = new RequestDto($this->getUri($uri), $method, $dto);
        $request->setHeaders(
            [
                'Accept'                 => 'application/json',
                'Content-Type'           => 'application/json',
                'X-Shopify-Access-Token' => $this->getPassword($applicationInstall),
            ],
        );

        if (isset($data)) {
            $request->setBody($data);
        }

        return $request;
    }

    /**
     * @return FormStack
     */
    public function getFormStack(): FormStack
    {
        $form = new Form(ApplicationInterface::AUTHORIZATION_FORM, 'Authorization settings');
        $form
            ->addField(new Field(Field::TEXT, BasicApplicationInterface::USER, 'Shop name', NULL, TRUE))
            ->addField(new Field(Field::TEXT, BasicApplicationAbstract::PASSWORD, 'App password', NULL, TRUE));

        $formStack = new FormStack();
        $formStack->addForm($form);

        return $formStack;
    }

    /**
     * @return WebhookSubscription[]
     */
    public function getWebhookSubscriptions(): array
    {
        return [
            new WebhookSubscription('New customer', 'Webhook', '', ['name' => 'customers/create']),
        ];
    }

    /***
     * @param ApplicationInstall  $applicationInstall
     * @param WebhookSubscription $subscription
     * @param string              $url
     *
     * @return RequestDto
     * @throws CurlException
     */
    public function getWebhookSubscribeRequestDto(
        ApplicationInstall $applicationInstall,
        WebhookSubscription $subscription,
        string $url,
    ): RequestDto
    {
        return $this->getRequestDto(
            new ProcessDto(),
            $applicationInstall,
            CurlManager::METHOD_POST,
            '/webhooks.json',
            Json::encode(
                [
                    'webhook' =>
                        [
                            'address' => $url,
                            'format'  => 'json',
                            'topic'   => $subscription->getParameters()['name'],
                        ],
                ],
            ),
        );
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param Webhook            $webhook
     *
     * @return RequestDto
     * @throws CurlException
     */
    public function getWebhookUnsubscribeRequestDto(
        ApplicationInstall $applicationInstall,
        Webhook $webhook,
    ): RequestDto
    {
        return $this->getRequestDto(
            new ProcessDto(),
            $applicationInstall,
            CurlManager::METHOD_DELETE,
            sprintf('/webhooks/%s.json', $webhook->getWebhookId()),
        );
    }

    /**
     * @param ResponseDto        $dto
     * @param ApplicationInstall $install
     *
     * @return string
     * @throws JsonException
     */
    public function processWebhookSubscribeResponse(ResponseDto $dto, ApplicationInstall $install): string
    {
        $install;

        return (string) Json::decode($dto->getBody())['webhook']['id'];
    }

    /**
     * @param ResponseDto $dto
     *
     * @return bool
     */
    public function processWebhookUnsubscribeResponse(ResponseDto $dto): bool
    {
        return $dto->getStatusCode() === 200;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     */
    private function getPassword(ApplicationInstall $applicationInstall): string
    {
        return $applicationInstall->getSettings(
        )[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationInterface::PASSWORD];
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     */
    private function getBaseUrl(ApplicationInstall $applicationInstall): string
    {
        return sprintf(
            'https://%s.%s%s',
            $this->getShopName($applicationInstall),
            self::SHOPIFY_URL,
            self::SHOPIFY_VERSION,
        );
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     */
    private function getShopName(ApplicationInstall $applicationInstall): string
    {
        return $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][self::USER];
    }

}
