<?php

namespace App\Controller\Admin;

use App\Entity\Produit;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProduitCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Produit::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            TextField::new('nom', 'Nom du produit'),

            NumberField::new('prixHT', 'Prix HT')
                ->setNumDecimals(2)
                ->setFormTypeOptions([
                    'html5' => true,
                    'attr' => ['step' => '0.01'], // permet les décimales
                ])
                ->formatValue(fn($v, $entity) => '€ ' . number_format($entity->getPrixHT(), 2, '.', '')),

            AssociationField::new('categorie', 'Catégorie'),
            AssociationField::new('tauxTva', 'Taux de TVA'),
        ];

        if ($pageName === 'index') {
            $fields[] = NumberField::new('prixTTC', 'Prix TTC')
                ->setVirtual(true) // calculé, pas stocké en base
                ->setNumDecimals(2)
                ->formatValue(fn($v, $entity) => '€ ' . number_format($entity->getPrixTTC(), 2, '.', ''));
        }

        return $fields;
    }
}
