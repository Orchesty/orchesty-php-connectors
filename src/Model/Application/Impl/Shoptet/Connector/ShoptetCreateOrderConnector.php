<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector;

use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFAppStore\Document\Synchronization;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessExceptionTrait;

/**
 * Class ShoptetCreateOrderConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector
 */
final class ShoptetCreateOrderConnector extends ShoptetConnectorAbstract
{

    use ProcessExceptionTrait;

    public const NAME = 'shoptet-create-order';

    private const URL   = '/api/orders';
    private const CODE  = 'code';
    private const ORDER = 'order';

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
     * @throws ConnectorException
     * @throws OnRepeatException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $applicationInstall = $this->getApplicationInstallFromProcess($dto);

        try {
            $response = $this->processResponse(
                $this->getSender()->send(
                    $this->getApplication()->getRequestDto(
                        $applicationInstall,
                        CurlManager::METHOD_POST,
                        sprintf('%s%s', $this->host, self::URL),
                        $dto->getData(),
                    )->setDebugInfo($dto),
                )->getJsonBody(),
                $dto,
            );

            $externalId = $response[self::DATA][self::ORDER][self::CODE];
            $dto->addHeader(Synchronization::EXTERNAL_ID_HEADER, $externalId);

            return $dto->setJsonData($response);
        } catch (CurlException $e) {
            throw $this->createRepeatException($dto, $e, self::REPEATER_INTERVAL);
        }
    }

}
