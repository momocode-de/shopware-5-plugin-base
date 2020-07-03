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
     * @param string $name
     * @param string $subject
     * @param string $content
     *
     * @return Mail
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createMailTemplate($name, $subject, $content)
    {
        // Create a new mail template
        $mail = $this->modelManager->getRepository(Mail::class)->findOneBy(['name' => $name]);
        if (!$mail) {
            $mail = new Mail();
            $mail->setFromMail('{config name=mail}');
            $mail->setFromName('{config name=shopName}');
            $mail->setIsHtml(true);
            $mail->setMailtype(1);
            $mail->setContentHtml('');
            $mail->setContent('');
            $mail->setName($name);
            $mail->setSubject($subject);
            $this->modelManager->persist($mail);
        }
        $mail->setContentHtml($content);
        $mail->setContext([]);
        $this->modelManager->flush();

        $this->createdEntities[] = $mail;

        return $mail;
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
        foreach ($this->createdEntities as $entity) {
            $this->modelManager->remove($entity);
        }
    }
}
