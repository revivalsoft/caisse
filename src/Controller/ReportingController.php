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

namespace App\Controller;

use App\Service\ReportingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reporting')]
class ReportingController extends AbstractController
{
    public function __construct(private readonly ReportingService $reportingService) {}

    #[Route('/', name: 'reporting_index')]
    public function index(Request $request): Response
    {
        $startStr = $request->query->get('start');
        $endStr   = $request->query->get('end');

        [$startDate, $endDate] = $this->adjustDateRange($startStr, $endStr);

        // Récupération des totaux depuis les lignes immuables
        $totaux = $this->reportingService->getTotaux($startDate, $endDate);

        return $this->render('reporting/index.html.twig', [
            'totauxParJour'       => $totaux['parJour'],
            'totauxParMois'       => $totaux['parMois'],
            'totauxParCategorie'  => $totaux['parCategorie'],
            'totalGlobal'         => $totaux['global'],
            'start'               => $startDate?->format('Y-m-d'),
            'end'                 => $endDate?->format('Y-m-d'),
        ]);
    }

    /**
     * Ajuste les dates pour inclure toute la journée
     */
    private function adjustDateRange(?string $startStr, ?string $endStr): array
    {
        $start = $startStr ? new \DateTimeImmutable($startStr . ' 00:00:00') : null;
        $end   = $endStr ? new \DateTimeImmutable($endStr . ' 23:59:59') : null;
        return [$start, $end];
    }
}
