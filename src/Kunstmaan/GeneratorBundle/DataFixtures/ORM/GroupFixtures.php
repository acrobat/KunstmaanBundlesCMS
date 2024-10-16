<?php

namespace Kunstmaan\GeneratorBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Kunstmaan\AdminBundle\Entity\Group;

/**
 * Fixture for creating the basic groups
 */
class GroupFixtures extends AbstractFixture implements OrderedFixtureInterface
{
    const REFERENCE_ADMINS_GROUP = 'admins-group';
    const REFERENCE_GUESTS_GROUP = 'guests-group';
    const REFERENCE_SUPERADMINS_GROUP = 'superadmins-group';

    /**
     * Load data fixtures with the passed EntityManager
     */
    public function load(ObjectManager $manager)
    {
        $group1 = $this->createGroup($manager, 'Administrators', [
            $this->getReference(RoleFixtures::REFERENCE_PERMISSIONMANAGER_ROLE),
            $this->getReference(RoleFixtures::REFERENCE_ADMIN_ROLE),
        ]);

        $guestRoles = [$this->getReference(RoleFixtures::REFERENCE_GUEST_ROLE)];
        if ($this->hasReference(RoleFixtures::REFERENCE_PUBLIC_ACCESS_ROLE)) {
            $guestRoles[] = $this->getReference(RoleFixtures::REFERENCE_PUBLIC_ACCESS_ROLE);
        }
        $group2 = $this->createGroup($manager, 'Guests', $guestRoles);

        $group3 = $this->createGroup($manager, 'Super administrators', [
            $this->getReference(RoleFixtures::REFERENCE_PERMISSIONMANAGER_ROLE),
            $this->getReference(RoleFixtures::REFERENCE_ADMIN_ROLE),
            $this->getReference(RoleFixtures::REFERENCE_SUPERADMIN_ROLE),
        ]);

        $manager->flush();

        $this->addReference(self::REFERENCE_ADMINS_GROUP, $group1);
        $this->addReference(self::REFERENCE_GUESTS_GROUP, $group2);
        $this->addReference(self::REFERENCE_SUPERADMINS_GROUP, $group3);
    }

    /**
     * Create a group
     *
     * @param ObjectManager $manager The object manager
     * @param string        $name    The name of the group
     * @param array         $roles   The roles connected to this group
     *
     * @return Group
     */
    private function createGroup(ObjectManager $manager, $name, array $roles = [])
    {
        $group = new Group($name);
        foreach ($roles as $role) {
            $group->addRole($role);
        }
        $manager->persist($group);

        return $group;
    }

    /**
     * Get the order of this fixture
     *
     * @return int
     */
    public function getOrder()
    {
        return 2;
    }
}
