<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\OAuth2\CustomNode;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\DateTimeUtils;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class GetApplicationForRefreshBatchTest
 *
 * @package Tests\Integration\Model\Application\Impl\OAuth2\CustomNode
 */
class GetApplicationForRefreshBatchTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers GetApplicationForRefreshBatch::process
     * @throws Exception
     */
    public function testProcessBatch(): void
    {
        $this->pf((new ApplicationInstall())->setExpires(DateTimeUtils::getUtcDateTime()));

        $this->assertBatch(
            self::$container->get('hbpf.custom_node.get_application_for_refresh_batch'),
            new ProcessDto(),
            function (SuccessMessage $successMessage): void {
                self::assertEquals('', $successMessage->getData());
            }
        );
    }

}
