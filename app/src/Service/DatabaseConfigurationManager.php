<?php

namespace Db3v4l\Service;

class DatabaseConfigurationManager
{
    protected $instanceList = [];

    public function __construct(array $instanceList)
    {
        $this->instanceList = $instanceList;
    }

    /**
     * @param string $includeFilter accepts 'glob' wildcards
     * @param string $excludeFilter accepts 'glob' wildcards
     * @return string[]
     */
    public function listInstances($includeFilter = null, $excludeFilter = null)
    {
        $names = [];
        foreach(array_keys($this->instanceList) as $name) {
            if (($includeFilter == '' || fnmatch($includeFilter, $name)) && ($excludeFilter == '' || !fnmatch($includeFilter, $name)))
                $names[] = $name;
        }
        return $names;
    }

    /**
     * @param string $instanceName
     * @return string[]
     * @throws \OutOfBoundsException
     */
    public function getConnectionSpecification($instanceName)
    {
        if (isset($this->instanceList[$instanceName])) {
            return $this->instanceList[$instanceName];
        }

        throw new \OutOfBoundsException("Unknown database instance '$instanceName'");
    }

    /**
     * @param string[] $instanceNames
     * @return string[][]
     * @throws \OutOfBoundsException
     */
    public function getConnectionsSpecifications(array $instanceNames)
    {
        $instancesDefinitions = [];
        foreach($instanceNames as $instanceName)
        {
            $instancesDefinitions[$instanceName] = $this->getConnectionSpecification($instanceName);
        }
        return $instancesDefinitions;
    }
}
