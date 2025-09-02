<?php

namespace App\Controller\Admin;

use App\Entity\TableRestaurant;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class TableRestaurantCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TableRestaurant::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            //IdField::new('id')->onlyOnIndex(),
            TextField::new('nom'),

        ];
    }
}
