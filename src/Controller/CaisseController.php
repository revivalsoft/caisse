<?php

namespace App\Controller;

use App\Entity\TableRestaurant;
use App\Entity\Restaurant;
use App\Entity\Produit;
use App\Entity\Categorie;
use App\Entity\Commande;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class CaisseController extends AbstractController
{

    #[Route('/', name: 'caisse_tables', methods: ['GET'])]
    public function tables(EntityManagerInterface $em): Response
    {
        $tables = $em->getRepository(TableRestaurant::class)->findAll();
        $categories = $em->getRepository(Categorie::class)->findAll();

        $restaurant = $em->getRepository(Restaurant::class)->findOneBy([]);


        return $this->render('caisse/table.html.twig', [
            'tables' => $tables,
            'categories' => $categories,
            'restaurant' => $restaurant,
        ]);
    }

    #[Route('/caisse/save', name: 'caisse_save', methods: ['POST'])]
    public function saveTicket(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['tableId']) || !isset($data['produits'])) {
            return new JsonResponse(['success' => false, 'error' => 'Données manquantes']);
        }

        $table = $em->getRepository(TableRestaurant::class)->find($data['tableId']);
        if (!$table) return new JsonResponse(['success' => false, 'error' => 'Table introuvable']);

        $commande = new Commande();
        $commande->setTable($table);

        foreach ($data['produits'] as $p) {
            $produit = $em->getRepository(Produit::class)->find($p['id']);
            if ($produit) {
                $commande->addProduit($produit, $p['quantite']);
            }
        }

        $em->persist($commande);
        $em->flush();

        return new JsonResponse(['success' => true, 'id' => $commande->getId()]);
    }
}
