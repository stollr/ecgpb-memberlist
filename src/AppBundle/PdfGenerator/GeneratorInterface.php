<?php

namespace Ecgpb\MemberBundle\PdfGenerator;

/**
 * Ecgpb\MemberBundle\PdfGenerator\GeneratorInterface
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
