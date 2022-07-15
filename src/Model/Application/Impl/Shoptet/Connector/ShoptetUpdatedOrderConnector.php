<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector;

use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Utils\ProcessContentTrait;
use Hanaboso\Utils\System\PipesHeaders;

/**
 * Class ShoptetUpdatedOrderConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector
 */
final class ShoptetUpdatedOrderConnector extends ShoptetConnectorAbstract
{

    use ProcessContentTrait;

    public const NAME = 'shoptet-updated-order-connector';

    private const URL = 'api/orders/%s?include=notes';

    private const EVENT_INSTANCE = 'eventInstance';
    private const ORDER          = 'order';

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
        $dto
            ->addHeader(PipesHeaders::USER, (string) $this->getContentByKey($dto, 'eshopId'))
            ->addHeader(PipesHeaders::APPLICATION, ShoptetApplication::SHOPTET_KEY);

        try {
            $data = $this->processResponse(
                $this->getSender()->send(
                    $this->getApplication()->getRequestDto(
                        $this->getApplicationInstallFromProcess($dto),
                        CurlManager::METHOD_GET,
                        $this->getUrl(self::URL, $this->getContentByKey($dto, self::EVENT_INSTANCE)),
                    )->setDebugInfo($dto),
                )->getJsonBody(),
                $dto,
            )[self::DATA][self::ORDER];

            return $dto->setJsonData($data);
        } catch (CurlException $e) {
            throw $this->createRepeatException($dto, $e);
        }
    }

}
