<?php

namespace Db3v4l\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension as BaseExtension;

class Extension extends BaseExtension
{
    public function getAlias()
    {
        return 'db3v4l';
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('db3v4l.database_instances', $config['database_instances']);
    }
}
