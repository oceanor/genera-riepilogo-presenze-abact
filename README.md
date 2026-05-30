# Genera riepilogo presenze – ABACT

Genera un report di percentuale presenze partendo dai PDF di prenotazione esami e presenze per l'Accademia di Belle Arti.

Questo strumento è disponibile in due versioni:
1. **Versione Web (PHP)**: Ideale per l'utilizzo online direttamente su un server web, senza richiedere l'installazione locale di Python.
2. **Versione Desktop (Python)**: Ideale per l'utilizzo locale tramite interfaccia grafica (GUI).

---

## 1. Versione Web (PHP)

Questa versione è pensata per essere ospitata su un qualsiasi server web con supporto PHP, rendendo lo strumento accessibile online da qualsiasi browser e dispositivo.

### Caratteristiche
- **Zero dipendenze esterne**: La libreria PHP per il parsing dei PDF (`smalot/pdfparser`) è inclusa localmente nella cartella `lib/`, quindi non è necessario Composer.
- **Interfaccia moderna e reattiva**: Sviluppata con Tailwind CSS v4, include transizioni fluide e un design premium con effetto "glassmorphism".
- **Download diretto**: Il report generato viene scaricato direttamente dall'utente senza salvare dati sensibili sul server.

### Come usare
1. Carica il contenuto della cartella `php-version/` sul tuo server web (richiede PHP 7.4 o superiore).
2. Visita l'indirizzo corrispondente nel browser (es. `https://tuosito.it/presenze/`).
3. Clicca su **Aggiungi materia** per configurare le materie da elaborare.
4. Per ciascuna materia:
   - Inserisci il nome.
   - Carica il PDF della **lista iscritti** (elenco degli prenotati all'esame).
   - Carica uno o più PDF del **registro presenze**.
5. Clicca su **Genera riepilogo** per visualizzare e stampare/salvare il report HTML finale.

---

## 2. Versione Desktop (Python)

Questa versione si avvia con un'interfaccia grafica (GUI) direttamente sul proprio computer.

### Requisiti
- Python 3
- Libreria **pypdf** (`pip install pypdf`)

### Come usare
1. Installa i requisiti ed esegui lo script dalla cartella del progetto:
   ```bash
   pip install pypdf
   python main.py
   ```
   Oppure fai doppio clic su **avvia_gui.vbs** (solo Windows, avvia lo script senza mostrare la console nera).
   
2. Nella finestra grafica:
   - Clicca su **Aggiungi riga**.
   - Inserisci il **nome della materia**.
   - Clicca su **Sfoglia** in "Prenotati (lista esame)" e seleziona il PDF degli iscritti.
   - Clicca su **Sfoglia** in "Presenze" e seleziona uno o più PDF delle presenze.
   
3. Clicca su **Genera riepilogo**. Verrà creato il file `riepilogo_esami.html` e aperto automaticamente nel browser predefinito.

---

## Colori nella colonna Percentuale

- **Verde**: percentuale presenze ≥ 50%
- **Rosso**: percentuale presenze < 50%

## Licenza

MIT – vedi [LICENSE](LICENSE)
