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

use App\Entity\Restaurant;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use Doctrine\ORM\EntityManagerInterface;

class RestaurantCrudController extends AbstractCrudController
{

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public static function getEntityFqcn(): string
    {
        return Restaurant::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('nom', 'Nom du restaurant'),
            TextField::new('adresse', 'Adresse')->setRequired(false),
            TextField::new('telephone', 'Téléphone')->setRequired(false),
            ImageField::new('logo', 'Logo')
                ->setUploadDir('public/uploads/logos') // où le fichier est stocké
                ->setBasePath('uploads/logos')         // URL publique pour l’affichage
                ->setRequired(false)
                ->setSortable(false)
        ];
    }
    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);

        $existingCount = $this->em->getRepository(Restaurant::class)->count([]);

        if ($existingCount > 0) {
            $actions->disable(Action::NEW);
        }

        return $actions;
    }
}
