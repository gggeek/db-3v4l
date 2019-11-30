<?php

namespace Db3v4l\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DocController extends AbstractController
{
    /// @todo get this injected
    protected $docRoot = '/var/www/doc';

    /**
     * @Route("/doc/{fileName}")
     *
     * @param string $fileName
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function displayFileAction($fileName)
    {
        // sanitize
        $fileName = basename($fileName);

        $fileName = $this->docRoot . '/' . $fileName;

        if (!is_file($fileName)) {
            throw $this->createNotFoundException("The doc file '$fileName' does not exist");
        }

        /// @todo allow different types of doc format
        return $this->render('Doc/markdown.html.twig', ['markup' => file_get_contents($fileName)]);
    }
}
