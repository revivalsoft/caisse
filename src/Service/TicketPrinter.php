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

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\DummyPrintConnector;
use Dompdf\Dompdf;
use Dompdf\Options;

class TicketPrinter
{
    private string $mode; // 'dev' ou 'prod'
    private string $ip;
    private int $port;

    public function __construct(string $mode = 'dev', string $ip = '', int $port = 9100)
    {
        $this->mode = $mode;
        $this->ip = $ip;
        $this->port = $port;
    }

    public function printTicket(array $commande): void
    {
        if ($this->mode === 'prod') {
            $this->printProd($commande);
        } else {
            $this->printDevPdf($commande);
        }
    }

    // -----------------------------
    // Mode production : imprimante réseau
    // -----------------------------
    private function printProd(array $commande): void
    {
        $connector = new NetworkPrintConnector($this->ip, $this->port);
        $printer = new Printer($connector);

        try {
            $this->writeEscpos($printer, $commande);
        } finally {
            $printer->close();
        }
    }

    // -----------------------------
    // Mode développement : PDF 58mm
    // -----------------------------
    private function printDevPdf(array $commande): void
    {
        $header = $commande['header'] ?? [];

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>
        @page { size: 58mm auto; margin: 0; }
        body {
            font-family: monospace;
            font-size: 11pt;
            width: 58mm;
            margin: 0;
            padding: 0;
        }
        .center { text-align:center; }
        .right { text-align:right; }
        .line { border-bottom:1px dashed #000; margin:3px 0; }
        .product { display:flex; justify-content:space-between; margin:1px 0; word-wrap: break-word; }
        .product-name { max-width: 60%; display:inline-block; vertical-align:top; }
        .product-qty { width: 15%; text-align:right; display:inline-block; }
        .product-price { width: 25%; text-align:right; display:inline-block; }
    </style></head><body>';

        // En-tête restaurant
        $html .= '<div class="center">';

        $html .= '<strong>' . ($header['nom'] ?? 'Mon Restaurant') . '</strong><br>';
        $html .= ($header['adresse'] ?? '') . '<br>';
        $html .= ($header['telephone'] ?? '');
        $html .= '</div><div class="line"></div>';

        // Commande
        $html .= '<div class="center">Commande n°' . $commande['id'] . '</div>';
        $html .= '<div class="center">' . $commande['date']->format('d/m/Y H:i') . '</div>';
        $html .= '<div class="line"></div>';

        $total = 0;
        foreach ($commande['items'] as $item) {
            $lineTotal = $item['prix'] * $item['quantite'];
            $total += $lineTotal;

            $html .= '<div class="product">
            <span class="product-name">' . htmlspecialchars($item['nom']) . '</span>
            <span class="product-qty">' . $item['quantite'] . '</span>
            <span class="product-price">' . number_format($lineTotal, 2) . '€</span>
        </div>';
        }

        $html .= '<div class="line"></div>';
        $html .= '<div class="right"><strong>TOTAL: ' . number_format($total, 2) . '€</strong></div>';
        $html .= '<div class="center">Merci pour votre visite !</div>';
        $html .= '</body></html>';

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $file = __DIR__ . '/../../public/tickets/ticket_' . $commande['id'] . '.pdf';
        file_put_contents($file, $dompdf->output());
    }


    // -----------------------------
    // Écriture ESC/POS
    // -----------------------------
    private function writeEscpos(Printer $printer, array $commande): void
    {
        $header = $commande['header'] ?? [];

        $printer->setJustification(Printer::JUSTIFY_CENTER);


        $printer->setTextSize(2, 2);
        $printer->text(($header['nom'] ?? 'Mon Restaurant') . "\n");
        $printer->setTextSize(1, 1);
        if (!empty($header['adresse'])) $printer->text($header['adresse'] . "\n");
        if (!empty($header['telephone'])) $printer->text($header['telephone'] . "\n");
        $printer->text("----------------------------\n");

        // Commande
        $printer->setTextSize(1, 1);
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $total = 0;
        foreach ($commande['items'] as $item) {
            $line = sprintf("%-15s %2dx %5.2f€", $item['nom'], $item['quantite'], $item['prix'] * $item['quantite']);
            $printer->text($line . "\n");
            $total += $item['prix'] * $item['quantite'];
        }

        $printer->text("----------------------------\n");
        $printer->setJustification(Printer::JUSTIFY_RIGHT);
        $printer->text(sprintf("TOTAL: %5.2f€\n", $total));
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("Merci pour votre visite !\n\n\n");
        $printer->cut();
    }
}
