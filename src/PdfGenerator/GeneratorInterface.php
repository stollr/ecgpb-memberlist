<?php

namespace App\PdfGenerator;

/**
 * App\PdfGenerator\GeneratorInterface
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
