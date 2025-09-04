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

namespace App\Service;

use App\Repository\CommandeRepository;
use DateTimeInterface;

class ReportingService
{
    public function __construct(private readonly CommandeRepository $commandeRepository) {}

    /**
     * Retourne les totaux par jour, par mois, par catégorie et global
     */
    public function getTotaux(?DateTimeInterface $start = null, ?DateTimeInterface $end = null): array
    {
        $commandes = $this->commandeRepository->findCommandesBetween($start, $end);

        $totauxParJour = [];
        $totauxParMois = [];
        $totauxParCategorie = [];
        $totalGlobal = ['HT' => 0, 'TTC' => 0];

        foreach ($commandes as $commande) {
            $jour = $commande->getDate()->format('Y-m-d');
            $mois = $commande->getDate()->format('Y-m');

            // Parcours des lignes de commande
            foreach ($commande->getLignes() as $ligne) {
                $qte = $ligne->getQuantite();
                $ht = $ligne->getPrixHT() * $qte;

                // Calcul du TTC selon le taux de TVA
                $tauxTva = $ligne->getTauxTva(); // ex: 20 pour 20%
                $ttc = $ht * (1 + $tauxTva / 100);

                // Totaux par jour
                $totauxParJour[$jour]['HT'] = ($totauxParJour[$jour]['HT'] ?? 0) + $ht;
                $totauxParJour[$jour]['TTC'] = ($totauxParJour[$jour]['TTC'] ?? 0) + $ttc;

                // Totaux par mois
                $totauxParMois[$mois]['HT'] = ($totauxParMois[$mois]['HT'] ?? 0) + $ht;
                $totauxParMois[$mois]['TTC'] = ($totauxParMois[$mois]['TTC'] ?? 0) + $ttc;

                // Totaux par catégorie (libellé stocké dans la ligne)
                $cat = $ligne->getCategorieLibelle() ?? 'Sans catégorie';
                $totauxParCategorie[$cat]['HT'] = ($totauxParCategorie[$cat]['HT'] ?? 0) + $ht;
                $totauxParCategorie[$cat]['TTC'] = ($totauxParCategorie[$cat]['TTC'] ?? 0) + $ttc;

                // Total global
                $totalGlobal['HT'] += $ht;
                $totalGlobal['TTC'] += $ttc;
            }
        }

        ksort($totauxParJour);
        ksort($totauxParMois);
        ksort($totauxParCategorie);

        return [
            'parJour' => $totauxParJour,
            'parMois' => $totauxParMois,
            'parCategorie' => $totauxParCategorie,
            'global' => $totalGlobal,
        ];
    }
}
