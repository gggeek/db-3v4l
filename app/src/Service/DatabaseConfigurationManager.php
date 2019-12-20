<?php

namespace Db3v4l\Service;

class DatabaseConfigurationManager
{
    protected $instanceList = [];

    /**
     * @param string[][] $instanceList key: instance name, values: connection specification
     */
    public function __construct(array $instanceList)
    {
        $this->instanceList = $instanceList;
    }

    /**
     * @param string|string[] $includeFilter accepts 'glob' wildcards. Empty = include all
     * @param string|string[] $excludeFilter accepts 'glob' wildcards. Empty = exclude none. NB: exclude match wins over include match
     * @return string[]
     */
    public function listInstances($includeFilter = [], $excludeFilter = [])
    {
        if (!is_array($includeFilter)) {
            $includeFilter = [$includeFilter];
        }
        if (!is_array($excludeFilter)) {
            $excludeFilter = [$excludeFilter];
        }

        $names = [];
        foreach(array_keys($this->instanceList) as $name) {

            if (empty($includeFilter)) {
                $include = true;
            } else {
                $include = false;
                foreach($includeFilter as $filter) {
                    if (fnmatch($filter, $name)) {
                        $include = true;
                        break;
                    }
                }
            }

            if ($include && !empty($excludeFilter)) {
                foreach($excludeFilter as $filter) {
                    if (fnmatch($filter, $name)) {
                        $include = false;
                        break;
                    }
                }
            }

            if ($include) {
                $names[] = $name;
            }
        }
        return $names;
    }

    /**
     * @param string $instanceName
     * @return string[]
     * @throws \OutOfBoundsException
     */
    public function getInstanceConfiguration($instanceName)
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
    public function getInstancesConfiguration(array $instanceNames)
    {
        $instancesDefinitions = [];
        foreach($instanceNames as $instanceName)
        {
            $instancesDefinitions[$instanceName] = $this->getInstanceConfiguration($instanceName);
        }
        return $instancesDefinitions;
    }
}
