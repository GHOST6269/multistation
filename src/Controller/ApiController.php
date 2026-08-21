<?php

namespace App\Controller;

use App\Entity\Stations;
use App\Entity\MouvementStock;
use App\Entity\StationArticles;
use App\Repository\ArticlesRepository;
use App\Repository\StationArticlesRepository;
use App\Repository\StationsRepository;
use App\Repository\UserRepository;
use App\Service\UserAccessService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class ApiController extends AbstractController
{
    #[Route('/dashboard', methods: ['GET'])]
    public function dashboard(StationsRepository $stations, ArticlesRepository $articles, UserRepository $users, StationArticlesRepository $stock, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require([UserAccessService::ROLE_GERANT, UserAccessService::ROLE_QUALITY_MARSHALL, UserAccessService::ROLE_ASSISTANT])) return $denied;
        $allowedIds = $access->allowedStationIds();
        $allStations = $access->isSuperAdmin() ? $stations->findAll() : array_filter($stations->findAll(), static fn (Stations $station): bool => in_array($station->getId(), $allowedIds, true));
        $inventoryItems = $access->isSuperAdmin() ? $stock->findAll() : array_filter($stock->findAll(), static fn ($item): bool => in_array($item->getStation()?->getId(), $allowedIds, true));
        $inventory = array_map($this->inventoryRow(...), $inventoryItems);
        $lowStock = array_values(array_filter($inventory, static fn (array $row): bool => $row['alert']));

        return $this->json([
            'metrics' => [
                'stations' => count($allStations),
                'activeStations' => count(array_filter($allStations, static fn (Stations $s): bool => strtoupper($s->getStatus() ?? '') === 'ACTIVE')),
                'articles' => $articles->count([]),
                'users' => $users->count(['isActive' => true]),
                'lowStock' => count($lowStock),
            ],
            'stations' => array_map(static fn (Stations $station): array => [
                'id' => $station->getId(), 'name' => $station->getName(), 'city' => $station->getCity(),
                'status' => $station->getStatus(), 'articlesCount' => $station->getStationArticles()->count(), 'manager' => $station->getGerant(),
            ], array_slice($allStations, 0, 5)),
            'lowStock' => array_slice($lowStock, 0, 5),
        ]);
    }

    #[Route('/stations', methods: ['GET'])]
    public function stations(Request $request, StationsRepository $repository, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require([UserAccessService::ROLE_GERANT, UserAccessService::ROLE_QUALITY_MARSHALL, UserAccessService::ROLE_ASSISTANT])) return $denied;
        $search = trim((string) $request->query->get('search', ''));
        $stations = $search === '' ? $repository->findBy([], ['id' => 'DESC']) : $repository->createQueryBuilder('s')
            ->andWhere('LOWER(s.name) LIKE :search OR LOWER(s.city) LIKE :search OR LOWER(s.code) LIKE :search')
            ->setParameter('search', '%'.mb_strtolower($search).'%')->orderBy('s.id', 'DESC')->getQuery()->getResult();
        if (!$access->isSuperAdmin()) {
            $allowedIds = $access->allowedStationIds();
            $stations = array_values(array_filter($stations, static fn (Stations $station): bool => in_array($station->getId(), $allowedIds, true)));
        }
        return $this->json(array_map($this->stationRow(...), $stations));
    }

    #[Route('/stations', methods: ['POST'])]
    public function createStation(Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require(UserAccessService::ROLE_SUPER_ADMIN)) return $denied;
        $data = $request->toArray();
        if (trim((string) ($data['name'] ?? '')) === '') return $this->json(['message' => 'Le nom de la station est obligatoire.'], 422);
        $station = $this->applyStationData((new Stations())->setCreatAt(new \DateTime()), $data);
        $em->persist($station); $em->flush();
        return $this->json($this->stationRow($station), 201);
    }

    #[Route('/stations/{id}', requirements: ['id' => '\\d+'], methods: ['PUT'])]
    public function updateStation(Stations $station, Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require(UserAccessService::ROLE_SUPER_ADMIN)) return $denied;
        $data = $request->toArray();
        if (trim((string) ($data['name'] ?? '')) === '') return $this->json(['message' => 'Le nom de la station est obligatoire.'], 422);
        $this->applyStationData($station, $data)->setUpdatedAt(new \DateTime());
        $em->flush();
        return $this->json($this->stationRow($station));
    }

    #[Route('/stations/{id}/deactivate', requirements: ['id' => '\\d+'], methods: ['PATCH'])]
    public function deactivateStation(Stations $station, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require(UserAccessService::ROLE_SUPER_ADMIN)) return $denied;
        $station->setStatus('INACTIVE')->setUpdatedAt(new \DateTime());
        $em->flush();
        return $this->json($this->stationRow($station));
    }

    #[Route('/inventory', methods: ['GET'])]
    public function inventory(Request $request, StationArticlesRepository $repository, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require([UserAccessService::ROLE_GERANT, UserAccessService::ROLE_QUALITY_MARSHALL])) return $denied;
        $criteria = $request->query->has('station') ? ['station' => (int) $request->query->get('station')] : [];
        if (isset($criteria['station']) && !$access->canAccessStation((int) $criteria['station'])) return $access->denyStation();
        $items = $repository->findBy($criteria, ['id' => 'DESC']);
        if (!$access->isSuperAdmin() && !$criteria) {
            $allowedIds = $access->allowedStationIds();
            $items = array_values(array_filter($items, static fn ($item): bool => in_array($item->getStation()?->getId(), $allowedIds, true)));
        }
        return $this->json(array_map($this->inventoryRow(...), $items));
    }

    #[Route('/inventory/{id}/adjust', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function adjustInventory(StationArticles $item, Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require([UserAccessService::ROLE_GERANT, UserAccessService::ROLE_QUALITY_MARSHALL])) return $denied;
        if (!$access->canAccessStation($item->getStation())) return $access->denyStation();
        $data=$request->toArray();$type=(string)($data['type']??'');$quantity=(float)($data['quantity']??0);$reason=trim((string)($data['reason']??''));
        if(!in_array($type,['ADD','REMOVE','SET'],true)||$quantity<0||$reason==='')return $this->json(['message'=>'Le type, la quantité et le motif sont obligatoires.'],422);
        $basePricing=null;foreach($item->getStationArticleUnits() as $pricing)if($pricing->isActive()&&$pricing->getArticleUnit()?->isBaseUnit()){$basePricing=$pricing;break;}
        if(!$basePricing)return $this->json(['message'=>'Aucune unité de base active.'],422);
        $previous=(float)$item->getCurrentSockBase();$new=$type==='SET'?$quantity:($type==='REMOVE'?max(0,$previous-$quantity):$previous+$quantity);$delta=$new-$previous;
        $item->setCurrentSockBase((string)$new);
        $movement=(new MouvementStock())->setStationArticle($item)->setArticleUnit($basePricing->getArticleUnit())->setEnteredQuantity((string)$quantity)->setConversionFactor('1')->setBaseQuantity((string)$delta)->setPreviousStockBase((string)$previous)->setNewStockBase((string)$new)->setMouvementType($type)->setReason($reason)->setCreatedAt(new \DateTimeImmutable());
        $em->persist($movement);$em->flush();return $this->json($this->inventoryRow($item));
    }

    #[Route('/inventory/stocktake', methods: ['POST'])]
    public function stocktake(Request $request, StationArticlesRepository $repository, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require([UserAccessService::ROLE_GERANT, UserAccessService::ROLE_QUALITY_MARSHALL])) return $denied;
        $data=$request->toArray();$stationId=(int)($data['stationId']??0);$reason=trim((string)($data['reason']??''));$reference=trim((string)($data['reference']??''));$lines=$data['lines']??[];
        if (!$access->canAccessStation($stationId)) return $access->denyStation();
        if(!$stationId||$reason===''||!preg_match('/^INV-\\d+-\\d{8}-\\d{6}$/',$reference)||!is_array($lines)||!$lines)return $this->json(['message'=>'La référence, la station, le motif et les lignes de comptage sont obligatoires.'],422);
        $validated=[];
        foreach($lines as $line){$item=$repository->find((int)($line['id']??0));$counts=$line['counts']??[];if(!$item||$item->getStation()?->getId()!==$stationId||!is_array($counts)||!$counts)return $this->json(['message'=>'Une ligne de comptage est invalide.'],422);$available=[];$base=null;foreach($item->getStationArticleUnits() as $pricing)if($pricing->isActive()&&$pricing->getArticleUnit()?->isActive()){$au=$pricing->getArticleUnit();$available[$au->getId()]=$au;if($au->isBaseUnit())$base=$au;}if(!$base)return $this->json(['message'=>'Une ligne ne possède aucune unité de base.'],422);$physical=0;$details=[];foreach($counts as $count){$unitId=(int)($count['articleUnitId']??0);$quantity=(float)($count['quantity']??-1);$au=$available[$unitId]??null;if(!$au||$quantity<0)return $this->json(['message'=>'Une unité ou quantité de comptage est invalide.'],422);$factor=(float)$au->getConverstionFactor();$physical+=$quantity*$factor;$details[]=['articleUnitId'=>$au->getId(),'unit'=>$au->getUnit()?->getName(),'symbol'=>$au->getUnit()?->getSymbol(),'quantity'=>$quantity,'conversionFactor'=>$factor,'baseQuantity'=>$quantity*$factor];}$validated[]=[$item,$base,$physical,$details];}
        foreach($validated as [$item,$base,$physical,$details]){$previous=(float)$item->getCurrentSockBase();if(abs($physical-$previous)<.0000001)continue;$delta=$physical-$previous;$item->setCurrentSockBase((string)$physical);$movement=(new MouvementStock())->setStationArticle($item)->setArticleUnit($base)->setEnteredQuantity((string)$physical)->setConversionFactor('1')->setBaseQuantity((string)$delta)->setPreviousStockBase((string)$previous)->setNewStockBase((string)$physical)->setMouvementType('STOCKTAKE')->setReference($reference)->setDetails($details)->setReason($reason)->setCreatedAt(new \DateTimeImmutable());$em->persist($movement);}
        $em->flush();$items=$repository->findBy(['station'=>$stationId],['id'=>'DESC']);return $this->json(array_map($this->inventoryRow(...),$items));
    }

    private function stationRow(Stations $station): array
    {
        return ['id' => $station->getId(), 'code' => $station->getCode(), 'name' => $station->getName(), 'address' => $station->getAddress(),
            'city' => $station->getCity(), 'contact' => $station->getContact(), 'email' => $station->getEmail(), 'status' => $station->getStatus(),
            'manager' => $station->getGerant(), 'usersCount' => $station->getStationUsers()->count(), 'articlesCount' => $station->getStationArticles()->count(),
            'createdAt' => $station->getCreatAt()?->format(DATE_ATOM)];
    }

    private function applyStationData(Stations $station, array $data): Stations
    {
        $nullable = static fn (mixed $value): ?string => trim((string) $value) === '' ? null : trim((string) $value);
        return $station->setName(trim((string) $data['name']))
            ->setCode($nullable($data['code'] ?? null))->setCity($nullable($data['city'] ?? null))
            ->setAddress($nullable($data['address'] ?? null))->setContact($nullable($data['contact'] ?? null))
            ->setEmail($nullable($data['email'] ?? null))->setGerant($nullable($data['manager'] ?? null))
            ->setStatus((string) ($data['status'] ?? $station->getStatus() ?? 'ACTIVE'));
    }

    private function inventoryRow(\App\Entity\StationArticles $item): array
    {
        $current = (float) $item->getCurrentSockBase(); $minimum = (float) $item->getMinimumStockBase();
        $baseUnit=null;$countUnits=[];foreach($item->getStationArticleUnits() as $pricing)if($pricing->isActive()&&$pricing->getArticleUnit()?->isActive()){$articleUnit=$pricing->getArticleUnit();$unit=$articleUnit->getUnit();$countUnits[]=['articleUnitId'=>$articleUnit->getId(),'name'=>$unit?->getName(),'symbol'=>$unit?->getSymbol(),'conversionFactor'=>(float)$articleUnit->getConverstionFactor(),'isBaseUnit'=>$articleUnit->isBaseUnit()];if($articleUnit->isBaseUnit())$baseUnit=$unit;}
        return ['id' => $item->getId(), 'stationId' => $item->getStation()?->getId(), 'station' => $item->getStation()?->getName(),
            'articleId' => $item->getArticle()?->getId(), 'article' => $item->getArticle()?->getName(), 'category' => $item->getArticle()?->getCategorie()?->getName() ?? 'Sans catégorie',
            'currentStock' => $current, 'minimumStock' => $minimum, 'unit'=>$baseUnit?->getName(), 'symbol'=>$baseUnit?->getSymbol(), 'units'=>$countUnits, 'active' => $item->isActive(), 'alert' => $current <= $minimum];
    }
}
