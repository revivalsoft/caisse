<?php
// src/Controller/Admin/RestaurantCrudController.php
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
