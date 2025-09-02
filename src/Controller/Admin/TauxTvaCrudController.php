<?php

namespace App\Controller\Admin;

use App\Entity\TauxTva;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;


class TauxTvaCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TauxTva::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('libelle', 'Libellé'),
            NumberField::new('taux', 'Taux TVA (%)')
                ->setNumDecimals(2)
                ->setFormTypeOptions([
                    'html5' => true,
                    'attr' => ['step' => '0.01'],
                ])
                ->formatValue(fn($value, $entity) => number_format($value, 2, ',', '.') . ' %'),
        ];
    }
}
