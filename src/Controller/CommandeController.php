<?php

/*
 * Zoomerplanning - Logiciel de caisse pour restaurants
 * Copyright (C) 2025 RevivalSoft
 *
 * Ce programme est un logiciel libre ; vous pouvez le redistribuer et/ou
 * le modifier selon les termes de la Licence Publique Générale GNU publiée
 * par la Free Software Foundation Version 3.
 *
 * Ce programme est distribué dans l'espoir qu'il sera utile,
 * mais SANS AUCUNE GARANTIE ; sans même la garantie implicite de
 * COMMERCIALISATION ou D’ADÉQUATION À UN BUT PARTICULIER. Voir la
 * Licence Publique Générale GNU pour plus de détails.
 *
 * Vous devriez avoir reçu une copie de la Licence Publique Générale GNU
 * avec ce programme ; si ce n'est pas le cas, voir
 * <https://www.gnu.org/licenses/>.
 */

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

        // Filtrage par date
        if ($date) {
            $start = new \DateTime($date);
            $end = (clone $start)->modify('+1 day');

            $queryBuilder
                ->andWhere('c.date >= :start')
                ->andWhere('c.date < :end')
                ->setParameter('start', $start)
                ->setParameter('end', $end);
        }

        // Filtrage par table
        if ($tableId) {
            $queryBuilder
                ->andWhere('c.table = :table')
                ->setParameter('table', $tableId);
        }

        // Pagination
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

        // Préparer les items à afficher depuis les lignes
        $items = [];
        foreach ($commande->getLignes() as $ligne) {
            $prixTTC = $ligne->getPrixHt() * (1 + $ligne->getTauxTva() / 100);
            $items[] = [
                'nom' => $ligne->getLibelleProduit(),
                'quantite' => $ligne->getQuantite(),
                'prix' => $prixTTC,
                'categorie' => $ligne->getCategorieLibelle(),
            ];
        }

        return $this->render('commande/detail.html.twig', [
            'commande' => $commande,
            'items' => $items,
        ]);
    }

    #[Route('/commande/{id}/reimprimer', name: 'app_commande_reimprimer')]
    public function reimprimer(
        CommandeRepository $commandeRepository,
        TicketPrinter $ticketPrinter,
        int $id,
        EntityManagerInterface $em
    ): Response {
        $commande = $commandeRepository->find($id);
        if (!$commande) {
            throw $this->createNotFoundException('Commande non trouvée.');
        }

        // Préparer les items à envoyer à l'imprimante
        $items = [];
        foreach ($commande->getLignes() as $ligne) {
            $prixTTC = $ligne->getPrixHt() * (1 + $ligne->getTauxTva() / 100);
            $items[] = [
                'nom' => $ligne->getLibelleProduit(),
                'quantite' => $ligne->getQuantite(),
                'prix' => $prixTTC,
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
