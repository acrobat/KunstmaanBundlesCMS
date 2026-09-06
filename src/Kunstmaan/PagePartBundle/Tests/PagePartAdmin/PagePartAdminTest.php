<?php

namespace Kunstmaan\PagePartBundle\Tests\PagePartAdmin;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Kunstmaan\AdminBundle\Entity\EntityInterface;
use Kunstmaan\PagePartBundle\Entity\HeaderPagePart;
use Kunstmaan\PagePartBundle\Entity\PagePartRef;
use Kunstmaan\PagePartBundle\Entity\TextPagePart;
use Kunstmaan\PagePartBundle\Helper\HasPagePartsInterface;
use Kunstmaan\PagePartBundle\PagePartAdmin\PagePartAdmin;
use Kunstmaan\PagePartBundle\PagePartAdmin\PagePartAdminConfigurator;
use Kunstmaan\PagePartBundle\Repository\PagePartRefRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class TestPageWithPageParts implements HasPagePartsInterface, EntityInterface
{
    private $id = 1;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getPagePartAdminConfigurations()
    {
        return ['main'];
    }
}

class PagePartAdminTest extends TestCase
{
    /**
     * @var EntityManager|MockObject
     */
    private $em;

    /**
     * @var PagePartRefRepository|MockObject
     */
    private $refRepo;

    /**
     * @var TestPageWithPageParts
     */
    private $page;

    /**
     * @var ContainerInterface|MockObject
     */
    private $container;

