<?php declare(strict_types=1);

namespace Tests\Live\Model\Application\Impl\Hubspot\Connector;

use Exception;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Connector\HubspotCreateContactConnector;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;

/**
 * Class HubspotCreateContactConnectorTest
 *
 * @package Tests\Live\Model\Application\Impl\Hubspot\Connector
 */
final class HubspotCreateContactConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $app                           = self::$container->get('hbpf.application.hubspot');
        $hubspotCreateContactConnector = new HubspotCreateContactConnector(
            $app,
            self::$container->get('hbpf.transport.curl_manager'),
            $this->dm
        );

        $this->pf(DataProvider::getOauth2AppInstall(
            $app->getKey(),
            'user',
            'CPn036zALRICAQEYu_j2AiDpsIEEKJSQDDIZAAoa8Oq06qseob1dXiP5KB1H3dY5AG0ShToPAAoCQQAADIADAAgAAAABQhkAChrw6vXLFCILvuPoTaMFCHwh43lT3Ura'
        ));
        $this->dm->clear();

        $hubspotCreateContactConnector->processAction(DataProvider::getProcessDto(
            $app->getKey(),
            'user',
            (string) file_get_contents(__DIR__ . sprintf('/Data/contactBody.json'), TRUE)
        ));
    }

}
