<?php

/**
 * Estrae i nomi degli iscritti da un file PDF di lista prenotati esame.
 * Formato atteso nel testo:
 * progressivo  matricola  nome cognome  sigla_corso  numero  numero
 * 
 * @param string $testo Testo grezzo estratto dal PDF
 * @return array Array di nomi univoci
 */
function estrai_iscritti(string $testo): array {
    $nomi = [];
    $lines = explode("\n", $testo);
    
    foreach ($lines as $line) {
        $line = trim($line);
        // Regex: 
        // ^\d+          -> progressivo
        // \s+\d{5}      -> matricola (5 cifre)
        // \s+(.+?)      -> cattura nome (lazy)
        // \s+D[A-Z]{2}L\d+_[A-Z0-9]+ -> sigla corso (es. DASL11_FOT)
        // \s+\d+\s+\d+  -> due numeri finali
        if (preg_match('/^\d+\s+\d{5}\s+(.+?)\s+D[A-Z]{2}L\d+_[A-Z0-9]+\s+\d+\s+\d+/', $line, $m)) {
            $nome = trim($m[1]);
            // Filtra nomi troppo corti (artefatti)
            if ($nome && mb_strlen($nome) > 2) {
                $nomi[$nome] = true; // usa chiave per deduplicare preservando ordine originale
            }
        }
    }
    
    return array_keys($nomi);
}
