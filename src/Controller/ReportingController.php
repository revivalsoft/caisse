<?php

namespace App\Controller;

use App\Service\ReportingService;
use DateTimeImmutable;
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

        // $startDate = $startStr ? new DateTimeImmutable($startStr) : null;
        // $endDate   = $endStr ? new DateTimeImmutable($endStr) : null;

        [$startDate, $endDate] = $this->adjustDateRange($startStr, $endStr);


        $totaux = $this->reportingService->getTotaux($startDate, $endDate);

        return $this->render('reporting/index.html.twig', [
            'totauxParJour' => $totaux['parJour'],
            'totauxParMois' => $totaux['parMois'],
            'totauxParCategorie' => $totaux['parCategorie'],
            'totalGlobal' => $totaux['global'],
            'start' => $startDate?->format('Y-m-d'),
            'end' => $endDate?->format('Y-m-d'),
        ]);
    }

    private function adjustDateRange(?string $startStr, ?string $endStr): array
    {
        $start = $startStr ? new \DateTimeImmutable($startStr . ' 00:00:00') : null;
        $end   = $endStr ? new \DateTimeImmutable($endStr . ' 23:59:59') : null;
        return [$start, $end];
    }
}
