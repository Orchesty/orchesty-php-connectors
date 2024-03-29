<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Bigcommerce;

use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Utils\ScopeFormatter;

/**
 * Class BigcommerceApplication
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Bigcommerce
 */
final class BigcommerceApplication extends OAuth2ApplicationAbstract
{

    public const    BIGCOMMERCE_URL = 'https://login.bigcommerce.com/oauth2/authorize';
    public const    TOKEN_URL       = 'https://login.bigcommerce.com/oauth2/token';

    protected const SCOPE_SEPARATOR = ScopeFormatter::SPACE;

    private const  SCOPES = ['store_v2_products'];

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'bigcommerce';
    }

    /**
     * @return string
     */
    public function getPublicName(): string
    {
        return 'Bigcommerce';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Bigcommerce v1';
    }

    /**
     * @return string
     */
    public function getAuthUrl(): string
    {
        return self::BIGCOMMERCE_URL;
    }

    /**
     * @return string
     */
    public function getTokenUrl(): string
    {
        return self::TOKEN_URL;
    }

    /**
     * @param ProcessDtoAbstract $dto
     * @param ApplicationInstall $applicationInstall
     * @param string             $method
     * @param string|null        $url
     * @param string|null        $data
     *
     * @return RequestDto
     * @throws ApplicationInstallException
     * @throws CurlException
     */
    public function getRequestDto(
        ProcessDtoAbstract $dto,
        ApplicationInstall $applicationInstall,
        string $method,
        ?string $url = NULL,
        ?string $data = NULL,
    ): RequestDto
    {
        $request = new RequestDto($this->getUri($url), $method, $dto);
        $request->setHeaders(
            [
                'Accept'        => 'application/json',
                'Authorization' => sprintf('OAuth %s', $this->getAccessToken($applicationInstall)),
                'Content-Type'  => 'application/json',
            ],
        );

        if (!empty($data)) {
            $request->setBody($data);
        }

        return $request;
    }

    /**
     * @return FormStack
     */
    public function getFormStack(): FormStack
    {
        $form = new Form(ApplicationInterface::AUTHORIZATION_FORM, 'Authorization settings');
        $form->addField(new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_ID, 'Client Id', NULL, TRUE));
        $form->addField(new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_SECRET, 'Client Secret', NULL, TRUE));

        $formStack = new FormStack();
        $formStack->addForm($form);

        return $formStack;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string[]
     */
    protected function getScopes(ApplicationInstall $applicationInstall): array
    {
        $applicationInstall;

        return self::SCOPES;
    }

}
