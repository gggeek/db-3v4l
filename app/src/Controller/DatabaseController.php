<?php

namespace Db3v4l\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/database")
 */
class DatabaseController extends AbstractController
{
    /**
     * @Route("/list", name="database_list")
     */
    public function list()
    {
        /// @todo
    }
}
