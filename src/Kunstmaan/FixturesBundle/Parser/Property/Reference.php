<?php

namespace Kunstmaan\FixturesBundle\Parser\Property;

use Doctrine\Common\Collections\ArrayCollection;

class Reference implements PropertyParserInterface
{
    /**
     * Check if this parser is applicable
     *
     * @return bool
     */
    public function canParse($value)
    {
        if (is_string($value) && strpos($value, '@') === 0) {
            return true;
        }

        return false;
    }

    /**
     * Parse provided value into new data
     *
     * @param mixed                 $value
     * @param array|ArrayCollection $providers
     * @param array                 $references
     *
     * @return mixed
     */
    public function parse($value, $providers = [], $references = [])
    {
        $referenceName = substr($value, 1);

        if (isset($references[$referenceName])) {
            return $references[$referenceName];
        }

        return $value;
    }
}
