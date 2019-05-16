<?php

namespace Kunstmaan\CookieBundle\AdminList;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Kunstmaan\AdminListBundle\AdminList\Configurator\AbstractDoctrineORMAdminListConfigurator;
use Kunstmaan\AdminListBundle\AdminList\FieldAlias;
use Kunstmaan\AdminListBundle\AdminList\FilterType\ORM\EnumerationFilterType;
use Kunstmaan\AdminListBundle\AdminList\FilterType\ORM\StringFilterType;
use Kunstmaan\AdminListBundle\Entity\OverviewNavigationInterface;
use Kunstmaan\CookieBundle\Form\CookieAdminType;

/**
 * Class CookieAdminListConfigurator
 *
 * @package Kunstmaan\CookieBundle\AdminList
 */
class CookieAdminListConfigurator extends AbstractDoctrineORMAdminListConfigurator implements OverviewNavigationInterface
{
    /**
     * @param EntityManager $em        The entity manager
     * @param AclHelper     $aclHelper The acl helper
     */
    public function __construct(EntityManager $em, AclHelper $aclHelper = null)
    {
        parent::__construct($em, $aclHelper);
        $this->setAdminType(CookieAdminType::class);
    }

    /**
     * Configure the visible columns
     */
    public function buildFields()
    {
        $this->addField('name', 'kuma.cookie.adminlists.cookie.name', true);
        $this->addField('t.name', 'kuma.cookie.adminlists.cookie.type', true, null, new FieldAlias('t', 'type'));
    }

    /**
     * Build filters for admin list
     */
    public function buildFilters()
    {
        $this->addFilter('name', new StringFilterType('name'), 'kuma.cookie.adminlists.cookie.name');
        $this->addFilter('type', new EnumerationFilterType('id', 't'), 'kuma.cookie.adminlists.cookie.type', $this->getCookieTypes());
    }

    public function adaptQueryBuilder(QueryBuilder $queryBuilder)
    {
        $queryBuilder
            ->addSelect('t')
            ->innerJoin('b.type', 't');
    }

    /**
     * @return array
     */
    private function getCookieTypes()
    {
        $cookieTypes = [];
        foreach ($this->em->getRepository('KunstmaanCookieBundle:CookieType')->findAll() as $cookieType) {
            $cookieTypes[$cookieType->getId()] = $cookieType->getName();
        }

        return $cookieTypes;
    }

    /**
     * Get bundle name
     *
     * @return string
     */
    public function getBundleName()
    {
        return 'KunstmaanCookieBundle';
    }

    /**
     * Get entity name
     *
     * @return string
     */
    public function getEntityName()
    {
        return 'Cookie';
    }

    /**
     * @return string
     */
    public function getOverViewRoute()
    {
        return 'kunstmaancookiebundle_admin_cookie';
    }
}
