<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\SendGrid\Connector;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\SendGrid\SendGridApplication;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\String\Json;

/**
 * Class SendGridSendEmailConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\SendGrid\Connector
 */
final class SendGridSendEmailConnector extends ConnectorAbstract
{

    public const NAME = 'send-grid.send-email';

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
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {

        $applicationInstall = $this->getApplicationInstallFromProcess($dto);
        $data               = $dto->getJsonData();
        if (!isset($data['email'], $data['name'], $data['subject'])) {
            throw new ConnectorException('Some data is missing. Keys [email, name, subject] is required.');
        }

        $body = [
            'from'             => [
                'email' => 'noreply@johndoe.com',
                'name'  => 'John Doe',
            ],
            'personalizations' => [
                [
                    'subject' => $data['subject'],
                    'to'      => [
                        [
                            'email' => $data['email'],
                            'name'  => $data['name'],
                        ],
                    ],
                ],
            ],
            'reply_to'         => [
                'email' => 'noreply@johndoe.com',
                'name'  => 'John Doe',
            ],
            'template_id'      => '1',
        ];

        $url     = sprintf('%s/mail/send', SendGridApplication::BASE_URL);
        $request = $this->getApplication()
            ->getRequestDto($dto,$applicationInstall, CurlManager::METHOD_POST, $url, Json::encode($body));

        try {
            $response = $this->getSender()->send($request);

            if (!$this->evaluateStatusCode($response->getStatusCode(), $dto)) {
                return $dto;
            }
        } catch (CurlException|PipesFrameworkException $e) {
            throw new ConnectorException($e->getMessage(), $e->getCode(), $e);
        }

        return $dto->setData($response->getBody());
    }

}
