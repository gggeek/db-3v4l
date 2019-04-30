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
     * @return string[]
     */
    public function listInstances()
    {
        return array_keys($this->instanceList);
    }

    /**
     * @param string $instanceName
     * @return string[]
     * @throws \OutOfBoundsException
     */
    public function getDatabaseConnectionSpecification($instanceName)
    {
        if (isset($this->instanceList[$instanceName])) {
            return $this->instanceList[$instanceName];
        }

        throw new \OutOfBoundsException("Unknown database instance '$instanceName'");
    }
}
