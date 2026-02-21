# Genera riepilogo presenze – ABACT

Genera un report di percentuale presenze partendo dai PDF di prenotazione esami e presenze per l'Accademia di Belle Arti.

## Cosa fa

Legge i PDF delle liste iscritti agli esami e i PDF dei registri presenze per corso, estrae i dati e genera:

- **riepilogo_esami.html** – pagina con le tabelle per ogni esame (nome, assenze, presenze, percentuale colorata); pulsante Stampa per stampare o salvare come PDF

## Come usare

L'applicazione si avvia con interfaccia grafica. Non servono cartelle fisse né file di configurazione: le associazioni tra materie e PDF si creano dalla finestra.

1. **Avvio** – dalla cartella del progetto:
   ```bash
   pip install pypdf
   python main.py
   ```
   Oppure doppio clic su **avvia_gui.vbs** (Windows, senza finestra console).

2. Nella finestra **aggiungi righe**. Per ogni riga:
   - Inserisci il **nome materia** (titolo usato nella pagina)
   - Clicca **Sfoglia** in "Prenotati (lista esame)" e seleziona il PDF della lista iscritti
   - Clicca **Sfoglia** in "Presenze" e seleziona uno o più PDF dei registri presenze

3. Clicca **Genera riepilogo**. Verrà creato `riepilogo_esami.html` e aperto nel browser. Usa il pulsante **Stampa** per stampare o salvare come PDF.

## Requisiti

- Python 3
- libreria **pypdf** (`pip install pypdf`)

## Colori nella colonna Percentuale

- **Verde**: percentuale ≥ 50%
- **Rosso**: percentuale < 50%

## Licenza

MIT – vedi [LICENSE](LICENSE)
