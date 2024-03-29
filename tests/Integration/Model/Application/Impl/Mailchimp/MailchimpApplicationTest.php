<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Mailchimp;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\MailchimpApplication;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Document\Webhook;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookSubscription;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use HbPFConnectorsTests\MockCurlMethod;

/**
 * Class MailchimpApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Mailchimp
 */
final class MailchimpApplicationTest extends KernelTestCaseAbstract
{

    use PrivateTrait;

    private const CLIENT_ID = '6748****7235';

    /**
     * @var MailchimpApplication
     */
    private MailchimpApplication $application;

    /**
     * @throws Exception
     */
    public function testAutorize(): void
    {
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall(
            $this->application->getName(),
            'user',
            'token123',
            self::CLIENT_ID,
        );
        self::assertEquals(TRUE, $this->application->isAuthorized($applicationInstall));
        $this->application->authorize($applicationInstall);
    }

    /**
     * @throws Exception
     */
    public function testIsAuthorizedNoToken(): void
    {
        $this->setApplication();
        $applicationInstall = new ApplicationInstall();
        self::assertEquals(FALSE, $this->application->isAuthorized($applicationInstall));
    }

    /**
     * @throws Exception
     */
    public function testWebhookSubscribeRequestDto(): void
    {
        $this->mockCurl(
            [
                new MockCurlMethod(
                    200,
                    'responseDatacenter.json',
                    [],
                ),
            ],
        );
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall(
            $this->application->getName(),
            'user',
            'fa830d8d4308*****c307906e83de659',
        );
        $applicationInstall->addSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM => array_merge(
                    $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM],
                    [MailchimpApplication::AUDIENCE_ID => '2a8******8'],
                ),
                MailchimpApplication::API_KEYPOINT       => $this->application->getApiEndpoint($applicationInstall),
            ],
        );

        $subscription = new WebhookSubscription('test', 'node', 'xxx', ['name' => 0]);

        $request = $this->application->getWebhookSubscribeRequestDto(
            $applicationInstall,
            $subscription,
            sprintf(
                '%s/webhook/topologies/%s/nodes/%s/token/%s',
                rtrim('www.xx.cz', '/'),
                $subscription->getTopology(),
                $subscription->getNode(),
                bin2hex(random_bytes(25)),
            ),
        );

        $requestUn = $this->application->getWebhookUnsubscribeRequestDto(
            $applicationInstall,
            (new Webhook())->setWebhookId('358'),
        );

        self::assertEquals(
            sprintf(
                '%s/3.0/lists/%s/webhooks',
                $applicationInstall->getSettings()[MailchimpApplication::API_KEYPOINT],
                $applicationInstall->getSettings(
                )[ApplicationInterface::AUTHORIZATION_FORM][MailchimpApplication::AUDIENCE_ID],
            ),
            $request->getUriString(),
        );

        self::assertEquals(
            sprintf(
                '%s/3.0/lists/%s/webhooks/358',
                $applicationInstall->getSettings()[MailchimpApplication::API_KEYPOINT],
                $applicationInstall->getSettings(
                )[ApplicationInterface::AUTHORIZATION_FORM][MailchimpApplication::AUDIENCE_ID],
            ),
            $requestUn->getUriString(),
        );
    }

    /**
     * @throws Exception
     */
    public function testPublicName(): void
    {
        $this->setApplication();
        self::assertEquals(
            'Mailchimp',
            $this->application->getPublicName(),
        );
    }

    /**
     * @throws Exception
     */
    public function testGetDescription(): void
    {
        $this->setApplication();
        self::assertEquals(
            'Mailchimp v3',
            $this->application->getDescription(),
        );
    }

    /**
     * @throws Exception
     */
    public function testGetApplicationType(): void
    {
        $this->setApplication();
        self::assertEquals(
            ApplicationTypeEnum::WEBHOOK->value,
            $this->application->getApplicationType(),
        );
    }

    /**
     * @throws Exception
     */
    public function testGetFormStack(): void
    {
        $this->setApplication();
        $forms = $this->application->getFormStack()->getForms();
        foreach ($forms as $form) {
            foreach ($form->getFields() as $field) {
                self::assertContainsEquals(
                    $field->getKey(),
                    [
                        OAuth2ApplicationAbstract::CLIENT_ID,
                        OAuth2ApplicationAbstract::CLIENT_SECRET,
                        MailchimpApplication::AUDIENCE_ID,
                    ],
                );
            }
        }
    }

    /**
     * @throws Exception
     */
    public function testProcessWebhookSubscribeResponse(): void
    {
        $this->setApplication();
        $response = $this->application->processWebhookSubscribeResponse(
            new ResponseDto(200, '', '{"id":"id88"}', []),
            new ApplicationInstall(),
        );
        self::assertEquals('id88', $response);
    }

    /**
     * @throws Exception
     */
    public function testProcessWebhookUnsubscribeResponse(): void
    {
        $this->setApplication();
        $response = $this->application->processWebhookUnsubscribeResponse(
            new ResponseDto(204, '', '{"id":"id88"}', []),
        );
        self::assertEquals(TRUE, $response);
    }

    /**
     * @throws Exception
     */
    public function testGetWebhookSubscriptions(): void
    {
        $this->setApplication();
        $webhookSubcriptions = $this->application->getWebhookSubscriptions();
        foreach ($webhookSubcriptions as $webhookSubscription) {
            self::assertContains(
                $webhookSubscription->getParameters()['name'],
                ['subscribe', 'upemail', 'unsubscribe'],
            );
        }
    }

    /**
     * @throws Exception
     */
    public function testSetAuthorization(): void
    {
        $this->mockCurl(
            [
                new MockCurlMethod(
                    200,
                    'responseDatacenter.json',
                    [],
                ),
            ],
        );
        $providerMock = self::createPartialMock(OAuth2Provider::class, ['getAccessToken']);
        $providerMock->method('getAccessToken')->willReturn(
            [
                'access_token' => 'token333',
                'code'         => 'code123',
            ],
        );
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall(
            $this->application->getName(),
            'user',
            'fa830d8d4308*****c307906e83de659',
            self::CLIENT_ID,
            'secret',
        );
        $this->setProperty($this->application, 'provider', $providerMock);
        $return = $this->application->setAuthorizationToken(
            $applicationInstall,
            [
                'access_token' => 'token333',
                'code'         => 'code123',
            ],
        );
        self::assertEquals(MailchimpApplication::class, $return::class);
        self::assertEquals(
            'https://us3.api.mailchimp.com',
            $applicationInstall->getSettings()[MailchimpApplication::API_KEYPOINT],
        );
        self::assertEquals(
            'code123',
            $applicationInstall->getSettings(
            )[ApplicationInterface::AUTHORIZATION_FORM][MailchimpApplication::TOKEN]['code'],
        );
        self::assertEquals(
            'token333',
            $applicationInstall->getSettings(
            )[ApplicationInterface::AUTHORIZATION_FORM][MailchimpApplication::TOKEN]['access_token'],
        );
    }

    /**
     * @throws Exception
     */
    private function setApplication(): void
    {
        $this->mockRedirect(MailchimpApplication::MAILCHIMP_URL, self::CLIENT_ID);
        $this->application = self::getContainer()->get('hbpf.application.mailchimp');
    }

}
