<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\CustomNode;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\HbPFAppStore\Loader\ApplicationLoader;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract;

/**
 * Class RefreshOAuth2TokenCustomNode
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\CustomNode
 */
class RefreshOAuth2TokenCustomNode extends CustomNodeAbstract
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var ApplicationLoader
     */
    private $loader;

    /**
     * @var ObjectRepository|ApplicationInstallRepository
     */
    private $repository;

    /**
     * @param DocumentManager   $dm
     * @param ApplicationLoader $loader
     */
    public function __construct(DocumentManager $dm, ApplicationLoader $loader)
    {
        $this->dm         = $dm;
        $this->repository = $dm->getRepository(ApplicationInstall::class);
        $this->loader     = $loader;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ApplicationInstallException
     * @throws AuthorizationException
     * @throws DateTimeException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $applicationId = PipesHeaders::get(GetApplicationForRefreshBatch::APPLICATION_ID, $dto->getHeaders());

        /** @var ApplicationInstall $applicationInstall */
        $applicationInstall = $this->repository->findOneBy(['_id' => $applicationId]);

        /** @var OAuth2ApplicationAbstract $application */
        $application = $this->loader->getApplication($applicationInstall->getKey());

        $application->refreshAuthorization($applicationInstall);

        $this->dm->flush();

        return $dto;
    }

}