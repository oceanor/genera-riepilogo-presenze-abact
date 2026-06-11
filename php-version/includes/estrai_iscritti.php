<?php

/**
 * Estrae gli iscritti da un file PDF di lista prenotati esame.
 * Formato atteso nel testo:
 * progressivo  matricola  nome cognome  sigla_corso  numero  numero
 *
 * @param string $testo Testo grezzo estratto dal PDF
 * @return array Array di record con chiavi: nome, matricola
 */
function estrai_iscritti(string $testo): array {
    $iscritti = [];
    $seen = [];
    $lines = explode("\n", $testo);

    foreach ($lines as $line) {
        $line = trim($line);
        if (preg_match('/^\d+\s+(\d{5})\s+(.+?)\s+D[A-Z]{2}L\d+_[A-Z0-9]+\s+\d+\s+\d+/', $line, $m)) {
            $matricola = $m[1];
            $nome = trim($m[2]);
            if ($nome && mb_strlen($nome) > 2) {
                $key = $matricola ?: $nome;
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $iscritti[] = [
                        'nome' => $nome,
                        'matricola' => $matricola,
                    ];
                }
            }
        }
    }

    return $iscritti;
}
