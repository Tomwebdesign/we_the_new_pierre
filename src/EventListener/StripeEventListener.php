<?php

namespace App\EventListener;

use App\Entity\Order\Order;
use App\Entity\Order\Payment;
use App\Event\StripeEvent;
use App\Repository\Order\OrderRepository;
use App\Repository\Order\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'payment_intent.succeeded', method: 'onPaymentSucceed')]
#[AsEventListener(event: 'payment_intent.payment_failed', method: 'onPaymentFailed')]
class StripeEventListener
{
    public function __construct(
        private EntityManagerInterface $em,
        private OrderRepository $orderRepository,
        private PaymentRepository $paymentRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function onPaymentSucceed(StripeEvent $event): void
    {
        $ressource = $event->getRessource();

        // On vérifie que la ressource est bien une charge Stripe
        if (!$ressource) {
            throw new \InvalidArgumentException('The event ressource is missing.');
        }

        // On récupère le payment associé en BDD
        $payment = $this->paymentRepository->find($ressource->metadata->payment_id);
        // On récupère la commande associé en BDD
        $order = $this->orderRepository->find($ressource->metadata->order_id);

        // On vérifie que le paiement et la commande existent
        if (!$payment || !$order) {
            throw new \InvalidArgumentException('The payment or order is missing.');
        }

        // On met à jour le paiement et la commande
        $payment->setStatus(Payment::STATUS_PAID);
        $order->setStatus(Order::STATUS_SHIPPING);

        $this->em->persist($payment);
        $this->em->persist($order);

        $this->em->flush();
    }

    public function onPaymentFailed(StripeEvent $event): void
    {
        $ressource = $event->getRessource();

        if (!$ressource) {
            throw new \InvalidArgumentException('The event ressource is missing.');
        }

        $payment = $this->paymentRepository->find($ressource->metadata->payment_id);
        $order = $this->orderRepository->find($ressource->metadata->order_id);

        if (!$payment || !$order) {
            throw new \InvalidArgumentException('The payment or order is missing.');
        }

        $order->setStatus(Order::STATUS_AWAITING_PAYMENT);
        $payment->setStatus(Payment::STATUS_FAILED);

        $this->em->persist($payment);
        $this->em->persist($order);

        $this->em->flush();
    }
}