    protected function setUp(): void
    {
        $this->page = new TestPageWithPageParts();
        // The concrete class is mocked because PagePartRef::getPagePart() type hints it
        $this->em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->refRepo = $this->getMockBuilder(PagePartRefRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnArgument(0);

        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method('get')->with('event_dispatcher')->willReturn($dispatcher);
    }

    public function testPagePartsAreLinkedToTheirPagePartRef()
    {
        $text10 = $this->createPagePart(TextPagePart::class, 10);
        $text11 = $this->createPagePart(TextPagePart::class, 11);
        $header20 = $this->createPagePart(HeaderPagePart::class, 20);

        $pagePartAdmin = $this->createPagePartAdmin(
            [
                $this->createPagePartRef(1, TextPagePart::class, 10, 1),
                $this->createPagePartRef(2, HeaderPagePart::class, 20, 2),
                $this->createPagePartRef(3, TextPagePart::class, 11, 3),
            ],
            [
                TextPagePart::class => [$text10, $text11],
                HeaderPagePart::class => [$header20],
            ]
        );

        $this->assertSame([1 => $text10, 2 => $header20, 3 => $text11], $pagePartAdmin->getPagePartMap());
    }

    public function testPagePartIsOnlyLinkedToASinglePagePartRef()
    {
        $text10 = $this->createPagePart(TextPagePart::class, 10);

        $pagePartAdmin = $this->createPagePartAdmin(
            [
                $this->createPagePartRef(1, TextPagePart::class, 10, 1),
                $this->createPagePartRef(2, TextPagePart::class, 10, 2),
            ],
            [TextPagePart::class => [$text10]]
        );

        $this->assertSame([1 => $text10], $pagePartAdmin->getPagePartMap());
    }

    public function testPagePartRefWithoutPagePartIsSkipped()
    {
        $pagePartAdmin = $this->createPagePartAdmin(
            [$this->createPagePartRef(1, TextPagePart::class, 10, 1)],
            [TextPagePart::class => []]
        );

        $this->assertSame([], $pagePartAdmin->getPagePartMap());
    }

    public function testPossiblePagePartTypesAreOnlyCountedOnce()
    {
        $limitedType = ['name' => 'Text', 'class' => TextPagePart::class, 'pagelimit' => 3];
        $unlimitedType = ['name' => 'Header', 'class' => HeaderPagePart::class];

        $this->refRepo->expects($this->once())
            ->method('countPagePartsOfType')
            ->with($this->page, TextPagePart::class, 'main')
            ->willReturn(1);

        $pagePartAdmin = $this->createPagePartAdmin([], [], [$limitedType, $unlimitedType]);

        $this->assertSame([$limitedType, $unlimitedType], $pagePartAdmin->getPossiblePagePartTypes());
        $this->assertSame([$limitedType, $unlimitedType], $pagePartAdmin->getPossiblePagePartTypes());
    }

    public function testPossiblePagePartTypesRespectThePageLimit()
    {
        $limitedType = ['name' => 'Text', 'class' => TextPagePart::class, 'pagelimit' => 1];

        $this->refRepo->method('countPagePartsOfType')->willReturn(1);

        $pagePartAdmin = $this->createPagePartAdmin([], [], [$limitedType]);

        $this->assertSame([], $pagePartAdmin->getPossiblePagePartTypes());
    }

    public function testPersistingNewPagePartsOnlyFlushesTwice()
    {
        $pagePartAdmin = $this->createPagePartAdmin([], []);

        $request = new Request([], [
            'main_new' => ['newpp_1', 'newpp_2', 'newpp_3'],
            'main_type_newpp_1' => TextPagePart::class,
            'main_type_newpp_2' => TextPagePart::class,
            'main_type_newpp_3' => HeaderPagePart::class,
            'main_sequence' => ['newpp_1', 'newpp_2', 'newpp_3'],
        ]);

        $this->em->expects($this->exactly(3))->method('persist');
        $this->em->expects($this->exactly(2))->method('flush');

        // The references are added without flushing, the pageparts they point to
        // are already flushed and the references are flushed together afterwards
        $this->refRepo->expects($this->exactly(3))
            ->method('addPagePart')
            ->with($this->page, $this->anything(), $this->anything(), 'main', false, false);

        $pagePartAdmin->preBindRequest($request);
        $pagePartAdmin->persist($request);
    }

    public function testPersistingWithoutNewPagePartsDoesNotFlush()
    {
        $text10 = $this->createPagePart(TextPagePart::class, 10);
        $pagePartAdmin = $this->createPagePartAdmin(
            [$this->createPagePartRef(1, TextPagePart::class, 10, 1)],
            [TextPagePart::class => [$text10]]
        );

        $request = new Request([], ['main_sequence' => [1]]);

        $this->em->expects($this->never())->method('flush');
        $this->refRepo->expects($this->never())->method('addPagePart');

        $pagePartAdmin->preBindRequest($request);
        $pagePartAdmin->persist($request);
    }

    /**
     * @param array<string, array> $pagePartsPerType
     */
    private function createPagePartAdmin(array $pagePartRefs, array $pagePartsPerType, array $possiblePagePartTypes = []): PagePartAdmin
    {
        $this->refRepo->method('getPagePartRefs')->willReturn($pagePartRefs);

        $repositories = [PagePartRef::class => $this->refRepo];
        foreach ($pagePartsPerType as $class => $pageParts) {
            $repository = $this->getMockBuilder(EntityRepository::class)
                ->disableOriginalConstructor()
                ->getMock();
            $repository->method('findBy')->willReturn($pageParts);

            $repositories[$class] = $repository;
        }

        $this->em->method('getRepository')->willReturnCallback(static function ($class) use ($repositories) {
            return $repositories[$class];
        });

        $configurator = new PagePartAdminConfigurator();
        $configurator->setName('main');
        $configurator->setContext('main');
        $configurator->setPossiblePagePartTypes($possiblePagePartTypes);

        return new PagePartAdmin($configurator, $this->em, $this->page, 'main', $this->container);
    }

    private function createPagePartRef(int $id, string $pagePartEntityName, int $pagePartId, int $sequenceNumber): PagePartRef
    {
        $pagePartRef = new PagePartRef();
        $pagePartRef->setId($id);
        $pagePartRef->setPageEntityname(TestPageWithPageParts::class);
        $pagePartRef->setPageId($this->page->getId());
        $pagePartRef->setPagePartEntityname($pagePartEntityName);
        $pagePartRef->setPagePartId($pagePartId);
        $pagePartRef->setContext('main');
        $pagePartRef->setSequencenumber($sequenceNumber);

        return $pagePartRef;
    }

    private function createPagePart(string $class, int $id)
    {
        $pagePart = new $class();
        $pagePart->setId($id);

        return $pagePart;
    }
}
