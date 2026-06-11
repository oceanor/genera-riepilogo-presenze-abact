<?php

/**
 * Normalizza un nome per confronto (lowercase + spazi collassati).
 */
function normalizza_nome(string $nome): string {
    return trim(preg_replace('/\s+/', ' ', mb_strtolower($nome)));
}

/**
 * Rimuove tutti gli spazi dal nome normalizzato (fallback per nomi spezzati dal PDF).
 */
function nome_senza_spazi(string $nome): string {
    return str_replace(' ', '', normalizza_nome($nome));
}

/**
 * Estrae nome e matricola da un iscritto (supporta formato legacy stringa).
 *
 * @return array{0: string, 1: ?string} [nome, matricola]
 */
function iscritto_nome_matricola($iscritto): array {
    if (is_array($iscritto)) {
        return [
            $iscritto['nome'] ?? '',
            $iscritto['matricola'] ?? null,
        ];
    }
    return [(string)$iscritto, null];
}

/**
 * Cerca presenze per iscritto: matricola → nome → nome senza spazi.
 */
function trova_presenze(array $maps, $iscritto): ?array {
    [$nome, $matricola] = iscritto_nome_matricola($iscritto);

    if ($matricola && isset($maps['byMatricola'][$matricola])) {
        return $maps['byMatricola'][$matricola];
    }

    $key = normalizza_nome($nome);
    if (isset($maps['byNome'][$key])) {
        return $maps['byNome'][$key];
    }

    $compatto = nome_senza_spazi($nome);
    if (isset($maps['byNomeCompatto'][$compatto])) {
        return $maps['byNomeCompatto'][$compatto];
    }

    return null;
}

/**
 * Costruisce le mappe di lookup dalle presenze estratte.
 */
function build_presenze_maps(array $studenti): array {
    $maps = [
        'byMatricola' => [],
        'byNome' => [],
        'byNomeCompatto' => [],
    ];

    foreach ($studenti as $s) {
        if (!empty($s['matricola'])) {
            $maps['byMatricola'][$s['matricola']] = $s;
        }
        $maps['byNome'][normalizza_nome($s['nome'])] = $s;
        $maps['byNomeCompatto'][nome_senza_spazi($s['nome'])] = $s;
    }

    return $maps;
}

/**
 * URL della home dell'app (directory dello script, senza index.php).
 * Con .htaccess root, /presenze-abact/ viene riscritto su php-version/;
 * index.php in root può essere assente o vuoto e non va usato nel link.
 */
function home_url(): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $dir = rtrim(dirname($script), '/');
    return ($dir === '' || $dir === '.') ? './' : $dir . '/';
}

/**
 * Genera l'HTML di riepilogo a partire dai dati analizzati.
 * Utilizza TailwindCSS v4 per lo styling, con un design premium.
 * 
 * @param array $dati Dati strutturati (esiti del parsing)
 * @return string HTML completo
 */
