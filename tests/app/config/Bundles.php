<?php declare(strict_types=1);

use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use Hanaboso\CommonsBundle\HbPFCommonsBundle;
use Hanaboso\HbPFAppStore\HbPFAppStoreBundle;
use Hanaboso\HbPFConnectors\HbPFConnectorsBundle;
use Hanaboso\PipesPhpSdk\HbPFApplicationBundle\HbPFApplicationBundle;
use Hanaboso\PipesPhpSdk\HbPFConnectorBundle\HbPFConnectorBundle;
use Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\HbPFCustomNodeBundle;
use Hanaboso\PipesPhpSdk\HbPFTableParserBundle\HbPFTableParserBundle;
use RabbitMqBundle\RabbitMqBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;

return [
    DebugBundle::class           => ['dev' => TRUE, 'test' => TRUE],
    DoctrineMongoDBBundle::class => ['all' => TRUE],
    FrameworkBundle::class       => ['all' => TRUE],
    MonologBundle::class         => ['all' => TRUE],
    SecurityBundle::class        => ['all' => TRUE],

    HbPFApplicationBundle::class     => ['all' => TRUE],
    HbPFAppStoreBundle::class        => ['all' => TRUE],
    HbPFCommonsBundle::class         => ['all' => TRUE],
    HbPFConnectorBundle::class       => ['all' => TRUE],
    HbPFConnectorsBundle::class      => ['all' => TRUE],
    HbPFCustomNodeBundle::class      => ['all' => TRUE],
    HbPFTableParserBundle::class     => ['all' => TRUE],
    RabbitMqBundle::class            => ['all' => TRUE],
];
