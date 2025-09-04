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

namespace App\Controller\Admin;

use App\Entity\Categorie;
use App\Entity\Produit;
use App\Entity\TauxTva;
use App\Entity\TableRestaurant;
use App\Entity\Restaurant;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'restaurantImage' => 'images/restaurant.webp',

        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Caisse Admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Catégories', 'fa fa-tags', Categorie::class);
        yield MenuItem::linkToCrud('Produits', 'fa fa-box', Produit::class);
        yield MenuItem::linkToCrud('Taux TVA', 'fa fa-percent', TauxTva::class);
        yield MenuItem::linkToCrud('Tables du restaurant', 'fas fa-chair', TableRestaurant::class);
        yield MenuItem::linkToCrud('Restaurant', 'fas fa-utensils', Restaurant::class);

        yield MenuItem::section();

        yield MenuItem::linkToUrl('Accès au restaurant', 'fas fa-globe', $this->generateUrl('caisse_tables'));
    }
}
