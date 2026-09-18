<?php

namespace App\EventSubscriber;

use App\Entity\{CustomerPayment, Expense, FuelDelivery, SupplierPayment};
use App\Service\UserAccessService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::prePersist)]
final class OperationAuditSubscriber
{
    public function __construct(private readonly UserAccessService $access) {}

    public function prePersist(PrePersistEventArgs $event): void
    {
        $entity = $event->getObject();
        if (!$entity instanceof FuelDelivery && !$entity instanceof SupplierPayment && !$entity instanceof CustomerPayment && !$entity instanceof Expense) return;
        if ($entity->getPerformedBy() === null && $this->access->currentUser()) $entity->setPerformedBy($this->access->currentUser());
    }
}
