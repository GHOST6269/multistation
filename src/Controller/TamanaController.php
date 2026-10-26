<?php

namespace App\Controller;

use App\Entity\{Customer, Stations, TamanaCard, TamanaTransaction};
use App\Service\UserAccessService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tamana')]
final class TamanaController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require([UserAccessService::ROLE_GERANT, UserAccessService::ROLE_ASSISTANT])) return $denied;
        $station = $this->station($request->query->getInt('station'), $em, $access);
        if ($station instanceof JsonResponse) return $station;
        $cards = $em->getRepository(TamanaCard::class)->findBy(['station' => $station], ['holderName' => 'ASC']);
        $transactions = $em->getRepository(TamanaTransaction::class)->findBy(['station' => $station], ['transactionDate' => 'DESC', 'id' => 'DESC'], 300);
        $balances = [];
        foreach ($transactions as $transaction) {
            $id = $transaction->getCard()->getId();
            $balances[$id] = ($balances[$id] ?? 0.0) + ($transaction->getType() === 'TOP_UP' ? 1 : -1) * (float) $transaction->getAmount();
        }
        $customers = $em->getRepository(Customer::class)->findBy(['station' => $station, 'isActive' => true], ['name' => 'ASC']);
        return $this->json(['customers' => array_map(static fn(Customer $customer) => ['id' => $customer->getId(), 'name' => $customer->getName(), 'code' => $customer->getCode()], $customers), 'cards' => array_map(fn(TamanaCard $card) => ['id' => $card->getId(), 'customerId' => $card->getCustomer()?->getId(), 'cardNumber' => $card->getCardNumber(), 'holderName' => $card->getCustomer()?->getName() ?? $card->getHolderName(), 'status' => $card->getStatus(), 'balance' => round($balances[$card->getId()] ?? 0, 2)], $cards), 'transactions' => array_map(fn(TamanaTransaction $t) => ['id' => $t->getId(), 'date' => $t->getTransactionDate()?->format('Y-m-d'), 'customerId' => $t->getCard()->getCustomer()?->getId(), 'cardNumber' => $t->getCard()->getCardNumber(), 'holderName' => $t->getCard()->getCustomer()?->getName() ?? $t->getCard()->getHolderName(), 'type' => $t->getType(), 'amount' => (float) $t->getAmount(), 'balanceAfter' => (float) $t->getBalanceAfter(), 'reference' => $t->getReference(), 'note' => $t->getNote()], $transactions)]);
    }

    #[Route('/cards', methods: ['POST'])]
    public function createCard(Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require([UserAccessService::ROLE_GERANT, UserAccessService::ROLE_ASSISTANT])) return $denied;
        $data = $request->toArray(); $station = $this->station((int) ($data['stationId'] ?? 0), $em, $access);
        if ($station instanceof JsonResponse) return $station;
        $number = trim((string) ($data['cardNumber'] ?? ''));
        $customer = $em->getRepository(Customer::class)->find((int) ($data['customerId'] ?? 0));
        if ($number === '' || !$customer || $customer->getStation()?->getId() !== $station->getId() || !$customer->isActive()) return $this->json(['message' => 'Le numéro de carte et un client actif de cette station sont obligatoires.'], 422);
        if ($em->getRepository(TamanaCard::class)->findOneBy(['station' => $station, 'cardNumber' => $number])) return $this->json(['message' => 'Ce numéro de carte existe déjà pour cette station.'], 422);
        $card = (new TamanaCard())->setStation($station)->setCustomer($customer)->setCardNumber($number)->setHolderName($customer->getName())->setCreatedAt(new \DateTimeImmutable());
        $em->persist($card); $em->flush();
        return $this->json(['id' => $card->getId()], 201);
    }

    #[Route('/transactions', methods: ['POST'])]
    public function transact(Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require([UserAccessService::ROLE_GERANT, UserAccessService::ROLE_ASSISTANT])) return $denied;
        $data = $request->toArray(); $station = $this->station((int) ($data['stationId'] ?? 0), $em, $access);
        if ($station instanceof JsonResponse) return $station;
        $card = $em->getRepository(TamanaCard::class)->find((int) ($data['cardId'] ?? 0));
        $type = strtoupper((string) ($data['type'] ?? '')); $amount = round((float) ($data['amount'] ?? 0), 2);
        if (!$card || $card->getStation()?->getId() !== $station->getId() || $card->getStatus() !== 'ACTIVE') return $this->json(['message' => 'Carte TAMANA+ invalide ou inactive.'], 422);
        if (!in_array($type, ['TOP_UP', 'PURCHASE'], true) || $amount <= 0) return $this->json(['message' => 'Type de mouvement ou montant invalide.'], 422);
        $existing = $em->getRepository(TamanaTransaction::class)->findBy(['station' => $station, 'card' => $card]);
        $balance = array_sum(array_map(static fn(TamanaTransaction $t) => ($t->getType() === 'TOP_UP' ? 1 : -1) * (float) $t->getAmount(), $existing));
        if ($type === 'PURCHASE' && $amount > $balance + .001) return $this->json(['message' => 'Solde TAMANA+ insuffisant.'], 422);
        $after = round($balance + ($type === 'TOP_UP' ? $amount : -$amount), 2);
        $transaction = (new TamanaTransaction())->setStation($station)->setCard($card)->setPerformedBy($access->currentUser())->setType($type)->setAmount((string) $amount)->setBalanceAfter((string) $after)->setTransactionDate(new \DateTimeImmutable((string) ($data['date'] ?? 'today')))->setReference($this->nullable($data['reference'] ?? null))->setNote($this->nullable($data['note'] ?? null))->setCreatedAt(new \DateTimeImmutable());
        $em->persist($transaction); $em->flush();
        return $this->json(['id' => $transaction->getId(), 'balance' => $after], 201);
    }

    private function station(int $id, EntityManagerInterface $em, UserAccessService $access): Stations|JsonResponse
    {
        $station = $em->getRepository(Stations::class)->find($id);
        return !$station || !$access->canAccessStation($station) ? $access->denyStation() : $station;
    }
    private function nullable(mixed $value): ?string { return trim((string) $value) ?: null; }
}
