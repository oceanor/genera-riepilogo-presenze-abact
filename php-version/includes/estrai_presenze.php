<?php

/**
 * Estrae i dati di presenza dal testo di un PDF (registro presenze).
 * Implementa un parser a stato che cerca nomi in maiuscolo seguiti dal marker "P A".
 * 
 * @param string $testo Testo grezzo estratto dal PDF
 * @return array Array di dizionari con chiavi: nome, assenze, presenze, percentuale
 */
function estrai_presenze(string $testo): array {
    $risultati = [];
    $lines = explode("\n", $testo);
    $count = count($lines);
    $i = 0;

    while ($i < $count) {
        $line = trim($lines[$i]);

        // Step 1: Verifica se è un nome candidato (tutto maiuscolo, 1-5 parole)
        if (preg_match('/^[A-ZÀÈÉÌÒÙ\s\'-]+$/u', $line)
            && ($wc = count(preg_split('/\s+/', $line))) >= 1 
            && $wc <= 5
            && strpos($line, 'Matricola') === false
            && strpos($line, 'P A') === false
            && mb_strlen($line) > 2
        ) {
            $nome_candidate = $line;
            $j = $i + 1;

            // Step 2: Accumula righe successive che appartengono allo stesso nome
            while ($j < $count 
                && trim($lines[$j]) !== ''
                && preg_match('/^[A-ZÀÈÉÌÒÙ\s\'-]+$/u', trim($lines[$j]))
                && strpos($lines[$j], 'Matricola') === false
            ) {
                $nome_candidate .= ' ' . trim($lines[$j]);
                $j++;
            }

            // Normalizza spazi
            $nome_candidate = preg_replace('/\s+/', ' ', trim($nome_candidate));
            $word_count = count(explode(' ', $nome_candidate));

            if ($word_count >= 2 && $word_count <= 6) {
                // Step 3: Cerca "P A" nelle 15 righe successive
                $k = $j;
                while ($k < min($j + 15, $count)) {
                    if (strpos($lines[$k], 'P A') !== false) {
                        $block = implode(' ', array_slice($lines, $k, 14));

                        // Step 4a: Estrai formato giorni (Xg)
                        preg_match_all('/(\d+)g/', $block, $giorni);
                        if (count($giorni[1]) >= 2) {
                            $presenze_g = (int)$giorni[1][0];
                            $assenze_g  = (int)$giorni[1][1];
                        } else {
                            // Step 4b: Estrai formato ore (XX:00h) - fallback
                            preg_match_all('/(\d+):00h/', $block, $ore_p);
                            preg_match_all('/(\d+)%/', $block, $perc);
                            
                            if (count($ore_p[1]) >= 2 && count($perc[1]) >= 2) {
                                $presenze_g = intdiv((int)$ore_p[1][0], 6);
                                $assenze_g  = intdiv((int)$ore_p[1][1], 6);
                            } else {
                                $presenze_g = 0;
                                $assenze_g  = 0;
                            }
                        }

                        // Step 5: Calcola percentuale
                        $tot = $presenze_g + $assenze_g;
                        $percentuale = $tot > 0 ? round(100 * $presenze_g / $tot, 1) : 0;

                        $risultati[] = [
                            'nome'        => $nome_candidate,
                            'assenze'     => $assenze_g,
                            'presenze'    => $presenze_g,
                            'percentuale' => $percentuale,
                        ];
                        break;
                    }
                    $k++;
                }
            }
            $i = $j;
            continue;
        }
        $i++;
    }
    
    return $risultati;
}

/**
 * Unisce i risultati di più registri presenze (PDF multipli).
 * L'ultimo record sovrascrive i precedenti se c'è stesso nome.
 * 
 * @param array $lista_per_pdf Array di array di risultati da estrai_presenze
 * @return array Array unito di presenze
 */
function merge_presenze(array $lista_per_pdf): array {
    $by_name = [];
    foreach ($lista_per_pdf as $lista) {
        foreach ($lista as $studente) {
            $by_name[$studente['nome']] = $studente;
        }
    }
    return array_values($by_name);
}
