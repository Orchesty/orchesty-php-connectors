<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector;

use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Utils\ProcessHeaderTrait;
use Hanaboso\Utils\Traits\UrlBuilderTrait;

/**
 * Class ShoptetConnectorAbstract
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector
 */
abstract class ShoptetConnectorAbstract extends ConnectorAbstract
{

    use UrlBuilderTrait;
    use ProcessHeaderTrait;

    protected const ID                = 'id';
    protected const TYPE              = 'type';
    protected const DATA              = 'data';
    protected const REPEATER_INTERVAL = 5_000;

    /**
     * ShoptetConnectorAbstract constructor.
     */
    public function __construct()
    {
        $this->host = ShoptetApplication::SHOPTET_URL;
    }

    /**
     * @param mixed[]    $data
     * @param ProcessDto $dto
     *
     * @return mixed[]
     * @throws ConnectorException
     * @throws OnRepeatException
     */
    protected function processResponse(array $data, ProcessDto $dto): array
    {
        $isRepeatable = FALSE;

        if (isset($data['errors'])) {
            $exception = $this->createException(
                implode(
                    PHP_EOL,
                    array_map(
                        static function (array $message) use (&$isRepeatable): string {
                            if ($message['instance'] === 'url-locked') {
                                $isRepeatable = TRUE;
                            }

                            return sprintf('%s: %s', $message['errorCode'], $message['message']);
                        },
                        $data['errors'],
                    ),
                ),
            );

            if ($isRepeatable) {
                throw $this->createRepeatException($dto, $exception, self::REPEATER_INTERVAL, self::REPEATER_INTERVAL);
            }

            throw $exception;
        }

        return $data;
    }

}
