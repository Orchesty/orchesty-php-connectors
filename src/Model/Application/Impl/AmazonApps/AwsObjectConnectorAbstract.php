<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps;

use Doctrine\Persistence\ObjectRepository;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessExceptionTrait;

/**
 * Class AwsObjectConnectorAbstract
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps
 */
abstract class AwsObjectConnectorAbstract extends ConnectorAbstract
{

    use ProcessExceptionTrait;

    protected const QUERY  = 'query';
    protected const RESULT = 'result';

    protected const BUCKET = 'Bucket';
    protected const KEY    = 'Key';
    protected const SOURCE = 'SourceFile';
    protected const TARGET = 'SaveAs';

    protected const NAME    = 'name';
    protected const CONTENT = 'content';

    /**
     * @var ObjectRepository<ApplicationInstall>&ApplicationInstallRepository
     */
    protected ApplicationInstallRepository $repository;

    /**
     * @return string
     */
    abstract protected function getCustomName(): string;

    /**
     * AwsObjectConnectorAbstract constructor.
     */
    public function __construct()
    {}

    /**
     * @param mixed[] $parameters
     * @param mixed[] $content
     *
     * @throws ConnectorException
     */
    protected function checkParameters(array $parameters, array $content): void
    {
        foreach ($parameters as $parameter) {
            if (!isset($content[$parameter])) {
                throw $this->createException("Required parameter '%s' is not provided!", $parameter);
            }
        }
    }

}
