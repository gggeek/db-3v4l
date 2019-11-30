<?php

namespace Db3v4l\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class Homepage extends Controller
{
    /**
     * @Route("/")
     */
    public function indexAction()
    {
        return $this->render('Homepage/index.html.twig');
    }
}
