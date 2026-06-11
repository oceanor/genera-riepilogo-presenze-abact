<?php
require_once __DIR__ . '/includes/genera_html.php';

$mock_data = [
    'corsi' => [
        [
            'id' => 'storia-arte',
            'nome_esame' => 'Storia dell\'Arte Contemporanea',
            'iscritti_esame' => [
                ['nome' => 'Mario Rossi', 'matricola' => '10001'],
                ['nome' => 'Luigi Verdi', 'matricola' => '10002'],
                ['nome' => 'Giulia Bianchi', 'matricola' => '10003'],
                ['nome' => 'Anna Neri', 'matricola' => '10004'],
            ],
            'studenti_con_presenze' => [
                ['nome' => 'Mario Rossi', 'matricola' => '10001', 'assenze' => 2, 'presenze' => 8, 'percentuale' => 80],
                ['nome' => 'Luigi Verdi', 'matricola' => '10002', 'assenze' => 6, 'presenze' => 4, 'percentuale' => 40],
                ['nome' => 'Giulia Bianchi', 'matricola' => '10003', 'assenze' => 0, 'presenze' => 10, 'percentuale' => 100],
            ]
        ],
        [
            'id' => 'fotografia',
            'nome_esame' => 'Fotografia 1',
            'iscritti_esame' => [
                ['nome' => 'Carlo Marroni', 'matricola' => '20001'],
                ['nome' => 'Elena Gialli', 'matricola' => '20002'],
            ],
            'studenti_con_presenze' => [] // Nessuna presenza per questo corso
        ]
    ]
];

$html = genera_html($mock_data);
file_put_contents(__DIR__ . '/mock_output.html', $html);
echo "File mock_output.html generato con successo.\n";
