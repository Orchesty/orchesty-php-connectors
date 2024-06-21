<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Fakturoid;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\FakturoidApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class FakturoidApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Fakturoid
 */
#[CoversClass(FakturoidApplication::class)]
final class FakturoidApplicationTest extends KernelTestCaseAbstract
{

    /**
     * @var FakturoidApplication
     */
    private FakturoidApplication $app;

    /**
     * @throws Exception
     */
    public function testGetKey(): void
    {
        self::assertEquals('fakturoid', $this->app->getName());
    }

    /**
     * @throws Exception
     */
    public function testGetPublicName(): void
    {
        self::assertEquals('Fakturoid aplication', $this->app->getPublicName());
    }

    /**
     * @throws Exception
     */
    public function testGetDescription(): void
    {
        self::assertEquals('Fakturoid aplication', $this->app->getDescription());
    }

    /**
     * @throws Exception
     */
    public function testGetFormStack(): void
    {
        $forms = $this->app->getFormStack()->getForms();
        foreach ($forms as $form) {
            self::assertCount(3, $form->getFields());
        }
    }

    /**
     * @throws Exception
     */
    public function testIsAuthorized(): void
    {
        $applicationInstall = new ApplicationInstall();
        $applicationInstall->setSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM => [
                    BasicApplicationInterface::PASSWORD => 'cf4*****191bbef40dcd86*****625ec4c4*****',
                    BasicApplicationInterface::USER     => 'hana******.com',
                    FakturoidApplication::ACCOUNT       => 'test',
                ],
            ],
        );
        self::assertTrue($this->app->isAuthorized($applicationInstall));
    }

    /**
     * @throws Exception
     */
    public function testGetRequestDtoWithData(): void
    {
        $app                = self::getContainer()->get('hbpf.application.fakturoid');
        $applicationInstall = new ApplicationInstall();
        $applicationInstall->setSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM => [
                    BasicApplicationInterface::PASSWORD => 'cf4*****191bbef40dcd86*****625ec4c4*****',
                    BasicApplicationInterface::USER     => 'hana******.com',
                    FakturoidApplication::ACCOUNT       => 'test',
                ],
            ],
        );
        $res = $app->getRequestDto(
            new ProcessDto(),
            $applicationInstall,
            CurlManager::METHOD_POST,
            'https://app.fakturoid.cz/api/v2',
            '{
                                "subdomain": "fakturacnitest"
                                }',
        );

        self::assertEquals('https://app.fakturoid.cz/api/v2', $res->getUri(TRUE));
    }

    /**
     * -------------------------------------------- HELPERS ------------------------------------
     */

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->app = self::getContainer()->get('hbpf.application.fakturoid');
    }

}
