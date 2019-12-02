<?php

namespace Db3v4l\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/doc")
 */
class DocController extends AbstractController
{
    /// @todo get this injected
    protected $docRoot = '/var/www/doc';

    /**
     * @Route("/list", name="doc_list")
     */
    public function list()
    {
        $docs = glob($this->docRoot . '/*.md');
        array_walk($docs, function(&$path, $key) {$path = basename($path);});
        return $this->render('Doc/list.html.twig', ['docs' => $docs]);
    }

    /**
     * @Route("/view/{fileName}", name="doc_view")
     *
     * @param string $fileName
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function displayFile($fileName)
    {
        // sanitize
        $fileName = basename($fileName);

        $fileName = $this->docRoot . '/' . $fileName;

        if (!is_file($fileName)) {
            throw $this->createNotFoundException("The doc file '$fileName' does not exist");
        }

        /// @todo allow different types of doc format
        return $this->render('Doc/File/markdown.html.twig', ['file' => basename($fileName), 'markup' => file_get_contents($fileName)]);
    }
}
