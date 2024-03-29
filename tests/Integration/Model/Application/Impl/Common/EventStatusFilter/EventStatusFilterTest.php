<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Common\EventStatusFilter;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Common\Events\EventEnum;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Common\EventStatusFilter\EventStatusFilter;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use HbPFConnectorsTests\KernelTestCaseAbstract;

/**
 * Class EventStatusFilterTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Common\EventStatusFilter
 */
final class EventStatusFilterTest extends KernelTestCaseAbstract
{

    /**
     * @return void
     * @throws PipesFrameworkException
     */
    public function testProcessAction(): void
    {
        $eventStatusFilter = new EventStatusFilter(
            EventEnum::PROCESS_SUCCESS->value,
            self::getContainer()->get('hbpf.application_install.repository'),
        );
        $dto               = new ProcessDto();

        $dto->setJsonData(['type' => EventEnum::PROCESS_SUCCESS->value]);
        $dto = $eventStatusFilter->processAction($dto);

        self::assertEquals(0, sizeof($dto->getHeaders()));

        $dto->setJsonData(['type' => EventEnum::PROCESS_FAILED->value]);
        $dto = $eventStatusFilter->processAction($dto);

        self::assertEquals(
            [
                'result-code'    => '1003',
                'result-message' => 'Filtered out!',
            ],
            $dto->getHeaders(),
        );
    }

    /**
     * @return void
     */
    public function testGetName(): void
    {
        $eventStatusFilter = new EventStatusFilter(
            EventEnum::PROCESS_SUCCESS->value,
            self::getContainer()->get('hbpf.application_install.repository'),
        );
        self::assertEquals(
            'event-status-filter',
            $eventStatusFilter->getName(),
        );
    }

}
