<?php
require_once __DIR__ . '/includes/pdf_reader.php';
require_once __DIR__ . '/includes/estrai_iscritti.php';
require_once __DIR__ . '/includes/estrai_presenze.php';
require_once __DIR__ . '/includes/genera_html.php';

// Aumenta il tempo di esecuzione e la memoria (il parsing di grandi PDF può essere intensivo)
ini_set('max_execution_time', '300');
ini_set('memory_limit', '512M');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Accesso negato.");
}

$upload_dir = __DIR__ . '/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Helper per pulire la cartella uploads al termine
function cleanup_uploads(array $files) {
    foreach ($files as $file) {
        if (file_exists($file)) {
            @unlink($file);
        }
    }
}

$temp_files = [];
$corsi = [];
$errori = [];

try {
    $nomi_materia = $_POST['nome_materia'] ?? [];
    $file_prenotati = $_FILES['file_prenotati'] ?? [];

    foreach ($nomi_materia as $index => $nome_materia) {
        $nome_materia = trim($nome_materia);
        if (empty($nome_materia)) continue;

        // 1. Elabora file prenotati
        if (!isset($file_prenotati['tmp_name'][$index]) || $file_prenotati['error'][$index] !== UPLOAD_ERR_OK) {
            $errori[] = "Errore nel caricamento del file prenotati per '$nome_materia'.";
            continue;
        }

        $path_prenotati = $upload_dir . uniqid('prenotati_') . '.pdf';
        if (!move_uploaded_file($file_prenotati['tmp_name'][$index], $path_prenotati)) {
            $errori[] = "Impossibile salvare il file prenotati per '$nome_materia'.";
            continue;
        }
        $temp_files[] = $path_prenotati;

        // Estrai testo da prenotati
        try {
            $testo_prenotati = testo_pdf($path_prenotati);
            $iscritti = estrai_iscritti($testo_prenotati);
            
            // Fallback strategy: se l'estrazione standard non ha trovato iscritti, proviamo con getDataTm
            if (empty($iscritti)) {
                $testo_prenotati_alt = testo_pdf_dataTm($path_prenotati);
                $iscritti = estrai_iscritti($testo_prenotati_alt);
            }
        } catch (Exception $e) {
            $errori[] = "Errore lettura PDF prenotati per '$nome_materia': " . $e->getMessage();
            continue;
        }

        // 2. Elabora file presenze
        // L'input presenze ha il nome dinamico file_presenze_{index}[] creato via JS
        $key_presenze = 'file_presenze_' . $index;
        $liste_presenze = [];
        
        if (isset($_FILES[$key_presenze])) {
            $presenze_files = $_FILES[$key_presenze];
            
            if (is_array($presenze_files['tmp_name'])) {
                foreach ($presenze_files['tmp_name'] as $i => $tmp_name) {
                    if ($presenze_files['error'][$i] === UPLOAD_ERR_OK) {
                        $path_presenze = $upload_dir . uniqid('presenze_') . '.pdf';
                        if (move_uploaded_file($tmp_name, $path_presenze)) {
                            $temp_files[] = $path_presenze;
                            
                            try {
                                $testo_pres = testo_pdf($path_presenze);
                                $dati_pres = estrai_presenze($testo_pres);
                                
                                // Fallback
                                if (empty($dati_pres)) {
                                    $testo_pres_alt = testo_pdf_dataTm($path_presenze);
                                    $dati_pres = estrai_presenze($testo_pres_alt);
                                }
                                
                                if (!empty($dati_pres)) {
                                    $liste_presenze[] = $dati_pres;
                                }
                            } catch (Exception $e) {
                                $errori[] = "Errore lettura file presenze per '$nome_materia': " . $e->getMessage();
                            }
                        }
                    }
                }
            }
        }

        $studenti_presenze = !empty($liste_presenze) ? merge_presenze($liste_presenze) : [];

        $corsi[] = [
            'id' => slug($nome_materia),
            'nome_esame' => $nome_materia,
            'iscritti_esame' => $iscritti,
            'studenti_con_presenze' => $studenti_presenze
        ];
    }

    if (empty($corsi) && empty($errori)) {
        $errori[] = "Nessun dato valido estratto.";
    }

    if (!empty($errori)) {
        // Se ci sono errori, mostra un avviso ma stampa comunque l'HTML generato per le materie valide
        $html_errori = "<div class='max-w-5xl mx-auto px-6 mt-8'><div class='bg-red-50 border-l-4 border-red-500 p-4 rounded-md'><h3 class='text-sm font-medium text-red-800'>Avvisi durante l'elaborazione:</h3><ul class='mt-2 text-sm text-red-700 list-disc list-inside'>";
        foreach ($errori as $err) {
            $html_errori .= "<li>" . htmlspecialchars($err) . "</li>";
        }
        $html_errori .= "</ul></div></div>";
    }

    // Genera output
    $out = ['corsi' => $corsi];
    $html_output = genera_html($out);
    
    if (!empty($errori)) {
        // Inserisce gli errori appena dopo il body
        $html_output = preg_replace('/<body[^>]*>/', '$0' . $html_errori, $html_output, 1);
    }

    echo $html_output;

} finally {
    // Pulizia dei file temporanei
    cleanup_uploads($temp_files);
}