function genera_html(array $dati): string {
    $home = htmlspecialchars(home_url(), ENT_QUOTES, 'UTF-8');
    $html = '<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Riepilogo iscritti esami e presenze - ABACT</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
  <style>
    body { font-family: \'Outfit\', sans-serif; }
    .custom-gradient-bg { background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); }
    @media print {
      @page {
        size: A4 portrait;
        margin: 8mm 10mm;
      }
      .no-print { display: none !important; }
      html, body {
        width: 100% !important;
        max-width: 100% !important;
        overflow: visible !important;
        background: white !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      body {
        min-height: auto !important;
        padding: 0 !important;
        margin: 0 !important;
      }
      .report-container {
        max-width: none !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
      }
      .report-table-wrap {
        overflow: visible !important;
        border-radius: 0 !important;
      }
      .page-break {
        page-break-after: always;
        break-after: page;
        margin-bottom: 0 !important;
        padding: 0.5rem 0 !important;
        border-radius: 0 !important;
        overflow: visible !important;
      }
      .page-break:last-child { page-break-after: auto; break-after: auto; }
      .shadow-xl, .shadow-sm, .shadow-lg { box-shadow: none !important; }
      .print-hide-decor { display: none !important; }
      .overflow-x-auto, .overflow-hidden { overflow: visible !important; }
      table { width: 100% !important; table-layout: fixed; }
      .whitespace-nowrap {
        white-space: normal !important;
        word-break: break-word;
      }
      th, td {
        padding: 0.3rem 0.45rem !important;
        font-size: 10pt !important;
        border-bottom: 1px solid #e2e8f0 !important;
      }
      th { font-size: 9pt !important; }
      h1 { font-size: 17pt !important; color: #1e293b !important; }
      h2 { font-size: 13pt !important; }
      .report-header { padding: 0.5rem 0 !important; margin-bottom: 0.5rem !important; }
      .mb-10, .mb-12, .mb-8, .mb-6 { margin-bottom: 0.4rem !important; }
      .p-8 { padding: 0.5rem 0 !important; }
      .py-10 { padding-top: 0 !important; padding-bottom: 0 !important; }
      td span.inline-flex svg { display: none !important; }
      td span.inline-flex {
        padding: 0.1rem 0.35rem !important;
        font-size: 9pt !important;
        gap: 0 !important;
      }
    }
  </style>
</head>
<body class="custom-gradient-bg text-slate-800 min-h-screen py-10 selection:bg-indigo-200 selection:text-indigo-900">
  <div class="report-container max-w-5xl mx-auto px-4 sm:px-6">
    <div class="no-print flex justify-between items-center mb-8">
      <a href="' . $home . '" class="text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-2 transition bg-white py-2 px-4 rounded-xl shadow-sm border border-indigo-100 hover:shadow-md">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
        Torna alla Home
      </a>
      <button onclick="window.print()" class="bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white font-semibold py-2.5 px-6 rounded-xl shadow-lg shadow-emerald-500/30 transition-all hover:shadow-xl hover:-translate-y-0.5 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
        </svg>
        Stampa Documento
      </button>
    </div>
    
    <div class="report-header bg-white rounded-2xl shadow-xl shadow-slate-200/50 p-8 mb-10 border border-slate-100 relative overflow-hidden">
      <div class="print-hide-decor absolute top-0 right-0 w-64 h-64 bg-indigo-50 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 z-0"></div>
      <h1 class="text-3xl sm:text-4xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-slate-800 to-indigo-900 tracking-tight relative z-10">Riepilogo Esami e Presenze</h1>
      <p class="text-slate-500 font-medium mt-2 relative z-10 uppercase tracking-widest text-sm">Accademia di Belle Arti</p>
    </div>';

    foreach ($dati['corsi'] as $corso) {
        $nomeCorso = htmlspecialchars($corso['nome_esame'] ?: $corso['id']);
        
        $html .= '<div class="page-break mb-12 bg-white rounded-2xl shadow-xl shadow-slate-200/40 p-8 border border-slate-100">';
        $html .= '<div class="flex items-center gap-4 mb-6 border-b border-slate-100 pb-4">';
        $html .= '<div class="w-2 h-8 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-full"></div>';
        $html .= '<h2 class="text-2xl font-bold text-slate-800 tracking-tight">' . $nomeCorso . '</h2>';
        $html .= '</div>';
        
        $presenzeMaps = build_presenze_maps($corso['studenti_con_presenze'] ?? []);
        
        $hasPres = !empty($corso['studenti_con_presenze']);
        
        $html .= '<div class="report-table-wrap overflow-x-auto rounded-xl border border-slate-200">';
        $html .= '<table class="w-full text-left border-collapse">';
        
        if ($hasPres) {
            $html .= '<thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200">
                          <th class="py-4 px-5 font-semibold text-slate-700 text-sm uppercase tracking-wider">Nome Studente</th>
                          <th class="py-4 px-5 font-semibold text-slate-700 text-sm uppercase tracking-wider text-center">Assenze</th>
                          <th class="py-4 px-5 font-semibold text-slate-700 text-sm uppercase tracking-wider text-center">Presenze</th>
                          <th class="py-4 px-5 font-semibold text-slate-700 text-sm uppercase tracking-wider text-right">Esito</th>
                        </tr>
                      </thead>';
        } else {
            $html .= '<thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200">
                          <th class="py-4 px-5 font-semibold text-slate-700 text-sm uppercase tracking-wider">Nome Studente</th>
                        </tr>
                      </thead>';
        }
        
        $html .= '<tbody class="divide-y divide-slate-100">';
        
        $iscritti = $corso['iscritti_esame'] ?? [];
        foreach ($iscritti as $iscritto) {
            [$nome, ] = iscritto_nome_matricola($iscritto);
            $p = trova_presenze($presenzeMaps, $iscritto);
            
            $html .= '<tr class="hover:bg-slate-50/50 transition-colors">';
            $html .= '<td class="py-3.5 px-5 whitespace-nowrap font-medium text-slate-700">' . htmlspecialchars($nome) . '</td>';
            
            if ($hasPres) {
                if ($p) {
                    $ass = $p['assenze'];
                    $pre = $p['presenze'];
                    $perc = $p['percentuale'];
                    
                    if ($perc < 50) {
                        $badge = '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-bold bg-rose-100 text-rose-700 border border-rose-200 shadow-sm"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>' . $perc . '%</span>';
                    } else {
                        $badge = '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-bold bg-emerald-100 text-emerald-700 border border-emerald-200 shadow-sm"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>' . $perc . '%</span>';
                    }
                    
                    $html .= '<td class="py-3.5 px-5 text-center text-slate-500 font-medium">' . $ass . '</td>';
                    $html .= '<td class="py-3.5 px-5 text-center text-slate-700 font-bold">' . $pre . '</td>';
                    $html .= '<td class="py-3.5 px-5 text-right">' . $badge . '</td>';
                } else {
                    $html .= '<td class="py-3.5 px-5 text-center text-slate-300 font-medium">&mdash;</td>';
                    $html .= '<td class="py-3.5 px-5 text-center text-slate-300 font-medium">&mdash;</td>';
                    $html .= '<td class="py-3.5 px-5 text-right"><span class="inline-flex px-3 py-1 rounded-full text-sm font-medium bg-slate-100 text-slate-400">&mdash;</span></td>';
                }
            }
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table></div></div>';
    }
    
    $html .= '
  </div>
</body>
</html>';

    return $html;
}

/**
 * Genera uno slug sicuro per ID HTML (non strettamente necessario qui, ma mantenuto per compatibilità)
 */
function slug(string $nome): string {
    $slug = trim(preg_replace('/[^\w\s-]/u', '', $nome));
    $slug = preg_replace('/\s+/', '-', $slug);
    return $slug ?: 'corso';
}

