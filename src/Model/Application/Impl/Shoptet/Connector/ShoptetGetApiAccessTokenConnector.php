<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;

/**
 * Class ShoptetGetApiAccessTokenConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector
 */
final class ShoptetGetApiAccessTokenConnector extends ShoptetConnectorAbstract
{

    public const NAME = 'shoptet-get-access-token';

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ApplicationInstallException
     * @throws CurlException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $applicationInstall = $this->getApplicationInstallFromProcess($dto);
        $response           = $this->processActionArray($applicationInstall, $dto);

        return $dto->setJsonData($response);
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param ProcessDto|null    $processDto
     *
     * @return mixed[]
     * @throws ApplicationInstallException
     * @throws CurlException
     */
    public function processActionArray(ApplicationInstall $applicationInstall, ?ProcessDto $processDto = NULL): array
    {
        /** @var ShoptetApplication $application */
        $application = $this->application;
        $requestDto  = $application->getApiTokenDto($applicationInstall);
        if ($processDto) {
            $requestDto->setDebugInfo($processDto);
        }

        return $this->getSender()->send($requestDto)->getJsonBody();
    }

}
