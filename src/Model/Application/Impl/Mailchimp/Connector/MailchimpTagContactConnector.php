<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\Connector;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ObjectRepository;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\MailchimpApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use JsonException;

/**
 * Class MailchimpTagContactConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\Connector
 */
final class MailchimpTagContactConnector extends ConnectorAbstract
{

    /**
     * @var ObjectRepository<ApplicationInstall>&ApplicationInstallRepository
     */
    private ApplicationInstallRepository $repository;

    /**
     * MailchimpTagContactConnector constructor.
     *
     * @param CurlManagerInterface $curlManager
     * @param DocumentManager      $dm
     */
    public function __construct(private CurlManagerInterface $curlManager, DocumentManager $dm)
    {
        $this->repository = $dm->getRepository(ApplicationInstall::class);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'mailchimp_tag_contact';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws PipesFrameworkException
     * @throws ConnectorException
     * @throws JsonException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $applicationInstall = $this->repository->findUserAppByHeaders($dto);
        $apiEndpoint        = $applicationInstall->getSettings()[MailchimpApplication::API_KEYPOINT];

        $return = $this->curlManager->send(
            $this->getApplication()->getRequestDto(
                $applicationInstall,
                CurlManager::METHOD_POST,
                sprintf(
                    '%s/3.0/lists/%s/segments/%s/members',
                    $apiEndpoint,
                    $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][MailchimpApplication::AUDIENCE_ID],
                    $applicationInstall->getSettings()[MailchimpApplication::SEGMENT_ID],
                ),
                $dto->getData(),
            ),
        );

        $json       = $return->getJsonBody();
        $statusCode = $return->getStatusCode();
        $this->evaluateStatusCode($statusCode, $dto);

        return $this->setJsonContent($dto, $json);
    }

}
