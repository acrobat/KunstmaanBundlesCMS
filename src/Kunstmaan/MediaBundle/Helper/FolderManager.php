<?php

namespace Kunstmaan\MediaBundle\Helper;

use Kunstmaan\MediaBundle\Entity\Folder;
use Kunstmaan\MediaBundle\Repository\FolderRepository;

class FolderManager
{
    /** @var \Kunstmaan\MediaBundle\Repository\FolderRepository */
    private $repository;

    /**
     * @var \Kunstmaan\MediaBundle\Repository\FolderRepository
     */
    public function __construct(FolderRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return array|string
     */
    public function getFolderHierarchy(Folder $rootFolder)
    {
        return $this->repository->childrenHierarchy($rootFolder);
    }

    /**
     * @return Folder
     */
    public function getRootFolderFor(Folder $folder)
    {
        $parentIds = $this->getParentIds($folder);

        return $this->repository->getFolder($parentIds[0]);
    }

    /**
     * @return array
     */
    public function getParentIds(Folder $folder)
    {
        return $this->repository->getParentIds($folder);
    }

    /**
     * @return array
     */
    public function getParents(Folder $folder)
    {
        return $this->repository->getPath($folder);
    }
}
