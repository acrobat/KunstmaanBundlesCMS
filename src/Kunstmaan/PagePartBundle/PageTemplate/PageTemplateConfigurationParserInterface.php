<?php

namespace Kunstmaan\PagePartBundle\PageTemplate;

interface PageTemplateConfigurationParserInterface
{
    /**
     * @param string $name
     *
     * @return PageTemplateInterface
     */
    public function parse($name);
}
