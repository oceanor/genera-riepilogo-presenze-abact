document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('righe-container');
  const template = document.getElementById('riga-template');
  const btnAdd = document.getElementById('btn-add');
  const form = document.getElementById('form-main');
  const errorContainer = document.getElementById('error-container');
  const errorList = document.getElementById('error-list');
  const loadingOverlay = document.getElementById('loading-overlay');
  
  let rowCount = 0;

  // Aggiunge una nuova riga materia
  function aggiungiRiga() {
    const clone = template.content.cloneNode(true);
    const riga = clone.querySelector('.riga-materia');
    
    // Assegna un ID unico per poter mappare i file multipli di presenze alla riga corretta in PHP
    const rowIndex = rowCount++;
    riga.dataset.index = rowIndex;
    
    // Aggiorna il nome dell'input presenze per includere l'indice della riga
    // Questo è fondamentale perché l'input è multiple e in PHP ci serve sapere a quale materia appartengono i file
    const inputPresenze = riga.querySelector('.file-input-presenze');
    inputPresenze.name = `file_presenze_${rowIndex}[]`;

    // Setup pulsante rimuovi
    const btnRemove = riga.querySelector('.btn-remove');
    btnRemove.addEventListener('click', () => {
      riga.style.opacity = '0';
      riga.style.transform = 'scale(0.98)';
      setTimeout(() => riga.remove(), 200);
    });

    // Animazione di entrata
    riga.style.opacity = '0';
    riga.style.transform = 'translateY(10px)';
    container.appendChild(riga);
    
    // Trigger reflow per animazione
    void riga.offsetWidth;
    riga.style.opacity = '1';
    riga.style.transform = 'translateY(0)';
    riga.style.transition = 'all 0.3s ease-out';
  }

  // Mostra errori di validazione
  function mostraErrori(errori) {
    errorList.innerHTML = '';
    errori.forEach(err => {
      const li = document.createElement('li');
      li.textContent = err;
      errorList.appendChild(li);
    });
    errorContainer.classList.remove('hidden');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  // Valida il form prima dell'invio
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    errorContainer.classList.add('hidden');
    
    const righe = container.querySelectorAll('.riga-materia');
    const errori = [];
    let righeValide = 0;

    if (righe.length === 0) {
      errori.push("Devi aggiungere almeno una materia.");
    } else {
      righe.forEach((riga, index) => {
        const nome = riga.querySelector('input[name="nome_materia[]"]').value.trim();
        const filePrenotati = riga.querySelector('input[name="file_prenotati[]"]').files;
        const filePresenze = riga.querySelector('.file-input-presenze').files;

        // Se la riga è completamente vuota, la ignoriamo
        if (!nome && filePrenotati.length === 0 && filePresenze.length === 0) {
          return;
        }

        const numRiga = index + 1;
        const nomeDisplay = nome ? `"${nome}"` : `Riga ${numRiga}`;

        if (!nome) {
          errori.push(`${nomeDisplay}: manca il nome della materia.`);
        }
        if (filePrenotati.length === 0) {
          errori.push(`${nomeDisplay}: manca il file PDF dei prenotati.`);
        }
        if (filePresenze.length === 0) {
          errori.push(`${nomeDisplay}: manca il file PDF delle presenze.`);
        }

        if (nome && filePrenotati.length > 0 && filePresenze.length > 0) {
          righeValide++;
        }
      });
    }

    if (errori.length > 0) {
      mostraErrori(errori);
      return;
    }

    if (righeValide === 0) {
      mostraErrori(["Nessuna riga valida compilata (servono: nome, file prenotati e file presenze)."]);
      return;
    }

    // Se tutto OK, mostra loading e invia il form
    loadingOverlay.classList.remove('hidden');
    loadingOverlay.classList.add('flex');
    
    // Anima l'entrata
    setTimeout(() => {
      loadingOverlay.classList.remove('opacity-0');
      loadingOverlay.classList.add('opacity-100');
      const modal = document.getElementById('loading-modal');
      if (modal) {
        modal.classList.remove('scale-95');
        modal.classList.add('scale-100');
      }
    }, 10);
    
    // Invio form
    setTimeout(() => {
      form.submit();
    }, 400);
  });

  // Aggiungi subito una riga all'avvio
  btnAdd.addEventListener('click', aggiungiRiga);
  aggiungiRiga();
});
