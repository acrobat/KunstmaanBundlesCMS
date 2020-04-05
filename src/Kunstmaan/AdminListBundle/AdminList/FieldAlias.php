<?php

namespace Kunstmaan\AdminListBundle\AdminList;

/**
 * FieldAlias
 */
class FieldAlias
{
    /** @var string */
    private $abbr;
    /** @var string */
    private $relation;

    /**
     * FieldAlias constructor.
     *
     * @param string $abbr
     * @param string $relation
     */
    public function __construct($abbr, $relation)
    {
        $this->abbr = $abbr;
        $this->relation = $relation;
    }

    /**
     * @return string
     */
    public function getAbbr()
    {
        return $this->abbr;
    }

    /**
     * @return string
     */
    public function getRelation()
    {
        return $this->relation;
    }
}
