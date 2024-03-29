<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Zoho;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Zoho\ZohoApplication;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;

/**
 * Class ZohoApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Zoho
 */
final class ZohoApplicationTest extends KernelTestCaseAbstract
{

    private const CLIENT_ID = '123';

    /**
     * @var ZohoApplication
     */
    private ZohoApplication $application;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zoho\ZohoApplication::getApplicationType
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
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zoho\ZohoApplication::getName
     */
    public function testGetKey(): void
    {
        $this->setApplication();
        self::assertEquals(
            'zoho',
            $this->application->getName(),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zoho\ZohoApplication::getPublicName
     */
    public function testGetPublicName(): void
    {
        $this->setApplication();
        self::assertEquals(
            'Zoho',
            $this->application->getPublicName(),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zoho\ZohoApplication::getDescription
     */
    public function testGetDescription(): void
    {
        $this->setApplication();
        self::assertEquals(
            'Zoho is a provider of a Customer Relationship Management (CRM) solution',
            $this->application->getDescription(),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zoho\ZohoApplication::getFormStack
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
                    [OAuth2ApplicationAbstract::CLIENT_ID, OAuth2ApplicationAbstract::CLIENT_SECRET],
                );
            }
        }
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zoho\ZohoApplication::getRequestDto
     *
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
            'https://www.zohoapis.com/crm/v2/settings/modules',
            'data',
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
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zoho\ZohoApplication::getAuthUrl
     */
    public function testGetAuthUrl(): void
    {
        $this->setApplication();
        self::assertEquals(
            'https://accounts.zoho.eu/oauth/v2/auth',
            $this->application->getAuthUrl(),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zoho\ZohoApplication::getTokenUrl
     */
    public function testGetTokenUrl(): void
    {
        $this->setApplication();
        self::assertEquals(
            'https://accounts.zoho.eu/oauth/v2/token',
            $this->application->getTokenUrl(),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zoho\ZohoApplication::authorize
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Zoho\ZohoApplication::getScopes
     * @throws Exception
     */
    public function testAuthorize(): void
    {
        $this->setApplication();
        $applicationInstall = DataProvider::getOauth2AppInstall(
            $this->application->getName(),
            'user',
            'token123',
            self::CLIENT_ID,
        );
        self::assertTrue($this->application->isAuthorized($applicationInstall));
        $this->application->authorize($applicationInstall);
    }

    /**
     *
     */
    private function setApplication(): void
    {
        $this->mockRedirect(
            'https://accounts.zoho.eu/oauth/v2/auth',
            self::CLIENT_ID,
            'ZohoCRM.modules.ALL ZohoCRM.settings.ALL',
        );
        $this->application = self::getContainer()->get('hbpf.application.zoho');
    }

}
