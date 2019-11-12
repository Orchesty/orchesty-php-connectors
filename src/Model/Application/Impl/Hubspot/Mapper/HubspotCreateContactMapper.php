<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Mapper;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract;

/**
 * Class HubspotCreateContactMapper
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Mapper
 */
class HubspotCreateContactMapper extends CustomNodeAbstract
{

    /**
     * @param array $data
     *
     * @return array
     */
    private function createBody(array $data): array
    {
        $array  = [];
        $return = [];
        $fields = $this->requestedFields();
        $data   = $this->formatData($data);
        $i      = 0;

        foreach ($fields as $key => $field) {
            $array[$i]['property'] = $key;
            if (key_exists($field, $data)) {
                $array[$i]['value'] = $data[$field];
            } else if (key_exists($field, $data['billTo']) && $data['billTo'][$field] !== NULL) {
                $array[$i]['value'] = $data['billTo'][$field];
            } else if (key_exists($field, $data['shipTo'])) {
                $array[$i]['value'] = $data['shipTo'][$field];
            } else {
                $array[$i]['value'] = NULL;
            }
            $i++;
        }

        $return['properties'] = $array;

        return $return;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function formatData(array $data): array
    {
        $data['billTo']['streets'] = implode(
            ', ',
            array_filter(
                [
                    $data['billTo']['street1'] ?? NULL,
                    $data['billTo']['street2'] ?? NULL,
                    $data['billTo']['street3'] ?? NULL,
                ]
            )
        );

        $data['billTo']['streets'] = implode(
            ',',
            array_filter(
                [
                    $data['shipTo']['street1'] ?? NULL,
                    $data['shipTo']['street2'] ?? NULL,
                    $data['shipTo']['street3'] ?? NULL,
                ]
            )
        );

        $data['shipTo']['firstName'] = $this->splitName($data['shipTo']['name'] ?? NULL)[0];
        $data['shipTo']['lastName']  = $this->splitName($data['shipTo']['name'] ?? NULL)[1];

        $data['billTo']['firstName'] = $this->splitName($data['billTo']['name'] ?? NULL)[0];
        $data['billTo']['lastName']  = $this->splitName($data['billTo']['name'] ?? NULL)[1];

        return $data;
    }

    /**
     * @param string $name
     *
     * @return array
     */
    private function splitName(?string $name): array
    {
        if (!isset($name)) {
            return [NULL, NULL];
        }
        $name      = trim($name);
        $lastName  = strpos($name, ' ') === FALSE ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
        $firstName = trim((string) preg_replace(sprintf('#%s#', $lastName), '', $name));

        return [$firstName, $lastName];
    }

    /**
     * @return array
     *
     * keys (on the left) are required Hubspot fields, values (on the right) are provided Shipstation fields
     * Shipstation fields are processed in formatData method
     */
    private function requestedFields(): array
    {
        return [
            'email'     => 'customerEmail',
            'firstname' => 'firstName',
            'lastname'  => 'lastName',
            'website'   => '',
            'company'   => 'company',
            'phone'     => 'phone',
            'address'   => 'streets',
            'city'      => 'city',
            'state'     => 'state',
            'zip'       => 'postalCode',
        ];
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws PipesFrameworkException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $body = json_decode($dto->getData(), TRUE, 512, JSON_THROW_ON_ERROR)['orders'][0] ?? NULL;

        if (!$body) {
            $message = 'The body of ProcessDto couldnt be decoded from json.';
            $dto->setStopProcess(ProcessDto::STOP_AND_FAILED, $message);

            return $dto;
        }

        $dto->setData((string) json_encode($this->createBody((array) $body), JSON_THROW_ON_ERROR, 512));

        return $dto;
    }

}