<?php

namespace Kunstmaan\PagePartBundle\Tests\Repository;

use Doctrine\ORM\EntityManager;
use Kunstmaan\PagePartBundle\Entity\TextPagePart;
use Kunstmaan\PagePartBundle\Helper\HasPagePartsInterface;
use Kunstmaan\PagePartBundle\Repository\PagePartRefRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PagePartRefRepositoryTest extends TestCase
{
    /**
     * @var EntityManager|MockObject
     */
    private $em;

    protected function setUp(): void
    {
        $this->em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testCopyPagePartsFlushesOncePerStepInsteadOfOncePerPagePart()
    {
        $fromPage = $this->createMock(HasPagePartsInterface::class);
        $toPage = $this->createMock(HasPagePartsInterface::class);

        $repository = $this->createRepository([
            $this->createPagePart(10),
            $this->createPagePart(11),
            $this->createPagePart(12),
        ]);

        $this->em->expects($this->exactly(3))->method('persist');
        // Once to give all copies an id, once for all the created references
        $this->em->expects($this->exactly(2))->method('flush');

        $repository->expects($this->exactly(3))
            ->method('addPagePart')
            ->with($toPage, $this->isInstanceOf(TextPagePart::class), $this->anything(), 'main', false, false);

        $repository->copyPageParts($this->em, $fromPage, $toPage, 'main');
    }

    public function testCopyPagePartsCopiesThePagePartsWithoutTheirId()
    {
        $fromPage = $this->createMock(HasPagePartsInterface::class);
        $toPage = $this->createMock(HasPagePartsInterface::class);

        $original = $this->createPagePart(10);
        $original->setContent('content');

        $repository = $this->createRepository([$original]);

        $copies = [];
        $this->em->expects($this->once())
            ->method('persist')
            ->willReturnCallback(static function ($pagePart) use (&$copies) {
                $copies[] = $pagePart;
            });

        $repository->copyPageParts($this->em, $fromPage, $toPage, 'main');

        $this->assertCount(1, $copies);
        $this->assertNotSame($original, $copies[0]);
        $this->assertNull($copies[0]->getId());
        $this->assertSame('content', $copies[0]->getContent());
        $this->assertSame(10, $original->getId());
    }

    public function testCopyPagePartsWithoutPagePartsDoesNotFlush()
    {
        $fromPage = $this->createMock(HasPagePartsInterface::class);
        $toPage = $this->createMock(HasPagePartsInterface::class);

        $repository = $this->createRepository([]);

        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->never())->method('flush');
        $repository->expects($this->never())->method('addPagePart');

        $repository->copyPageParts($this->em, $fromPage, $toPage, 'main');
    }

    /**
     * @return PagePartRefRepository|MockObject
     */
    private function createRepository(array $pageParts)
    {
        $repository = $this->getMockBuilder(PagePartRefRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPageParts', 'addPagePart'])
            ->getMock();

        $repository->method('getPageParts')->willReturn($pageParts);

        return $repository;
    }

    private function createPagePart(int $id): TextPagePart
    {
        $pagePart = new TextPagePart();
        $pagePart->setId($id);

        return $pagePart;
    }
}
