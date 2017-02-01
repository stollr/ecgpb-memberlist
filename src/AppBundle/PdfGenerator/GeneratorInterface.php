<?php

namespace AppBundle\PdfGenerator;

/**
 * AppBundle\PdfGenerator\GeneratorInterface
 * 
 * @author naitsirch
 */
interface GeneratorInterface
{
    /**
     * Generates and returns a PDF string.
     * @return string
     */
    function generate();
}
