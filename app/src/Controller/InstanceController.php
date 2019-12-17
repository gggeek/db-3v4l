<?php

namespace Db3v4l\Controller;

use Db3v4l\Service\DatabaseConfigurationManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/instance")
 */
class InstanceController extends AbstractController
{
    /**
     * @Route("/list", name="instance_list")
     */
    public function list(DatabaseConfigurationManager $configurationManager)
    {
        $instances = [];
        foreach($configurationManager->listInstances() as $instanceName) {
            $instances[$instanceName] = $configurationManager->getInstanceConfiguration($instanceName);
        }
        return $this->render('Instance/list.html.twig', ['instances' => $instances]);
    }
}
