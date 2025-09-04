<?php

namespace App\Controller;

use App\Entity\TableRestaurant;
use App\Entity\CommandeLigne;
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
        try {
            $data = json_decode($request->getContent(), true);
            if (!isset($data['tableId']) || !isset($data['produits']) || !is_array($data['produits'])) {
                return new JsonResponse(['success' => false, 'error' => 'Données manquantes ou invalides']);
            }

            $table = $em->getRepository(TableRestaurant::class)->find($data['tableId']);
            if (!$table) {
                return new JsonResponse(['success' => false, 'error' => 'Table introuvable']);
            }

            $commande = new Commande();
            $commande->setTable($table);

            foreach ($data['produits'] as $p) {
                $produit = $em->getRepository(Produit::class)->find($p['id']);
                if (!$produit) continue; // Ignore les produits introuvables

                $ligne = new CommandeLigne();
                $ligne->setCommande($commande);
                $ligne->setLibelleProduit($produit->getNom());
                $ligne->setPrixHt($produit->getPrixHt());
                $ligne->setTauxTva($produit->getTauxtva()?->getTaux() ?? 0); // <-- correction TVA
                $ligne->setQuantite($p['quantite'] ?? 1);
                $ligne->setCategorieLibelle($produit->getCategorie()?->getNom() ?? '');
                $ligne->setProduitId($produit->getId());

                $commande->addLigne($ligne);
                $em->persist($ligne);
            }

            $em->persist($commande);
            $em->flush();

            return new JsonResponse(['success' => true, 'id' => $commande->getId()]);
        } catch (\Throwable $e) {
            // Capture toutes les exceptions et renvoie un JSON
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
