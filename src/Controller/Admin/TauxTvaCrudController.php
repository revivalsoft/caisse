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
