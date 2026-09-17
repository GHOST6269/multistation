<?php

namespace App\Controller;

use App\Entity\{Customer, Stations};
use App\Service\UserAccessService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/customers')]
final class CustomerController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require([UserAccessService::ROLE_GERANT, UserAccessService::ROLE_ASSISTANT])) return $denied;
        $station = $em->getRepository(Stations::class)->find((int) $request->query->get('station'));
        if (!$station || !$access->canAccessStation($station)) return $access->denyStation();
        $customers = $em->getRepository(Customer::class)->findBy(['station' => $station], ['name' => 'ASC']);
        return $this->json(['customers' => array_map(fn(Customer $customer) => $this->row($customer), $customers)]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require(UserAccessService::ROLE_GERANT)) return $denied;
        $data = $request->toArray(); $station = $em->getRepository(Stations::class)->find((int) ($data['stationId'] ?? 0));
        if (!$station || !$access->canAccessStation($station)) return $access->denyStation();
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') return $this->json(['message' => 'Le nom du client est obligatoire'], 422);
        $customer = (new Customer())->setStation($station)->setName($name)->setCode($this->nullable($data['code'] ?? null))->setContactPerson($this->nullable($data['contactPerson'] ?? null))->setPhone($this->nullable($data['phone'] ?? null))->setEmail($this->nullable($data['email'] ?? null))->setAddress($this->nullable($data['address'] ?? null))->setCreatedAt(new \DateTimeImmutable());
        $em->persist($customer); $em->flush();
        return $this->json($this->row($customer), 201);
    }

    #[Route('/{id}', requirements: ['id' => '\\d+'], methods: ['PUT'])]
    public function update(Customer $customer, Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require(UserAccessService::ROLE_GERANT)) return $denied;
        if (!$access->canAccessStation($customer->getStation())) return $access->denyStation();
        $data = $request->toArray(); $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') return $this->json(['message' => 'Le nom du client est obligatoire'], 422);
        $customer->setName($name)->setCode($this->nullable($data['code'] ?? null))->setContactPerson($this->nullable($data['contactPerson'] ?? null))->setPhone($this->nullable($data['phone'] ?? null))->setEmail($this->nullable($data['email'] ?? null))->setAddress($this->nullable($data['address'] ?? null));
        $em->flush(); return $this->json($this->row($customer));
    }

    #[Route('/{id}/deactivate', requirements: ['id' => '\\d+'], methods: ['PATCH'])]
    public function deactivate(Customer $customer, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require(UserAccessService::ROLE_GERANT)) return $denied;
        if (!$access->canAccessStation($customer->getStation())) return $access->denyStation();
        $customer->setIsActive(false); $em->flush(); return $this->json($this->row($customer));
    }

    private function nullable(mixed $value): ?string { $value = trim((string) $value); return $value ?: null; }
    private function row(Customer $customer): array { return ['id' => $customer->getId(), 'code' => $customer->getCode(), 'name' => $customer->getName(), 'contactPerson' => $customer->getContactPerson(), 'phone' => $customer->getPhone(), 'email' => $customer->getEmail(), 'address' => $customer->getAddress(), 'active' => $customer->isActive()]; }
}
