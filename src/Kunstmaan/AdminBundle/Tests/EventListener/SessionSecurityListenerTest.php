<?php

namespace Kunstmaan\AdminBundle\Tests\EventListener;

use Kunstmaan\AdminBundle\EventListener\SessionSecurityListener;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ServerBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;

class SessionSecurityListenerTest extends TestCase
{
    public function testOnKernelRequest()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $request = $this->createMock(Request::class);
        $request->server = $this->createMock(ServerBag::class);
        $request->headers = $this->createMock(HeaderBag::class);

        $session = $this->createMock(Session::class);

        $request->expects($this->once())->method('hasSession')->willReturn(true);
        $request->expects($this->exactly(2))->method('getSession')->willReturn($session);
        $request->server->expects($this->any())->method('get')->will($this->onConsecutiveCalls('Session ip', 'kuma_ua'));
        $request->headers->expects($this->any())->method('get')->willReturn('kuma_ua');
        $session->expects($this->once())->method('isStarted')->willReturn(true);
        $session->expects($this->any())->method('has')->willReturn(true);

        $listener = new SessionSecurityListener(true, true, $logger);
        $listener->onKernelRequest($this->getRequestEvent($request));

        $listener->onKernelRequest($this->getRequestEvent($request, HttpKernelInterface::SUB_REQUEST));
    }

    public function testOnKernelResponse()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $request = $this->createMock(Request::class);
        $request->server = $this->createMock(ServerBag::class);
        $request->headers = $this->createMock(HeaderBag::class);

        $session = $this->createMock(Session::class);

        $request->expects($this->once())->method('hasSession')->willReturn(true);
        $request->expects($this->exactly(2))->method('getSession')->willReturn($session);
        $request->server->expects($this->any())->method('get')->will($this->onConsecutiveCalls('Session ip', 'kuma_ua'));
        $request->headers->expects($this->any())->method('get')->willReturn('kuma_ua');
        $session->expects($this->once())->method('isStarted')->willReturn(true);
        $session->expects($this->exactly(2))->method('has')->willReturn(false);

        $listener = new SessionSecurityListener(true, true, $logger);
        $listener->onKernelRequest($this->getRequestEvent($request));

        $listener->onKernelRequest($this->getRequestEvent($request, HttpKernelInterface::SUB_REQUEST));
    }

    public function testInvalidateSessionWithNoIpSet()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $request = $this->createMock(Request::class);
        $request->server = $this->createMock(ServerBag::class);
        $request->headers = $this->createMock(HeaderBag::class);

        $session = $this->createMock(Session::class);
        $session->expects($this->once())->method('isStarted')->willReturn(true);
        $session->expects($this->exactly(2))->method('has')->willReturn(false);

        $request->expects($this->once())->method('hasSession')->willReturn(true);
        $request->expects($this->exactly(2))->method('getSession')->willReturn($session);
        $request->expects($this->once())->method('getClientIp')->willReturn('95.154.243.5');
        $request->server->expects($this->any())->method('get')->willReturn('');
        $request->headers->expects($this->any())->method('get')->willReturn('kuma_ua');

        $listener = new SessionSecurityListener(true, true, $logger);
        $listener->onKernelResponse($this->getResponseEvent($request, new Response()));
    }

    /**
     * @return GetResponseEvent|RequestEvent
     */
    private function getRequestEvent(Request $request, int $requestType = HttpKernelInterface::MASTER_REQUEST)
    {
        $kernelStub = new class ('dev', true) extends Kernel {
            public function registerBundles() {}
            public function registerContainerConfiguration(LoaderInterface $loader){}
        };

        if (class_exists(ResponseEvent::class)) {
            return new RequestEvent($kernelStub, $request, $requestType);
        }

        return new GetResponseEvent($kernelStub, $request, $requestType);
    }

    /**
     * @return FilterResponseEvent|ResponseEvent
     */
    private function getResponseEvent(Request $request, Response $response, int $requestType = HttpKernelInterface::MASTER_REQUEST)
    {
        $kernelStub = new class ('dev', true) extends Kernel {
            public function registerBundles() {}
            public function registerContainerConfiguration(LoaderInterface $loader){}
        };

        if (class_exists(ResponseEvent::class)) {
            return new ResponseEvent($kernelStub, $request, $requestType, $response);
        }

        return new FilterResponseEvent($kernelStub, $request, $requestType, $response);
    }
}
