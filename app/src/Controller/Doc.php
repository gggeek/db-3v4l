<?php

namespace Db3v4l\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class Doc extends Controller
{
    /// @todo get this injected
    protected $docRoot = '/home/db3v4l/doc';

    /**
     * @Route("/doc/{fileName}}")
     *
     * @param string $fileName
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
        return $this->render('Doc/markdown.html.twig');
    }
}
