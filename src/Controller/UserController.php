<?php

namespace App\Controller;

use App\Entity\StationUsers;
use App\Entity\User;
use App\Repository\StationsRepository;
use App\Repository\StationUsersRepository;
use App\Repository\UserRepository;
use App\Service\AuthTokenService;
use App\Service\UserAccessService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class UserController extends AbstractController
{
    #[Route('/auth/login', methods: ['POST'])]
    public function login(Request $request, UserRepository $users, UserPasswordHasherInterface $hasher, AuthTokenService $tokens, EntityManagerInterface $em): JsonResponse
    {
        $data = $request->toArray();
        $user = $users->findOneBy(['email' => trim((string) ($data['email'] ?? ''))]);
        if (!$user || !$user->isActive() || !$hasher->isPasswordValid($user, (string) ($data['password'] ?? ''))) {
            return $this->json(['message' => 'Email ou mot de passe incorrect.'], 401);
        }

        $user->setLastLogin(new \DateTime());
        $em->flush();

        return $this->json(['token' => $tokens->create($user), 'user' => $this->userRow($user, $this->stationIds($user, $em))]);
    }

    #[Route('/auth/me', methods: ['GET'])]
    public function me(UserAccessService $access, EntityManagerInterface $em): JsonResponse
    {
        $user = $access->currentUser();
        if (!$user) {
            return $this->json(['message' => 'Authentification requise.'], 401);
        }

        return $this->json(['user' => $this->userRow($user, $this->stationIds($user, $em))]);
    }

    #[Route('/auth/profile', methods: ['PUT'])]
    public function updateProfile(Request $request, UserAccessService $access, StationsRepository $stations, UserPasswordHasherInterface $hasher, EntityManagerInterface $em): JsonResponse
    {
        $user = $access->currentUser();
        if (!$user) {
            return $this->json(['message' => 'Authentification requise.'], 401);
        }

        $data = $request->toArray();
        if ($access->isSuperAdmin()) {
            $error = $this->applyUserData($user, $data, $stations, $em, true);
            if ($error) {
                return $this->json(['message' => $error], 422);
            }

            if (!empty($data['password'])) {
                $password = (string) $data['password'];
                if (strlen($password) < 6) {
                    return $this->json(['message' => 'Le mot de passe doit contenir au moins 6 caractères.'], 422);
                }
                $user->setPassword($hasher->hashPassword($user, $password));
            }

            $user->setUpdatedAt(new \DateTime());
            $em->flush();
            $this->syncStations($user, $data['stationIds'] ?? [], $stations, $em);

            return $this->json(['user' => $this->userRow($user, $this->stationIds($user, $em))]);
        }

        $firstName = trim((string) ($data['firstName'] ?? ''));
        if ($firstName === '') {
            return $this->json(['message' => 'Le prénom est obligatoire.'], 422);
        }

        // Only personal fields are accepted here. Login email, role and station assignments
        // remain managed by an administrator through the user-management endpoints.
        $user->setFirstName($firstName)
            ->setLastName(trim((string) ($data['lastName'] ?? '')) ?: null)
            ->setContact(trim((string) ($data['contact'] ?? '')) ?: null);

        if (!empty($data['password'])) {
            $password = (string) $data['password'];
            if (strlen($password) < 6) {
                return $this->json(['message' => 'Le mot de passe doit contenir au moins 6 caractères.'], 422);
            }
            $user->setPassword($hasher->hashPassword($user, $password));
        }

        $user->setUpdatedAt(new \DateTime());
        $em->flush();

        return $this->json(['user' => $this->userRow($user, $this->stationIds($user, $em))]);
    }

    #[Route('/users', methods: ['GET'])]
    public function index(UserAccessService $access, UserRepository $users, StationUsersRepository $stationUsers, EntityManagerInterface $em): JsonResponse
    {
        if ($denied = $access->require([UserAccessService::ROLE_SUPER_ADMIN, UserAccessService::ROLE_GERANT])) {
            return $denied;
        }

        $items = $access->isSuperAdmin() ? $users->findBy([], ['id' => 'DESC']) : array_filter(array_map(
            static fn (StationUsers $assignment): ?User => $assignment->getUser(),
            $stationUsers->findBy(['station' => $access->allowedStationIds(), 'isActive' => true]),
        ), static fn (?User $user): bool => $user !== null);

        return $this->json(array_map(fn (User $user): array => $this->userRow($user, $this->stationIds($user, $em)), $items));
    }

    #[Route('/users/roles', methods: ['GET'])]
    public function roles(UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require([UserAccessService::ROLE_SUPER_ADMIN, UserAccessService::ROLE_GERANT])) {
            return $denied;
        }

        $availableRoles = $access->isSuperAdmin() ? UserAccessService::ROLE_LABELS : array_filter(
            UserAccessService::ROLE_LABELS,
            static fn (string $role): bool => $role !== UserAccessService::ROLE_SUPER_ADMIN,
            ARRAY_FILTER_USE_KEY,
        );
        return $this->json(['roles' => array_map(
            static fn (string $role, string $label): array => ['value' => $role, 'label' => $label],
            array_keys($availableRoles),
            $availableRoles,
        )]);
    }

    #[Route('/users', methods: ['POST'])]
    public function create(Request $request, UserAccessService $access, StationsRepository $stations, UserPasswordHasherInterface $hasher, EntityManagerInterface $em): JsonResponse
    {
        if ($denied = $access->require([UserAccessService::ROLE_SUPER_ADMIN, UserAccessService::ROLE_GERANT])) {
            return $denied;
        }

        $data = $request->toArray();
        if (!$access->isSuperAdmin()) {
            $stationIds = $access->allowedStationIds();
            if (count($stationIds) !== 1) {
                return $this->json(['message' => 'Votre compte doit être rattaché à une station pour créer un utilisateur.'], 422);
            }
            if (!in_array($data['role'] ?? '', [UserAccessService::ROLE_GERANT, UserAccessService::ROLE_QUALITY_MARSHALL, UserAccessService::ROLE_ASSISTANT], true)) {
                return $this->json(['message' => 'Un gérant ne peut pas créer de compte super administrateur.'], 422);
            }
            $data['stationIds'] = [$stationIds[0]];
            $data['isActive'] = true;
        }
        $user = new User();
        $error = $this->applyUserData($user, $data, $stations, $em, false);
        if ($error) {
            return $this->json(['message' => $error], 422);
        }

        $password = (string) ($data['password'] ?? '');
        if (strlen($password) < 6) {
            return $this->json(['message' => 'Le mot de passe doit contenir au moins 6 caractères.'], 422);
        }

        $user->setPassword($hasher->hashPassword($user, $password))->setCreatAt(new \DateTime());
        $em->persist($user);
        $em->flush();
        $this->syncStations($user, $data['stationIds'] ?? [], $stations, $em);

        return $this->json($this->userRow($user, $this->stationIds($user, $em)), 201);
    }

    #[Route('/users/{id}', requirements: ['id' => '\\d+'], methods: ['PUT'])]
    public function update(User $user, Request $request, UserAccessService $access, StationsRepository $stations, UserPasswordHasherInterface $hasher, EntityManagerInterface $em): JsonResponse
    {
        if ($denied = $access->require(UserAccessService::ROLE_SUPER_ADMIN)) {
            return $denied;
        }

        $data = $request->toArray();
        $error = $this->applyUserData($user, $data, $stations, $em, true);
        if ($error) {
            return $this->json(['message' => $error], 422);
        }

        if (!empty($data['password'])) {
            if (strlen((string) $data['password']) < 6) {
                return $this->json(['message' => 'Le mot de passe doit contenir au moins 6 caractères.'], 422);
            }
            $user->setPassword($hasher->hashPassword($user, (string) $data['password']));
        }

        $user->setUpdatedAt(new \DateTime());
        $em->flush();
        $this->syncStations($user, $data['stationIds'] ?? [], $stations, $em);

        return $this->json($this->userRow($user, $this->stationIds($user, $em)));
    }

    #[Route('/users/{id}/toggle', requirements: ['id' => '\\d+'], methods: ['PATCH'])]
    public function toggle(User $user, UserAccessService $access, EntityManagerInterface $em): JsonResponse
    {
        if ($denied = $access->require(UserAccessService::ROLE_SUPER_ADMIN)) {
            return $denied;
        }

        $user->setIsActive(!$user->isActive())->setUpdatedAt(new \DateTime());
        $em->flush();

        return $this->json($this->userRow($user, $this->stationIds($user, $em)));
    }

    private function applyUserData(User $user, array $data, StationsRepository $stations, EntityManagerInterface $em, bool $updating): ?string
    {
        $email = trim((string) ($data['email'] ?? ''));
        $firstName = trim((string) ($data['firstName'] ?? ''));
        $role = (string) ($data['role'] ?? '');
        $stationIds = $data['stationIds'] ?? [];
        if ($email === '' || $firstName === '' || !isset(UserAccessService::ROLE_LABELS[$role])) {
            return 'Email, prénom et rôle sont obligatoires.';
        }
        $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing && (!$updating || $existing->getId() !== $user->getId())) {
            return 'Cet email est déjà utilisé.';
        }
        if ($role !== UserAccessService::ROLE_SUPER_ADMIN && (!is_array($stationIds) || count($stationIds) !== 1 || !$stations->find((int) $stationIds[0]))) {
            return 'Un utilisateur non super admin doit être fixé sur une seule station.';
        }

        $user->setEmail($email)->setFirstName($firstName)->setLastName(trim((string) ($data['lastName'] ?? '')) ?: null)
            ->setContact(trim((string) ($data['contact'] ?? '')) ?: null)->setRoles([$role])->setIsActive((bool) ($data['isActive'] ?? true));

        return null;
    }

    private function syncStations(User $user, mixed $stationIds, StationsRepository $stations, EntityManagerInterface $em): void
    {
        $ids = in_array(UserAccessService::ROLE_SUPER_ADMIN, $user->getRoles(), true) ? [] : array_map('intval', is_array($stationIds) ? $stationIds : []);
        foreach ($em->getRepository(StationUsers::class)->findBy(['user' => $user]) as $assignment) {
            $assignment->setIsActive(in_array($assignment->getStation()?->getId(), $ids, true));
        }
        foreach ($ids as $id) {
            $station = $stations->find($id);
            if (!$station) {
                continue;
            }
            $existing = $em->getRepository(StationUsers::class)->findOneBy(['user' => $user, 'station' => $station]);
            if ($existing) {
                $existing->setIsActive(true);
                continue;
            }
            $em->persist((new StationUsers())->setUser($user)->setStation($station)->setIsActive(true)->setAssignedAt(new \DateTime()));
        }
        $em->flush();
    }

    private function stationIds(User $user, EntityManagerInterface $em): array
    {
        return array_values(array_filter(array_map(
            static fn (StationUsers $row): ?int => $row->isActive() ? $row->getStation()?->getId() : null,
            $em->getRepository(StationUsers::class)->findBy(['user' => $user])
        )));
    }

    private function userRow(User $user, array $stationIds): array
    {
        $roles = array_values(array_filter($user->getRoles(), static fn (string $role): bool => $role !== 'ROLE_USER'));
        $role = $roles[0] ?? 'ROLE_USER';

        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'contact' => $user->getContact(),
            'role' => $role,
            'roleLabel' => UserAccessService::ROLE_LABELS[$role] ?? 'Utilisateur',
            'roles' => $roles,
            'stationIds' => $stationIds,
            'isActive' => $user->isActive(),
            'lastLogin' => $user->getLastLogin()?->format(DATE_ATOM),
        ];
    }
}
