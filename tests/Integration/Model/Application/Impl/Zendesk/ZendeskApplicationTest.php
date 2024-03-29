<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Zendesk;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Zendesk\ZendeskApplication;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use ReflectionException;

/**
 * Class ZendeskApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Zendesk
 */
final class ZendeskApplicationTest extends KernelTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @var ZendeskApplication
     */
    private ZendeskApplication $application;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zendesk\ZendeskApplication::getApplicationType
     */
    public function testGetApplicationType(): void
    {
        $this->setApplication();
        self::assertEquals(
            ApplicationTypeEnum::CRON->value,
            $this->application->getApplicationType(),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zendesk\ZendeskApplication::getName
     */
    public function testGetKey(): void
    {
        $this->setApplication();
        self::assertEquals(
            'zendesk',
            $this->application->getName(),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zendesk\ZendeskApplication::getPublicName
     */
    public function testGetPublicName(): void
    {
        $this->setApplication();
        self::assertEquals(
            'Zendesk',
            $this->application->getPublicName(),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zendesk\ZendeskApplication::getDescription
     */
    public function testGetDescription(): void
    {
        $this->setApplication();
        self::assertEquals(
            'Zendesk is a customer support software. It helps companies and organisations manage customer queries and problems through a ticketing system.',
            $this->application->getDescription(),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zendesk\ZendeskApplication::getFormStack
     *
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
                        'subdomain',
                    ],
                );
            }
        }
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zendesk\ZendeskApplication::getRequestDto
     *
     * @throws CurlException
     * @throws ApplicationInstallException
     * @throws Exception
     */
    public function testGetRequestDto(): void
    {
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall($this->application->getName());

        $dto = $this->application->getRequestDto(
            new ProcessDto(),
            $applicationInstall,
            CurlManager::METHOD_POST,
            'https://hanaboso.zendesk.com/api/v2/users',
            'body',
        );

        self::assertEquals(
            [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer token123',
                'Content-Type'  => 'application/json',
            ],
            $dto->getHeaders(),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zendesk\ZendeskApplication::getAuthUrlWithSubdomain
     *
     * @throws Exception
     */
    public function testGetAuthUrlWithSubdomain(): void
    {
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall($this->application->getName())
            ->setSettings([ApplicationInterface::AUTHORIZATION_FORM => ['subdomain' => 'domain123']]);

        $authUrl = $this->application->getAuthUrlWithSubdomain($applicationInstall);

        self::assertEquals('https://domain123.zendesk.com/oauth/authorizations/new', $authUrl);
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zendesk\ZendeskApplication::getTokenUrlWithSubdomain
     *
     * @throws Exception
     */
    public function testGetTokenUrlWithDomain(): void
    {
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall($this->application->getName())
            ->addSettings([ApplicationInterface::AUTHORIZATION_FORM => ['subdomain' => 'domain123']]);

        $authUrl = $this->application->getTokenUrlWithSubdomain($applicationInstall);

        self::assertEquals('https://domain123.zendesk.com/oauth/tokens', $authUrl);
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zendesk\ZendeskApplication::authorize
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zendesk\ZendeskApplication::getScopes
     *
     * @throws Exception
     */
    public function testAuthorize(): void
    {
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall($this->application->getName());
        $applicationInstall->addSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM => array_merge(
                    $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM],
                    ['subdomain' => 'domain123'],
                ),
            ],
        );

        $this->application->authorize($applicationInstall);
        self::assertTrue($this->application->isAuthorized($applicationInstall));
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zendesk\ZendeskApplication::getAuthUrl
     */
    public function testGetAuthUrl(): void
    {
        $this->setApplication();
        self::assertEquals('', $this->application->getAuthUrl());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zendesk\ZendeskApplication::getTokenUrl
     */
    public function testGetTokenUrl(): void
    {
        $this->setApplication();
        self::assertEquals('', $this->application->getTokenUrl());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zendesk\ZendeskApplication::createDto
     * @throws ReflectionException
     * @throws Exception
     */
    public function testCreateDto(): void
    {
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall($this->application->getName())
            ->addSettings([ApplicationInterface::AUTHORIZATION_FORM => ['subdomain' => 'domain123']]);

        $crateDto = $this->invokeMethod(
            $this->application,
            'createDto',
            [$applicationInstall, 'https://127.0.0.66/api/applications/authorize/token'],
        );

        self::assertEquals('https://127.0.0.66/api/applications/authorize/token', $crateDto->getRedirectUrl());
    }

    /**
     *
     */
    private function setApplication(): void
    {
        $this->mockRedirect('https://domain123.zendesk.com/oauth/authorizations/new', 'clientId', 'read write');
        $this->application = self::getContainer()->get('hbpf.application.zendesk');
    }

}
