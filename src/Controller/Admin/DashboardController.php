<?php

namespace App\Controller\Admin;

use App\Entity\Categorie;
use App\Entity\Produit;
use App\Entity\TauxTva;
use App\Entity\TableRestaurant;
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

        yield MenuItem::linkToUrl('Accès au restaurant', 'fas fa-globe', $this->generateUrl('caisse_tables'));
    }
}
