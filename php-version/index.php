<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Accademia di Belle Arti - Riepilogo iscritti esami e presenze</title>
  
  <!-- Font moderno -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
  
  <style>
    body {
      font-family: 'Outfit', sans-serif;
    }
    .glass-panel {
      background: rgba(255, 255, 255, 0.7);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.5);
    }
    .custom-gradient-bg {
      background: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 100%);
    }
    /* Animazioni custom */
    @keyframes slideDown {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .animate-slide-down {
      animation: slideDown 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
  </style>
</head>
<body class="custom-gradient-bg text-slate-800 min-h-screen relative overflow-x-hidden selection:bg-indigo-200 selection:text-indigo-900">
  
  <!-- Decorazioni di background -->
  <div class="fixed top-0 left-0 w-full h-full overflow-hidden -z-10 pointer-events-none">
    <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[50%] rounded-full bg-indigo-300/30 blur-3xl"></div>
    <div class="absolute top-[40%] -right-[10%] w-[40%] h-[60%] rounded-full bg-emerald-200/30 blur-3xl"></div>
  </div>

  <header class="glass-panel sticky top-0 z-20 shadow-sm">
    <div class="max-w-6xl mx-auto px-6 py-5 flex flex-col sm:flex-row justify-between items-center gap-4">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/30">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <div>
          <h1 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-slate-800 to-indigo-900 tracking-tight">Riepilogo Presenze</h1>
          <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Accademia di Belle Arti</p>
        </div>
      </div>
      <div class="flex gap-3 w-full sm:w-auto">
        <button type="button" id="btn-add" class="flex-1 sm:flex-none bg-white hover:bg-slate-50 text-indigo-700 font-semibold py-2.5 px-5 border border-indigo-200 rounded-xl shadow-sm transition-all duration-300 hover:shadow hover:-translate-y-0.5 flex items-center justify-center gap-2 group">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500 group-hover:rotate-90 transition-transform duration-300" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
          </svg>
          Aggiungi materia
        </button>
        <button type="submit" form="form-main" id="btn-submit" class="flex-1 sm:flex-none bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white font-semibold py-2.5 px-6 rounded-xl shadow-lg shadow-emerald-500/30 transition-all duration-300 hover:shadow-xl hover:shadow-emerald-500/40 hover:-translate-y-0.5 flex items-center justify-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
          </svg>
          Genera riepilogo
        </button>
      </div>
    </div>
  </header>

  <main class="max-w-6xl mx-auto px-6 py-10 relative z-10">
    
    <div id="error-container" class="hidden mb-8 glass-panel border-l-4 border-rose-500 p-5 rounded-xl shadow-sm animate-slide-down">
      <div class="flex items-start">
        <div class="flex-shrink-0 mt-0.5">
          <svg class="h-6 w-6 text-rose-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <div class="ml-3">
          <h3 class="text-md font-semibold text-rose-800">Sono stati riscontrati dei problemi:</h3>
          <ul id="error-list" class="mt-2 text-sm text-rose-700/90 list-disc list-inside space-y-1 font-medium"></ul>
        </div>
      </div>
    </div>

    <form id="form-main" action="genera.php" method="POST" enctype="multipart/form-data">
      <div id="righe-container" class="space-y-6">
        <!-- Righe aggiunte via JS -->
      </div>
    </form>
    
  </main>

  <!-- Template riga nascosto -->
  <template id="riga-template">
    <div class="riga-materia glass-panel rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 p-6 relative group overflow-hidden border border-white/60">
      
      <!-- Sottile gradiente superiore decorativo per ogni card -->
      <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-indigo-400 to-purple-400 opacity-70"></div>

      <button type="button" class="btn-remove absolute top-4 right-4 text-slate-400 hover:text-rose-500 transition-colors duration-200 bg-white hover:bg-rose-50 p-2 rounded-full opacity-0 group-hover:opacity-100 focus:opacity-100 shadow-sm z-10" title="Rimuovi materia">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </button>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 relative z-0">
        
        <!-- Nome Materia -->
        <div class="lg:col-span-12">
          <label class="block text-sm font-semibold text-slate-700 mb-2">Nome della materia</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h8a2 2 0 012 2v10a2 2 0 002 2H4a2 2 0 01-2-2V5zm3 1h6v2H5V6zm6 6H5v2h6v-2z" clip-rule="evenodd" />
              </svg>
            </div>
            <input type="text" name="nome_materia[]" class="w-full pl-10 pr-4 py-3 bg-white/80 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-slate-800 font-medium placeholder-slate-400 transition-all outline-none shadow-sm" placeholder="Es. Storia dell'Arte Contemporanea">
          </div>
        </div>
        
        <!-- File Prenotati -->
        <div class="lg:col-span-6 bg-white/50 backdrop-blur-sm p-5 rounded-xl border border-slate-200/60 shadow-sm transition hover:shadow-md hover:bg-white/80 group/file1">
          <label class="flex items-center gap-2 text-sm font-semibold text-indigo-900 mb-3">
            <div class="p-1.5 bg-indigo-100 rounded-md text-indigo-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg>
            </div>
            Lista iscritti (PDF)
          </label>
          <div class="relative overflow-hidden rounded-lg border border-dashed border-indigo-300 bg-indigo-50/50 group-hover/file1:bg-indigo-50 transition-colors">
            <input type="file" name="file_prenotati[]" accept=".pdf" class="file-input block w-full text-sm text-slate-500 
              file:mr-4 file:py-3 file:px-4 
              file:border-0 file:border-r file:border-indigo-200
              file:text-sm file:font-semibold
              file:bg-indigo-100 file:text-indigo-700
              hover:file:bg-indigo-200 cursor-pointer transition-colors">
          </div>
          <p class="text-xs text-slate-500 mt-2 ml-1">Carica il PDF con l'elenco degli studenti prenotati all'esame.</p>
        </div>

        <!-- File Presenze -->
        <div class="lg:col-span-6 bg-white/50 backdrop-blur-sm p-5 rounded-xl border border-slate-200/60 shadow-sm transition hover:shadow-md hover:bg-white/80 group/file2">
          <label class="flex items-center gap-2 text-sm font-semibold text-emerald-900 mb-3">
            <div class="p-1.5 bg-emerald-100 rounded-md text-emerald-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" /></svg>
            </div>
            Registri presenze (PDF multipli)
          </label>
          <div class="relative overflow-hidden rounded-lg border border-dashed border-emerald-300 bg-emerald-50/50 group-hover/file2:bg-emerald-50 transition-colors">
            <input type="file" accept=".pdf" multiple class="file-input-presenze block w-full text-sm text-slate-500
              file:mr-4 file:py-3 file:px-4
              file:border-0 file:border-r file:border-emerald-200
              file:text-sm file:font-semibold
              file:bg-emerald-100 file:text-emerald-700
              hover:file:bg-emerald-200 cursor-pointer transition-colors">
          </div>
          <p class="text-xs text-slate-500 mt-2 ml-1">Puoi selezionare più di un PDF tenendo premuto CTRL (o CMD).</p>
        </div>
      </div>
    </div>
  </template>

  <!-- Overlay caricamento premium -->
  <div id="loading-overlay" class="fixed inset-0 bg-slate-900/60 hidden items-center justify-center z-50 backdrop-blur-md transition-all duration-300 opacity-0">
    <div class="bg-white/90 p-10 rounded-3xl shadow-2xl flex flex-col items-center border border-white/20 transform scale-95 transition-transform duration-300 max-w-sm w-full text-center" id="loading-modal">
      <div class="relative w-20 h-20 mb-6">
        <div class="absolute inset-0 border-4 border-indigo-100 rounded-full"></div>
        <div class="absolute inset-0 border-4 border-indigo-600 rounded-full border-t-transparent animate-spin"></div>
        <div class="absolute inset-0 flex items-center justify-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
          </svg>
        </div>
      </div>
      <h2 class="text-xl font-bold text-slate-800 tracking-tight mb-2">Elaborazione in corso</h2>
      <p class="text-sm font-medium text-slate-500">Stiamo analizzando i PDF, per favore attendi qualche istante...</p>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
</body>
</html>
