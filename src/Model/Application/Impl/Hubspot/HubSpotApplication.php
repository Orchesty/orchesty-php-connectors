<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot;

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
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookSubscription;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Utils\ScopeFormatter;
use Hanaboso\Utils\String\Json;
use JsonException;

/**
 * Class HubSpotApplication
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot
 */
final class HubSpotApplication extends OAuth2ApplicationAbstract implements WebhookApplicationInterface
{

    public const    BASE_URL    = 'https://api.hubapi.com';
    public const    HUBSPOT_URL = 'https://app.hubspot.com/oauth/authorize';
    public const    TOKEN_URL   = 'https://api.hubapi.com/oauth/v1/token';
    public const    APP_ID      = 'app_id';

    protected const SCOPE_SEPARATOR = ScopeFormatter::SPACE;

    private const SCOPES = ['contacts'];

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
        return 'hub-spot';
    }

    /**
     * @return string
     */
    public function getPublicName(): string
    {
        return 'HubSpot Application';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'HubSpot offers a full stack of software for marketing, sales, and customer service, with a completely free CRM at its core. They’re powerful alone — but even better when used together.';
    }

    /**
     * @return string
     */
    public function getAuthUrl(): string
    {
        return self::HUBSPOT_URL;
    }

    /**
     * @return string
     */
    public function getTokenUrl(): string
    {
        return self::TOKEN_URL;
    }

    /**
     * @param ProcessDtoAbstract $dto
     * @param ApplicationInstall $applicationInstall
     * @param string             $method
     * @param string|null        $url
     * @param string|null        $data
     *
     * @return RequestDto
     * @throws ApplicationInstallException
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
        $request = new RequestDto($this->getUri($url ?? self::BASE_URL), $method, $dto);
        $request->setHeaders(
            [
                'Accept'        => 'application/json',
                'Authorization' => sprintf('Bearer %s', $this->getAccessToken($applicationInstall)),
                'Content-Type'  => 'application/json',
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
            ->addField(new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_ID, 'Client Id', NULL, TRUE))
            ->addField(new Field(Field::PASSWORD, OAuth2ApplicationInterface::CLIENT_SECRET, 'Client Secret', TRUE))
            ->addField(new Field(Field::TEXT, self::APP_ID, 'Application Id', NULL, TRUE));

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
            new WebhookSubscription('Create Contact', 'Webhook', '', ['name' => 'contact.creation']),
            new WebhookSubscription('Delete Contact', 'Webhook', '', ['name' => 'contact.deletion']),
        ];
    }

    /**
     * @param ApplicationInstall  $applicationInstall
     * @param WebhookSubscription $subscription
     * @param string              $url
     *
     * @return RequestDto
     * @throws CurlException
     * @throws ApplicationInstallException
     */
    public function getWebhookSubscribeRequestDto(
        ApplicationInstall $applicationInstall,
        WebhookSubscription $subscription,
        string $url,
    ): RequestDto
    {
        $hubspotUrl = sprintf(
            '%s/webhooks/v1/%s/subscriptions',
            self::BASE_URL,
            $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][self::APP_ID],
        );
        $body       = Json::encode(
            [
                'enabled'             => FALSE,
                'subscriptionDetails' => [
                    'propertyName'     => 'email',
                    'subscriptionType' => $subscription->getParameters()['name'],
                ],
                'webhookUrl'          => $url,
            ],
        );

        return $this->getRequestDto(
            new ProcessDto(),
            $applicationInstall,
            CurlManager::METHOD_POST,
            $hubspotUrl,
            $body,
        );
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param Webhook            $webhook
     *
     * @return RequestDto
     * @throws ApplicationInstallException
     * @throws CurlException
     */
    public function getWebhookUnsubscribeRequestDto(
        ApplicationInstall $applicationInstall,
        Webhook $webhook,
    ): RequestDto
    {
        $url = sprintf(
            '%s/webhooks/v1/%s/subscriptions/%s',
            self::BASE_URL,
            $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][self::APP_ID],
            $webhook->getWebhookId(),
        );

        return $this->getRequestDto(new ProcessDto(), $applicationInstall, CurlManager::METHOD_DELETE, $url);
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

        return $dto->getJsonBody()['id'] ?? '';
    }

    /**
     * @param ResponseDto $dto
     *
     * @return bool
     */
    public function processWebhookUnsubscribeResponse(ResponseDto $dto): bool
    {
        return $dto->getStatusCode() === 204;
    }

    /**
     * -------------------------------------------- HELPERS ------------------------------------
     */

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string[]
     */
    protected function getScopes(ApplicationInstall $applicationInstall): array
    {
        $applicationInstall;

        return self::SCOPES;
    }

}
