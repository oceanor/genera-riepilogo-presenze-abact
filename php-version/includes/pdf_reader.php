<?php
require_once __DIR__ . '/../lib/pdfparser/alt_autoload.php';

use Smalot\PdfParser\Parser;

/**
 * Estrae il testo da un PDF pagina per pagina.
 * Comportamento simile a pypdf.
 */
function testo_pdf(string $path): string {
    $parser = new Parser();
    $pdf = $parser->parseFile($path);
    $text = '';
    foreach ($pdf->getPages() as $page) {
        $text .= $page->getText() . "\n";
    }
    return $text;
}

/**
 * Fallback: estrae il testo usando le coordinate X,Y.
 * Utile se getText() produce testo disordinato.
 */
function testo_pdf_dataTm(string $path): string {
    $parser = new Parser();
    $pdf = $parser->parseFile($path);
    $all = '';
    foreach ($pdf->getPages() as $page) {
        $data = $page->getDataTm();
        $rows = [];
        foreach ($data as $item) {
            // Arrotonda coordinata Y per raggruppare testi sulla stessa linea visiva
            $y = round($item[0][5], 0);
            $rows[$y][] = [
                'x' => $item[0][4],
                'text' => $item[1]
            ];
        }
        
        // Ordina le righe dall'alto in basso (coordinata Y decrescente)
        krsort($rows);
        
        foreach ($rows as $cols) {
            // Ordina i frammenti della riga da sinistra a destra (X crescente)
            usort($cols, function($a, $b) {
                return $a['x'] <=> $b['x'];
            });
            $all .= implode(' ', array_column($cols, 'text')) . "\n";
        }
        $all .= "\n";
    }
    return $all;
}
