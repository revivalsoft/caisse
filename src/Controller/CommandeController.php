<?php

namespace App\Controller;

use App\Service\TicketPrinter;
use App\Repository\CommandeRepository;
use App\Entity\Restaurant;
use App\Repository\TableRestaurantRepository;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CommandeController extends AbstractController
{
    #[Route('/commandes', name: 'app_commandes')]
    public function index(
        Request $request,
        CommandeRepository $commandeRepository,
        TableRestaurantRepository $tableRepo,
        PaginatorInterface $paginator
    ): Response {
        $date = $request->query->get('date');
        $tableId = $request->query->get('table');

        $queryBuilder = $commandeRepository->createQueryBuilder('c')
            ->orderBy('c.date', 'DESC');

        // ✅ Filtrage par date (sans DATE())
        if ($date) {
            $start = new \DateTime($date);
            $end = (clone $start)->modify('+1 day');

            $queryBuilder
                ->andWhere('c.date >= :start')
                ->andWhere('c.date < :end')
                ->setParameter('start', $start)
                ->setParameter('end', $end);
        }

        // ✅ Filtrage par table
        if ($tableId) {
            $queryBuilder
                ->andWhere('c.table = :table')
                ->setParameter('table', $tableId);
        }

        // ✅ Pagination
        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('commande/index.html.twig', [
            'pagination' => $pagination,
            'tables' => $tableRepo->findAll(),
            'selected_date' => $date,
            'selected_table' => $tableId,
        ]);
    }

    #[Route('/commandes/{id}', name: 'app_commande_detail')]
    public function detail(CommandeRepository $commandeRepository, int $id): Response
    {
        $commande = $commandeRepository->find($id);

        if (!$commande) {
            throw $this->createNotFoundException('Commande non trouvée.');
        }

        return $this->render('commande/detail.html.twig', [
            'commande' => $commande,
        ]);
    }

    // #[Route('/commande/{id}/reimprimer', name: 'app_commande_reimprimer')]
    // public function reimprimer(
    //     CommandeRepository $commandeRepository,
    //     TicketPrinter $ticketPrinter,
    //     int $id,
    //     EntityManagerInterface $em,
    //     Request $request
    // ): Response {
    //     $commande = $commandeRepository->find($id);
    //     if (!$commande) {
    //         throw $this->createNotFoundException('Commande non trouvée.');
    //     }

    //     $items = [];
    //     foreach ($commande->getProduits() as $produit) {
    //         $items[] = [
    //             'nom' => $produit->getNom(),
    //             'quantite' => $commande->getQuantites()[$produit->getId()] ?? 1,
    //             'prix' => $produit->getPrixHT(),
    //         ];
    //     }

    //     $restaurant = $em->getRepository(Restaurant::class)->findOneBy([]);

    //     $header = [
    //         'nom' => $restaurant->getNom(),
    //         'adresse' => $restaurant->getAdresse(),
    //         'telephone' => $restaurant->getTelephone(),

    //     ];

    //     $ticketPrinter->printTicket([
    //         'id' => $commande->getId(),
    //         'date' => $commande->getDate(),
    //         'items' => $items,
    //         'header' => $header
    //     ]);

    //     // Message de confirmation
    //     $msg = $this->getParameter('kernel.environment') === 'prod'
    //         ? 'Ticket envoyé à l’imprimante !'
    //         : 'Ticket simulé en PDF (dev) !';

    //     $this->addFlash('success', $msg);

    //     return $this->redirectToRoute('app_commande_detail', ['id' => $id]);
    // }
    #[Route('/commande/{id}/reimprimer', name: 'app_commande_reimprimer')]
    public function reimprimer(
        CommandeRepository $commandeRepository,
        TicketPrinter $ticketPrinter,
        int $id,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $commande = $commandeRepository->find($id);
        if (!$commande) {
            throw $this->createNotFoundException('Commande non trouvée.');
        }

        $items = [];
        foreach ($commande->getProduits() as $produit) {
            $tva = $produit->getTauxTva()->getTaux(); // ex: 20
            $prixTTC = $produit->getPrixHT() * (1 + $tva / 100);

            $items[] = [
                'nom' => $produit->getNom(),
                'quantite' => $commande->getQuantites()[$produit->getId()] ?? 1,
                'prix' => $prixTTC, // ✅ prix TTC au lieu de HT
            ];
        }

        $restaurant = $em->getRepository(Restaurant::class)->findOneBy([]);

        $header = [
            'nom' => $restaurant->getNom(),
            'adresse' => $restaurant->getAdresse(),
            'telephone' => $restaurant->getTelephone(),
        ];

        $ticketPrinter->printTicket([
            'id' => $commande->getId(),
            'date' => $commande->getDate(),
            'items' => $items,
            'header' => $header
        ]);

        $msg = $this->getParameter('kernel.environment') === 'prod'
            ? 'Ticket envoyé à l’imprimante !'
            : 'Ticket simulé en PDF (dev) !';

        $this->addFlash('success', $msg);

        return $this->redirectToRoute('app_commande_detail', ['id' => $id]);
    }
}
