<?php

namespace Kunstmaan\AdminBundle\Form;

interface RoleDependentUserFormInterface
{
    /**
     * Allows you to specify if the user type form should contain all fields or not
     *
     * @param bool $canEditAllFields
     *
     * @return bool
     */
    public function setCanEditAllFields($canEditAllFields);
}
