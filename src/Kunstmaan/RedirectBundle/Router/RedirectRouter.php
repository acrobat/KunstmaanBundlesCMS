<?php

namespace Kunstmaan\RedirectBundle\Router;

use Doctrine\Common\Persistence\ObjectRepository;
use Kunstmaan\AdminBundle\Helper\DomainConfigurationInterface;
use Kunstmaan\RedirectBundle\Entity\Redirect;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class RedirectRouter implements RouterInterface
{
    /** @var RequestContext */
    private $context;

    /** @var RouteCollection */
    private $routeCollection;

    /** @var ObjectRepository */
    private $redirectRepository;

    /** @var DomainConfigurationInterface */
    private $domainConfiguration;

    /**
     * @param ObjectRepository             $redirectRepository
     * @param DomainConfigurationInterface $domainConfiguration
     */
    public function __construct(ObjectRepository $redirectRepository, DomainConfigurationInterface $domainConfiguration)
    {
        $this->redirectRepository = $redirectRepository;
        $this->domainConfiguration = $domainConfiguration;
        $this->context = new RequestContext();
    }

    /**
     * {@inheritDoc}
     */
    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        throw new RouteNotFoundException('You cannot generate a url from a redirect');
    }

    /**
     * {@inheritDoc}
     */
    public function match($pathinfo)
    {
        $urlMatcher = new UrlMatcher($this->getRouteCollection(), $this->getContext());

        return $urlMatcher->match($pathinfo);
    }

    /**
     * {@inheritDoc}
     */
    public function getRouteCollection()
    {
        if (\is_null($this->routeCollection)) {
            $this->routeCollection = new RouteCollection();
            $this->initRoutes();
        }

        return $this->routeCollection;
    }

    private function initRoutes()
    {
        $redirects = $this->redirectRepository->findAll();
        $domain = $this->domainConfiguration->getHost();

        /** @var Redirect $redirect */
        foreach ($redirects as $redirect) {
            // Check for wildcard routing and adjust as required
            if ($this->isWildcardRedirect($redirect)) {
                $route = $this->createWildcardRoute($redirect);
            } else {
                $route = $this->createRoute($redirect);
            }

            // Only add the route when the domain matches or the domain is empty
            if ($redirect->getDomain() == $domain || !$redirect->getDomain()) {
                $this->routeCollection->add(
                    '_redirect_route_' . $redirect->getId(),
                    $route
                );
            }
        }
    }

    /**
     * @param Redirect $redirect
     *
     * @return bool
     */
    private function isWildcardRedirect(Redirect $redirect)
    {
        $origin = $redirect->getOrigin();
        $matchSegment = substr($origin, 0, -1);
        if (substr($origin, -2) == '/*') {
            return $this->isPathInfoWildcardMatch($matchSegment);
        }

        return false;
    }

    private function isPathInfoWildcardMatch($matchSegment)
    {
        $path = $this->context->getPathInfo();

        return strstr($path, $matchSegment);
    }

    /**
     * @param Redirect $redirect
     *
     * @return Route
     */
    private function createRoute(Redirect $redirect)
    {
        $needsUtf8 = false;
        foreach ([$redirect->getOrigin(), $redirect->getTarget()] as $item) {
            if (preg_match('/[\x80-\xFF]/', $item)) {
                $needsUtf8 = true;

                break;
            }
        }

        return new Route(
            $redirect->getOrigin(), [
                '_controller' => 'FrameworkBundle:Redirect:urlRedirect',
                'path' => $redirect->getTarget(),
                'permanent' => $redirect->isPermanent(),
            ], [], ['utf8' => $needsUtf8]);
    }

    /**
     * @param Redirect $redirect
     *
     * @return Route
     */
    private function createWildcardRoute(Redirect $redirect)
    {
        $origin = $redirect->getOrigin();
        $target = $redirect->getTarget();
        $url = $this->context->getPathInfo();
        $needsUtf8 = preg_match('/[\x80-\xFF]/', $redirect->getTarget());

        $origin = substr($origin, 0, -1);
        $target = substr($target, 0, -1);
        $pathInfo = str_replace($origin, $target, $url);

        $this->context->setPathInfo($pathInfo);

        return new Route($url, [
            '_controller' => 'FrameworkBundle:Redirect:urlRedirect',
            'path' => $url,
            'permanent' => $redirect->isPermanent(),
        ], [], ['utf8' => $needsUtf8]);
    }

    /**
     * {@inheritDoc}
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * {@inheritDoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->context = $context;
    }
}
