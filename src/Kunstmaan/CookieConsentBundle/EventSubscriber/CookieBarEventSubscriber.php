<?php

namespace Kunstmaan\CookieBundle\EventSubscriber;

use Kunstmaan\AdminBundle\Helper\AdminRouteHelper;
use Kunstmaan\CookieBundle\Helper\LegalCookieHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class CookieBarEventSubscriber
 *
 * @package Kunstmaan\CookieBundle\EventSubscriber
 */
class CookieBarEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var AdminRouteHelper
     */
    protected $adminRouteHelper;

    /**
     * @var LegalCookieHelper
     */
    private $cookieHelper;


    /**
     * CookieBarEventSubscriber constructor.
     *
     * @param \Twig_Environment $twig
     * @param AdminRouteHelper  $adminRouteHelper
     * @param LegalCookieHelper $cookieHelper
     */
    public function __construct(\Twig_Environment $twig, AdminRouteHelper $adminRouteHelper, LegalCookieHelper $cookieHelper)
    {
        $this->twig = $twig;
        $this->adminRouteHelper = $adminRouteHelper;
        $this->cookieHelper = $cookieHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -125],
        ];
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        $request = $event->getRequest();
        $url = $event->getRequest()->getRequestUri();

        // Do not capture redirects
        if (!$event->isMasterRequest() || $this->adminRouteHelper->isAdminRoute($url)) {
            return;
        }

        if ($response->isRedirection() || ($response->headers->has('Content-Type') && false === strpos(
                    $response->headers->get('Content-Type'),
                    'html'
                ))
            || 'html' !== $request->getRequestFormat()
            || !$this->cookieHelper->isGrantedForCookieBundle($request)
            || false !== stripos($response->headers->get('Content-Disposition'), 'attachment;')
        ) {
            return;
        }

        $response = $this->cookieHelper->checkCookieVersionInResponse($response, $request);

        $this->injectCookieBar($response);
    }

    /**
     * @param Response $response
     */
    protected function injectCookieBar(Response $response)
    {
        $content = $response->getContent();
        $pos = strripos($content, '</kuma-cookie-bar>');

        if (false !== $pos) {
            $toolbar = "\n".str_replace(
                    "\n",
                    '',
                    $this->twig->render('@KunstmaanCookie/CookieBar/view.html.twig')
                )."\n";
            $content = substr($content, 0, $pos).$toolbar.substr($content, $pos);
            $response->setContent($content);
        }
    }
}
