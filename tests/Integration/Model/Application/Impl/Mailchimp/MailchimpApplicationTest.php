<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Mailchimp;

use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\MailchimpApplication;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;

/**
 * Class MailchimpApplicationTest
 *
 * @package Tests\Integration\Model\Application\Impl\Mailchimp
 */
final class MailchimpApplicationTest extends DatabaseTestCaseAbstract
{

    private const CLIENT_ID = '105786712126';

    /**
     * @throws DateTimeException
     */
    public function testAutorize(): void
    {
        $this->mockRedirect(MailchimpApplication::MAILCHIMP_URL, self::CLIENT_ID);
        $mailchimpApplication = self::$container->get('hbpf.application.mailchimp');
        $applicationInstall   = DataProvider::getOauth2AppInstall(
            'mailchimp',
            'user',
            'token123',
            self::CLIENT_ID,
            );
        $this->pf($applicationInstall);
        $mailchimpApplication->authorize($applicationInstall);
    }

}
