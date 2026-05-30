<?php
require_once __DIR__ . '/includes/genera_html.php';

$mock_data = [
    'corsi' => [
        [
            'id' => 'storia-arte',
            'nome_esame' => 'Storia dell\'Arte Contemporanea',
            'iscritti_esame' => ['Mario Rossi', 'Luigi Verdi', 'Giulia Bianchi', 'Anna Neri'],
            'studenti_con_presenze' => [
                ['nome' => 'Mario Rossi', 'assenze' => 2, 'presenze' => 8, 'percentuale' => 80],
                ['nome' => 'Luigi Verdi', 'assenze' => 6, 'presenze' => 4, 'percentuale' => 40],
                ['nome' => 'Giulia Bianchi', 'assenze' => 0, 'presenze' => 10, 'percentuale' => 100],
            ]
        ],
        [
            'id' => 'fotografia',
            'nome_esame' => 'Fotografia 1',
            'iscritti_esame' => ['Carlo Marroni', 'Elena Gialli'],
            'studenti_con_presenze' => [] // Nessuna presenza per questo corso
        ]
    ]
];

$html = genera_html($mock_data);
file_put_contents(__DIR__ . '/mock_output.html', $html);
echo "File mock_output.html generato con successo.\n";
