<?php

namespace App\Service;

use App\Entity\Stations;
use App\Entity\User;
use App\Repository\StationUsersRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

final class UserAccessService
{
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
    public const ROLE_GERANT = 'ROLE_GERANT';
    public const ROLE_QUALITY_MARSHALL = 'ROLE_QUALITY_MARSHALL';
    public const ROLE_ASSISTANT = 'ROLE_ASSISTANT';

    public const ROLE_LABELS = [
        self::ROLE_SUPER_ADMIN => 'Super admin',
        self::ROLE_GERANT => 'Gérant',
        self::ROLE_QUALITY_MARSHALL => 'Quality Marshal',
        self::ROLE_ASSISTANT => 'Assistant',
    ];

    private ?User $currentUser = null;
    private bool $resolved = false;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly AuthTokenService $tokens,
        private readonly UserRepository $users,
        private readonly StationUsersRepository $stationUsers,
    ) {
    }

    public function currentUser(): ?User
    {
        if ($this->resolved) {
            return $this->currentUser;
        }

        $this->resolved = true;
        $request = $this->requestStack->getCurrentRequest();
        $header = (string) $request?->headers->get('Authorization', '');
        $token = str_starts_with($header, 'Bearer ') ? trim(substr($header, 7)) : '';
        $userId = $token !== '' ? $this->tokens->userId($token) : null;
        $user = $userId ? $this->users->find($userId) : null;
        $this->currentUser = $user && $user->isActive() ? $user : null;

        return $this->currentUser;
    }

    public function isSuperAdmin(?User $user = null): bool
    {
        return in_array(self::ROLE_SUPER_ADMIN, ($user ?? $this->currentUser())?->getRoles() ?? [], true);
    }

    public function require(array|string $roles): ?JsonResponse
    {
        $user = $this->currentUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Authentification requise.'], 401);
        }

        if ($this->isSuperAdmin($user)) {
            return null;
        }

        $roles = (array) $roles;
        foreach ($roles as $role) {
            if (in_array($role, $user->getRoles(), true)) {
                return null;
            }
        }

        return new JsonResponse(['message' => 'Accès refusé pour ce rôle.'], 403);
    }

    public function allowedStationIds(?User $user = null): array
    {
        $user ??= $this->currentUser();
        if (!$user || $this->isSuperAdmin($user)) {
            return [];
        }

        $rows = $this->stationUsers->findBy(['user' => $user, 'isActive' => true]);

        return array_values(array_unique(array_filter(array_map(
            static fn ($row): ?int => $row->getStation()?->getId(),
            $rows
        ))));
    }

    public function canAccessStation(Stations|int|null $station): bool
    {
        $user = $this->currentUser();
        if (!$user) {
            return false;
        }

        if ($this->isSuperAdmin($user)) {
            return true;
        }

        $stationId = $station instanceof Stations ? $station->getId() : $station;

        return $stationId !== null && in_array((int) $stationId, $this->allowedStationIds($user), true);
    }

    public function denyStation(): JsonResponse
    {
        return new JsonResponse(['message' => 'Cette station n’est pas accessible avec votre compte.'], 403);
    }
}
