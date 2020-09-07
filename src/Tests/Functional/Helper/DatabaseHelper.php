<?php

namespace Momocode\ShopwareBase\Tests\Functional\Helper;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Shopware\Components\Model\ModelEntity;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Mail\Mail;

/**
 * @author Moritz Müller <moritz@momocode.de>
 */
class DatabaseHelper
{
    /**
     * @var ModelManager
     */
    protected $modelManager;

    /**
     * @var array
     */
    protected $createdEntities = [];

    public function __construct()
    {
        $this->modelManager = Shopware()->Models();
    }

    /**
     * @param string $entityClass
     * @param array $fixture
     * @return ModelEntity
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createEntity($entityClass, $fixture)
    {
        /** @var ModelEntity $entity */
        $entity = new $entityClass();
        $entity->fromArray($fixture);
        $this->modelManager->persist($entity);
        $this->modelManager->flush();
        $this->createdEntities[] = $entity;
        return $entity;
    }

    /**
     * @throws ORMException
     */
    public function removeEntities()
    {
        foreach ($this->createdEntities as $detachedEntity) {
            $entity = $this->modelManager->merge($detachedEntity);
            $this->modelManager->remove($entity);
        }
        $this->modelManager->flush();
    }
}
