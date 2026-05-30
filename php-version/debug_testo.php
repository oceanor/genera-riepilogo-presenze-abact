<?php
require_once __DIR__ . '/includes/pdf_reader.php';

$output = '';
$method = $_POST['method'] ?? 'getText';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
    $tmpName = $_FILES['pdf_file']['tmp_name'];
    try {
        if ($method === 'getDataTm') {
            $output = testo_pdf_dataTm($tmpName);
        } else {
            $output = testo_pdf($tmpName);
        }
    } catch (Exception $e) {
        $output = "Errore durante l'estrazione: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Debug Estrazione PDF</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gray-100 p-8 font-sans">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-bold mb-4">Debug Estrazione Testo PDF</h1>
        
        <form method="post" enctype="multipart/form-data" class="mb-6 flex gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Seleziona PDF</label>
                <input type="file" name="pdf_file" accept=".pdf" required class="block w-full text-sm text-gray-500
                    file:mr-4 file:py-2 file:px-4
                    file:rounded file:border-0
                    file:text-sm file:font-semibold
                    file:bg-blue-50 file:text-blue-700
                    hover:file:bg-blue-100" />
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Metodo Estrazione</label>
                <select name="method" class="block w-full py-2 px-3 border border-gray-300 rounded-md">
                    <option value="getText" <?= $method === 'getText' ? 'selected' : '' ?>>Standard (getText)</option>
                    <option value="getDataTm" <?= $method === 'getDataTm' ? 'selected' : '' ?>>Fallback (getDataTm XY)</option>
                </select>
            </div>
            
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Estrai
            </button>
        </form>

        <?php if ($output): ?>
            <h2 class="text-xl font-bold mb-2 border-b pb-2">Risultato</h2>
            <div class="bg-gray-50 border p-4 rounded overflow-auto max-h-[600px]">
                <pre class="text-sm font-mono whitespace-pre-wrap">
<?php 
// Mostra il testo riga per riga numerato per aiutare il debug
$lines = explode("\n", $output);
foreach ($lines as $i => $line) {
    echo str_pad($i + 1, 4, ' ', STR_PAD_LEFT) . " | " . htmlspecialchars($line) . "\n";
}
?>
                </pre>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
