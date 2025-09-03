<?php

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

            foreach ($commande->getProduits() as $produit) {
                $qte = $commande->getQuantites()[$produit->getId()] ?? 1;
                $ht = $produit->getPrixHT() * $qte;
                $ttc = $produit->getPrixTTC() * $qte;

                // Par jour
                $totauxParJour[$jour]['HT'] = ($totauxParJour[$jour]['HT'] ?? 0) + $ht;
                $totauxParJour[$jour]['TTC'] = ($totauxParJour[$jour]['TTC'] ?? 0) + $ttc;

                // Par mois
                $totauxParMois[$mois]['HT'] = ($totauxParMois[$mois]['HT'] ?? 0) + $ht;
                $totauxParMois[$mois]['TTC'] = ($totauxParMois[$mois]['TTC'] ?? 0) + $ttc;

                // Par catégorie
                $cat = $produit->getCategorie()?->getNom() ?? 'Sans catégorie';
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
