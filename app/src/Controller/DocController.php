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
     * @Route("/view/{filename}", name="doc_view")
     *
     * @param string $filename
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function displayFile($filename)
    {
        // sanitize
        $filename = basename($filename);

        $filename = $this->docRoot . '/' . $filename;

        if (!is_file($filename)) {
            throw $this->createNotFoundException("The doc file '$filename' does not exist");
        }

        /// @todo allow different types of doc format
        return $this->render('Doc/File/markdown.html.twig', ['file' => basename($filename), 'markup' => file_get_contents($filename)]);
    }
}
