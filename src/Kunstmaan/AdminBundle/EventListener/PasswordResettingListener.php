<?php

namespace Kunstmaan\AdminBundle\EventListener;

use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Model\UserManager;
use Kunstmaan\AdminBundle\Entity\BaseUser;

/**
 * Set password_changed property to 1 after changing the password
 */
class PasswordResettingListener
{
    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @param UserManager $userManager
     */
    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * @param FilterUserResponseEvent $event
     */
    public function onPasswordResettingSuccess(FilterUserResponseEvent $event)
    {
        $user = $event->getUser();
        if (!$user instanceof BaseUser) {
            return;
        }

        $user->setPasswordChanged(true);
        $this->userManager->updateUser($user);
    }
}
