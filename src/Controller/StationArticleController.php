<?php

namespace App\Controller;

use App\Entity\ArticleCategorie;
use App\Entity\Articles;
use App\Entity\ArticlesUnits;
use App\Entity\MouvementStock;
use App\Entity\StationArticles;
use App\Entity\StationArticleUnits;
use App\Entity\Stations;
use App\Entity\Units;
use App\Repository\ArticleCategorieRepository;
use App\Repository\StationArticlesRepository;
use App\Repository\StationsRepository;
use App\Repository\UnitsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/station-articles')]
final class StationArticleController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(Request $request, StationArticlesRepository $repository): JsonResponse
    {
        $stationId = (int) $request->query->get('station');
        if (!$stationId) return $this->json(['message' => 'La station est obligatoire.'], 422);
        $items = $repository->createQueryBuilder('sa')->join('sa.article', 'a')->andWhere('sa.station = :station')->setParameter('station', $stationId)
            ->orderBy('a.name', 'ASC')->getQuery()->getResult();
        return $this->json(array_map($this->row(...), $items));
    }

    #[Route('/options', methods: ['GET'])]
    public function options(StationsRepository $stations, ArticleCategorieRepository $categories, UnitsRepository $units): JsonResponse
    {
        return $this->json([
            'stations' => array_map(static fn (Stations $s) => ['id' => $s->getId(), 'name' => $s->getName()], $stations->findBy(['status' => 'ACTIVE'], ['name' => 'ASC'])),
            'categories' => array_map(static fn (ArticleCategorie $c) => ['id' => $c->getId(), 'name' => $c->getName()], $categories->findBy(['isActive' => true], ['name' => 'ASC'])),
            'units' => array_map(static fn (Units $u) => ['id' => $u->getId(), 'name' => $u->getName(), 'symbol' => $u->getSymbol()], $units->findBy(['isActive' => true], ['name' => 'ASC'])),
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, StationsRepository $stations, ArticleCategorieRepository $categories, UnitsRepository $units): JsonResponse
    {
        $data = $request->toArray();
        $station = $stations->find((int) ($data['stationId'] ?? 0));
        $unitRows = $data['units'] ?? [];
        if (!$station || trim((string) ($data['name'] ?? '')) === '' || !$unitRows || count(array_filter($unitRows, static fn(array $row) => (bool)($row['isBaseUnit'] ?? false))) !== 1)
            return $this->json(['message' => 'La station, le nom et une seule unité de base sont obligatoires.'], 422);

        $category = $categories->find((int) ($data['categoryId'] ?? 0));
        if (!$category) return $this->json(['message' => 'Sélectionnez une catégorie valide.'], 422);

        $article = (new Articles())->setName(trim($data['name']))->setDescription(trim((string) ($data['description'] ?? '')) ?: null)
            ->setCategorie($category)->setIsActive(true)->setCreatAt(new \DateTime());
        $stock = (string) max(0, (float) ($data['currentStock'] ?? 0));
        $stationArticle = (new StationArticles())->setStation($station)->setArticle($article)->setCurrentSockBase($stock)
            ->setMinimumStockBase((string) max(0, (float) ($data['minimumStock'] ?? 0)))->setIsActive(true);
        foreach ([$article, $stationArticle] as $entity) $em->persist($entity);
        $baseArticleUnit = null;
        foreach ($unitRows as $row) {
            $unitName = trim((string) ($row['unitName'] ?? ''));
            if ($unitName === '') return $this->json(['message' => 'Le nom de chaque unité est obligatoire.'], 422);
            $unit = (new Units())->setName($unitName)->setSymbol(trim((string) ($row['unitSymbol'] ?? '')) ?: null)
                ->setCode(trim((string) ($row['unitCode'] ?? '')) ?: null)->setIsActive(true);
            $em->persist($unit);
            $isBase = (bool) ($row['isBaseUnit'] ?? false);
            $articleUnit = (new ArticlesUnits())->setArticle($article)->setUnit($unit)->setConverstionFactor($isBase ? '1' : (string) max(0.000001, (float) ($row['conversionFactor'] ?? 1)))
                ->setIsBaseUnit($isBase)->setBarcode(trim((string) ($row['barcode'] ?? '')) ?: null)->setIsActive(true);
            $prices = (new StationArticleUnits())->setStationArticle($stationArticle)->setArticleUnit($articleUnit)
                ->setPurchasePrice($this->money($row['purchasePrice'] ?? null))->setSalePrice($this->money($row['salePrice'] ?? 0) ?? '0')
                ->setWholesalePrice($this->money($row['wholesalePrice'] ?? 0) ?? '0')->setMinimumSalePrice($this->money($row['minimumSalePrice'] ?? 0) ?? '0')->setIsActive(true)->setCreatAt(new \DateTime());
            $em->persist($articleUnit); $em->persist($prices); if ($isBase) $baseArticleUnit = $articleUnit;
        }
        if ((float) $stock > 0) {
            $movement = (new MouvementStock())->setStationArticle($stationArticle)->setArticleUnit($baseArticleUnit)->setEnteredQuantity($stock)
                ->setConversionFactor('1')->setBaseQuantity($stock)->setPreviousStockBase('0')->setNewStockBase($stock)->setMouvementType('INITIAL_STOCK')->setReason('Stock initial')->setCreatedAt(new \DateTimeImmutable());
            $em->persist($movement);
        }
        $em->flush();
        return $this->json($this->row($stationArticle), 201);
    }

    #[Route('/categories', methods: ['POST'])]
    public function createCategory(Request $request, EntityManagerInterface $em, ArticleCategorieRepository $repository): JsonResponse
    {
        $data=$request->toArray(); $name=trim((string)($data['name']??'')); if($name==='') return $this->json(['message'=>'Le nom est obligatoire.'],422);
        if($repository->findOneBy(['name'=>$name])) return $this->json(['message'=>'Cette catégorie existe déjà.'],409);
        $item=(new ArticleCategorie())->setName($name)->setCode(trim((string)($data['code']??''))?:null)->setIsActive(true);$em->persist($item);$em->flush();
        return $this->json(['id'=>$item->getId(),'name'=>$item->getName()],201);
    }

    #[Route('/{id}/deactivate', requirements: ['id' => '\\d+'], methods: ['PATCH'])]
    public function deactivate(StationArticles $item, EntityManagerInterface $em): JsonResponse
    {
        $item->setIsActive(false); $item->getArticle()?->setIsActive(false)->setUpdateAt(new \DateTime());
        foreach ($item->getStationArticleUnits() as $unit) { $unit->setIsActive(false)->setUpdatedAt(new \DateTime()); $unit->getArticleUnit()?->setIsActive(false); $unit->getArticleUnit()?->getUnit()?->setIsActive(false); }
        $em->flush(); return $this->json($this->row($item));
    }

    #[Route('/{id}', requirements: ['id' => '\\d+'], methods: ['PUT'])]
    public function update(StationArticles $item, Request $request, EntityManagerInterface $em, ArticleCategorieRepository $categories): JsonResponse
    {
        $data = $request->toArray();
        $rows = $data['units'] ?? [];
        if (trim((string) ($data['name'] ?? '')) === '' || !$rows || count(array_filter($rows, static fn(array $row) => (bool)($row['isBaseUnit'] ?? false))) !== 1)
            return $this->json(['message' => 'Le nom et une seule unité de base sont obligatoires.'], 422);
        $category = $categories->find((int) ($data['categoryId'] ?? 0));
        if (!$category) return $this->json(['message' => 'Sélectionnez une catégorie valide.'], 422);
        $article = $item->getArticle(); $article->setName(trim($data['name']))->setDescription(trim((string) ($data['description'] ?? '')) ?: null)->setCategorie($category)->setUpdateAt(new \DateTime());
        $oldStock = (float) $item->getCurrentSockBase(); $newStock = max(0, (float) ($data['currentStock'] ?? $oldStock));
        $item->setCurrentSockBase((string) $newStock)->setMinimumStockBase((string) max(0, (float) ($data['minimumStock'] ?? 0)));
        $existing = []; foreach ($item->getStationArticleUnits() as $pricing) $existing[$pricing->getId()] = $pricing;
        $kept = []; $baseArticleUnit = null;
        foreach ($rows as $row) {
            $pricing = isset($row['id']) ? ($existing[(int)$row['id']] ?? null) : null;
            if (!$pricing) {
                $unit = (new Units())->setIsActive(true); $em->persist($unit);
                $articleUnit = (new ArticlesUnits())->setArticle($article)->setUnit($unit)->setIsActive(true);
                $pricing = (new StationArticleUnits())->setStationArticle($item)->setArticleUnit($articleUnit)->setIsActive(true)->setCreatAt(new \DateTime());
                $em->persist($articleUnit); $em->persist($pricing);
            } else { $articleUnit = $pricing->getArticleUnit(); $unit = $articleUnit->getUnit(); }
            $unitName=trim((string)($row['unitName']??'')); if($unitName==='') return $this->json(['message'=>'Le nom de chaque unité est obligatoire.'],422);
            $unit->setName($unitName)->setSymbol(trim((string)($row['unitSymbol']??''))?:null)->setCode(trim((string)($row['unitCode']??''))?:null)->setIsActive(true);
            $isBase=(bool)($row['isBaseUnit']??false);$articleUnit->setConverstionFactor($isBase?'1':(string)max(.000001,(float)($row['conversionFactor']??1)))->setIsBaseUnit($isBase)->setBarcode(trim((string)($row['barcode']??''))?:null)->setIsActive(true);
            $pricing->setPurchasePrice($this->money($row['purchasePrice']??null))->setSalePrice($this->money($row['salePrice']??0)??'0')->setWholesalePrice($this->money($row['wholesalePrice']??0)??'0')->setMinimumSalePrice($this->money($row['minimumSalePrice']??0)??'0')->setIsActive(true)->setUpdatedAt(new \DateTime());
            if($pricing->getId())$kept[]=$pricing->getId();if($isBase)$baseArticleUnit=$articleUnit;
        }
        foreach($existing as $id=>$pricing)if(!in_array($id,$kept,true)){$pricing->setIsActive(false)->setUpdatedAt(new \DateTime());$pricing->getArticleUnit()?->setIsActive(false);$pricing->getArticleUnit()?->getUnit()?->setIsActive(false);}
        if ($newStock !== $oldStock) { $movement = (new MouvementStock())->setStationArticle($item)->setArticleUnit($baseArticleUnit)->setEnteredQuantity((string) abs($newStock - $oldStock))->setConversionFactor('1')->setBaseQuantity((string) ($newStock - $oldStock))->setPreviousStockBase((string) $oldStock)->setNewStockBase((string) $newStock)->setMouvementType('ADJUSTMENT')->setReason('Modification de la fiche article')->setCreatedAt(new \DateTimeImmutable()); $em->persist($movement); }
        $em->flush(); return $this->json($this->row($item));
    }

    private function money(mixed $value): ?string { return $value === null || $value === '' ? null : number_format(max(0, (float) $value), 2, '.', ''); }
    private function row(StationArticles $item): array
    {
        $activePrices = array_values(array_filter($item->getStationArticleUnits()->toArray(), static fn(StationArticleUnits $p) => $p->isActive()));
        usort($activePrices, static fn(StationArticleUnits $a, StationArticleUnits $b) => ($b->getArticleUnit()?->isBaseUnit() <=> $a->getArticleUnit()?->isBaseUnit()));
        $pricing = $activePrices[0] ?? null; $unit = $pricing?->getArticleUnit();
        return ['id' => $item->getId(), 'stationId' => $item->getStation()?->getId(), 'station' => $item->getStation()?->getName(),
            'name' => $item->getArticle()?->getName(), 'description' => $item->getArticle()?->getDescription(), 'categoryId' => $item->getArticle()?->getCategorie()?->getId(), 'category' => $item->getArticle()?->getCategorie()?->getName(),
            'unit' => $unit?->getUnit()?->getName(), 'symbol' => $unit?->getUnit()?->getSymbol(), 'barcode' => $unit?->getBarcode(),
            'currentStock' => (float) $item->getCurrentSockBase(), 'minimumStock' => (float) $item->getMinimumStockBase(),
            'purchasePrice' => $pricing ? (float) $pricing->getPurchasePrice() : null, 'salePrice' => $pricing ? (float) $pricing->getSalePrice() : 0,
            'wholesalePrice' => $pricing ? (float) $pricing->getWholesalePrice() : 0, 'minimumSalePrice' => $pricing ? (float) $pricing->getMinimumSalePrice() : 0, 'active' => $item->isActive(),
            'units' => array_map(static fn(StationArticleUnits $p):array => ['id'=>$p->getId(),'articleUnitId'=>$p->getArticleUnit()?->getId(),'unitName'=>$p->getArticleUnit()?->getUnit()?->getName(),'unitSymbol'=>$p->getArticleUnit()?->getUnit()?->getSymbol(),'unitCode'=>$p->getArticleUnit()?->getUnit()?->getCode(),'conversionFactor'=>(float)$p->getArticleUnit()?->getConverstionFactor(),'isBaseUnit'=>$p->getArticleUnit()?->isBaseUnit(),'barcode'=>$p->getArticleUnit()?->getBarcode(),'purchasePrice'=>$p->getPurchasePrice()!==null?(float)$p->getPurchasePrice():null,'salePrice'=>(float)$p->getSalePrice(),'wholesalePrice'=>(float)$p->getWholesalePrice(),'minimumSalePrice'=>(float)$p->getMinimumSalePrice()],$activePrices)];
    }
}
