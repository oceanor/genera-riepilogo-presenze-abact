# Riepilogo iscritti esami e presenze – estrazione PDF + GUI
import re
import traceback
from pathlib import Path
from datetime import datetime

import tkinter as tk
from tkinter import ttk, filedialog, messagebox
import webbrowser

from pypdf import PdfReader

# cartella dove sta lo script, usata per salvare output e log
BASE = Path(__file__).resolve().parent
PDF_EXT = [("PDF", "*.pdf"), ("Tutti", "*.*")]
ERRORS_LOG = BASE / "errors.log"


def _log_error(exc):
    # salva traceback su file per debug
    try:
        with open(ERRORS_LOG, "a", encoding="utf-8") as f:
            f.write(f"\n--- {datetime.now().isoformat()} ---\n")
            traceback.print_exc(file=f)
    except Exception:
        pass


# --- Estrazione da PDF ---

def testo_pdf(path):
    # legge tutto il testo da tutte le pagine
    r = PdfReader(path)
    return "\n".join(p.extract_text() or "" for p in r.pages)


def estrai_iscritti(testo):
    # formato lista esame: matricola 5 cifre, nome, sigla corso tipo DASL11_FOT
    iscritti = []
    seen = set()
    for line in testo.splitlines():
        line = line.strip()
        m = re.match(r"^\d+\s+(\d{5})\s+(.+?)\s+D[A-Z]{2}L\d+_[A-Z0-9]+\s+\d+\s+\d+", line)
        if m:
            matricola = m.group(1)
            nome = m.group(2).strip()
            if nome and len(nome) > 2:
                key = matricola or nome
                if key not in seen:
                    seen.add(key)
                    iscritti.append({"nome": nome, "matricola": matricola})
    return iscritti


def estrai_presenze(testo):
    # cerca blocchi nome + "P A" + ore/giorni; supporta sia ore (XX:00h) che giorni (Xg)
    risultati = []
    lines = testo.splitlines()
    i = 0
    while i < len(lines):
        line = lines[i].strip()
        if re.match(r"^[A-ZÀÈÉÌÒÙ\s'-]+$", line) and 1 <= len(line.split()) <= 5 and "Matricola" not in line and "P A" not in line and len(line) > 2:
            nome_candidate = line
            j = i + 1
            while j < len(lines) and lines[j].strip() and re.match(r"^[A-ZÀÈÉÌÒÙ\s'-]+$", lines[j].strip()) and "Matricola" not in lines[j]:
                nome_candidate += " " + lines[j].strip()
                j += 1
            nome_candidate = " ".join(nome_candidate.split())
            if 2 <= len(nome_candidate.split()) <= 6:
                k = j
                matricola = None
                while k < min(j + 15, len(lines)):
                    if matricola is None:
                        m_mat = re.search(r"Matricola\s+(\d{5})", lines[k])
                        if m_mat:
                            matricola = m_mat.group(1)
                    if "P A" in lines[k]:
                        block = " ".join(lines[k:k+14])
                        giorni = re.findall(r"(\d+)g", block)
                        if len(giorni) >= 2:
                            presenze_g = int(giorni[0])
                            assenze_g = int(giorni[1])
                        else:
                            ore_p = re.findall(r"(\d+):00h", block)
                            perc = re.findall(r"(\d+)%", block)
                            if len(ore_p) >= 2 and len(perc) >= 2:
                                presenze_g = int(ore_p[0]) // 6
                                assenze_g = int(ore_p[1]) // 6
                            else:
                                presenze_g = assenze_g = 0
                        tot = presenze_g + assenze_g
                        percentuale = round(100 * presenze_g / tot, 1) if tot else 0
                        risultati.append({
                            "nome": nome_candidate,
                            "matricola": matricola,
                            "assenze": assenze_g,
                            "presenze": presenze_g,
                            "percentuale": percentuale,
                        })
                        break
                    k += 1
            i = j
            continue
        i += 1
    return risultati


def _merge_presenze(lista_per_pdf):
    # unisce più PDF presenze: ultimo record vince se stessa matricola o nome
    by_key = {}
    for lista in lista_per_pdf:
        for s in lista:
            key = f"m:{s['matricola']}" if s.get("matricola") else f"n:{s['nome']}"
            by_key[key] = s
    return list(by_key.values())


def _slug(nome):
    # per id HTML, niente caratteri strani
    return re.sub(r"[^\w\s-]", "", nome).strip().replace(" ", "-") or "corso"


def _genera_html(out):
    # costruisce l'HTML con i dati in JSON inline
    import json
    json_esc = json.dumps(out, ensure_ascii=False)
    return """<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>Riepilogo iscritti esami e presenze</title>
  <style>
    body { font-family: system-ui, sans-serif; max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
    .barra-stampa { margin-bottom: 1rem; }
    .btn-stampa { padding: 8px 16px; font-size: 1rem; cursor: pointer; background: #7ec89e; color: #2a5a4a; border: none; border-radius: 4px; }
    .btn-stampa:hover { background: #5ab88a; }
    @media print { .barra-stampa { display: none; } }
    h1 { font-size: 1.5rem; margin-bottom: 1.5rem; }
    h2 { font-size: 1.15rem; margin-top: 2rem; margin-bottom: 0.75rem; color: #333; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 1rem; }
    th, td { border: 1px solid #ccc; padding: 0.4rem 0.6rem; text-align: left; }
    th { background: #f5f5f5; }
    /* sotto 50% rosso, sopra verde */
    .percent-bassa { background: #ffcdd2; color: #b71c1c; }
    .percent-ok { background: #c8e6c9; color: #1b5e20; }
    @media print { .corso { page-break-after: always; } .corso:last-child { page-break-after: auto; } }
  </style>
</head>
<body>
  <div class="barra-stampa"><button class="btn-stampa" onclick="window.print()">Stampa</button></div>
  <h1>Riepilogo iscritti esami e presenze</h1>
  <div id="contenuto"></div>
  <script>
    function normalizzaNome(n) {
      return n.toLowerCase().replace(/\\s+/g, ' ').trim();
    }
    function nomeSenzaSpazi(n) {
      return normalizzaNome(n).replace(/\\s/g, '');
    }
    function iscrittoNomeMatricola(iscritto) {
      if (typeof iscritto === 'string') return { nome: iscritto, matricola: null };
      return { nome: iscritto.nome || '', matricola: iscritto.matricola || null };
    }
    function buildPresenzeMaps(studenti) {
      var byMat = {}, byNome = {}, byCompatto = {};
      (studenti || []).forEach(function(s) {
        if (s.matricola) byMat[s.matricola] = s;
        byNome[normalizzaNome(s.nome)] = s;
        byCompatto[nomeSenzaSpazi(s.nome)] = s;
      });
      return { byMat: byMat, byNome: byNome, byCompatto: byCompatto };
    }
    function trovaPresenze(maps, iscritto) {
      var info = iscrittoNomeMatricola(iscritto);
      if (info.matricola && maps.byMat[info.matricola]) return maps.byMat[info.matricola];
      var k = normalizzaNome(info.nome);
      if (maps.byNome[k]) return maps.byNome[k];
      var c = nomeSenzaSpazi(info.nome);
      if (maps.byCompatto[c]) return maps.byCompatto[c];
      return null;
    }
    var dati = """ + json_esc + """;
    var html = '';
    dati.corsi.forEach(function(corso) {
      html += '<div class="corso">';
      var nomeCorso = corso.nome_esame || corso.id;
      html += '<h2>' + nomeCorso.replace(/</g,'&lt;') + '</h2>';
      var presenzeMaps = buildPresenzeMaps(corso.studenti_con_presenze);
      var hasPres = corso.studenti_con_presenze && corso.studenti_con_presenze.length > 0;
      if (hasPres) {
        html += '<table><thead><tr><th>Nome</th><th>Assenze</th><th>Presenze</th><th>Percentuale</th></tr></thead><tbody>';
      } else {
        html += '<table><thead><tr><th>Nome</th></tr></thead><tbody>';
      }
      (corso.iscritti_esame || []).forEach(function(iscritto) {
        var info = iscrittoNomeMatricola(iscritto);
        var nome = info.nome;
        var p = trovaPresenze(presenzeMaps, iscritto);
        if (hasPres) {
          var ass = p ? p.assenze : '—';
          var pre = p ? p.presenze : '—';
          var perc = p ? p.percentuale : '—';
          var percClass = (p && p.percentuale < 50) ? 'percent-bassa' : ((p && p.percentuale >= 50) ? 'percent-ok' : '');
          var percAttr = percClass ? ' class="' + percClass + '"' : '';
          html += '<tr><td>' + nome.replace(/</g,'&lt;') + '</td><td>' + ass + '</td><td>' + pre + '</td><td' + percAttr + '>' + (typeof perc === 'number' ? perc + '%' : perc) + '</td></tr>';
        } else {
          html += '<tr><td>' + nome.replace(/</g,'&lt;') + '</td></tr>';
        }
      });
      html += '</tbody></table></div>';
    });
    document.getElementById('contenuto').innerHTML = html;
  </script>
</body>
</html>"""


def run_from_righe(righe, output_dir):
    # legge PDF, estrae dati, scrive riepilogo_esami.html
    output_dir = Path(output_dir)
    corsi = []
    for i, riga in enumerate(righe):
        nome_esame = (riga.get("nome_esame") or "").strip() or f"Riga {i + 1}"
        path_lista = riga.get("path_lista")
        # per prenotati usiamo solo il primo file se ne ha scelti più di uno
        if path_lista is not None:
            path_lista = Path(path_lista)
        paths_presenze = [Path(p) for p in (riga.get("paths_presenze") or []) if p]
        if not path_lista or not path_lista.exists():
            iscritti = []
            studenti_presenze = []
        else:
            testo = testo_pdf(path_lista)
            iscritti = estrai_iscritti(testo)
            liste_presenze = []
            for p in paths_presenze:
                if p and Path(p).exists():
                    liste_presenze.append(estrai_presenze(testo_pdf(Path(p))))
            studenti_presenze = _merge_presenze(liste_presenze) if liste_presenze else []
        corsi.append({
            "id": _slug(nome_esame),
            "nome_esame": nome_esame,
            "iscritti_esame": iscritti,
            "studenti_con_presenze": studenti_presenze,
        })
    out = {"corsi": corsi}
    html_path = output_dir / "riepilogo_esami.html"
    with open(html_path, "w", encoding="utf-8") as f:
        f.write(_genera_html(out))
    return html_path


# --- GUI ---

# palette colori
BG_ROOT = "#f2f6fa"
BG_CARD = "#e8f0f8"
BG_BARRA = "#dce8f4"
ACCENT_ADD = "#7eb8e8"
ACCENT_GENERA = "#7ec89e"
ACCENT_RIMUOVI = "#e8a8a8"
ACCENT_SFOGLIA = "#7eb8e8"
TEXT_DIM = "#5a6a7a"

class RigaFrame(tk.Frame):
    # una materia: nome, file prenotati, file presenze, bottone rimuovi
    def __init__(self, parent, on_remove, **kwargs):
        super().__init__(parent, **kwargs)
        self.on_remove = on_remove
        self.files_sinistra = []
        self.files_destra = []
        self.configure(bg=BG_CARD, highlightbackground="#94a3b8", highlightthickness=1, padx=10, pady=8)

        # nome materia
        riga_nome = tk.Frame(self, bg=BG_CARD)
        riga_nome.grid(row=0, column=0, columnspan=2, sticky="ew", pady=(0, 4))
        self.columnconfigure(0, weight=1)
        tk.Label(riga_nome, text="Nome materia:", bg=BG_CARD, fg=TEXT_DIM, font=("Segoe UI", 9, "bold")).pack(side=tk.LEFT, padx=(0, 8))
        frame_entry = tk.Frame(riga_nome, bg=BG_CARD, height=28)
        frame_entry.pack(side=tk.LEFT, fill=tk.X, expand=True)
        frame_entry.pack_propagate(False)
        self.entry_nome = ttk.Entry(frame_entry)
        self.entry_nome.place(relx=0, rely=0, relwidth=1, relheight=1)

        # file lista prenotati
        tk.Label(self, text="Prenotati (lista esame):", bg=BG_CARD, fg=TEXT_DIM, font=("Segoe UI", 9)).grid(row=1, column=0, sticky="w", padx=(0, 8), pady=2)
        f_sx = tk.Frame(self, bg=BG_CARD)
        f_sx.grid(row=1, column=1, sticky="w", pady=2)
        self.btn_sfoglia_sx = tk.Button(f_sx, text="Sfoglia", command=self._sfoglia_sinistra, bg=ACCENT_SFOGLIA, fg="#2a4a6a", font=("Segoe UI", 9), relief=tk.FLAT, padx=10, pady=2, cursor="hand2", activebackground="#5a9ad8", activeforeground="#2a4a6a")
        self.btn_sfoglia_sx.pack(side=tk.LEFT, padx=(0, 6))
        self.label_sx = tk.Label(f_sx, text="Nessun file", bg=BG_CARD, fg="gray", font=("Segoe UI", 9))
        self.label_sx.pack(side=tk.LEFT)

        # file presenze (si possono selezionare più PDF)
        tk.Label(self, text="Presenze:", bg=BG_CARD, fg=TEXT_DIM, font=("Segoe UI", 9)).grid(row=2, column=0, sticky="w", padx=(0, 8), pady=2)
        f_dx = tk.Frame(self, bg=BG_CARD)
        f_dx.grid(row=2, column=1, sticky="w", pady=2)
        self.btn_sfoglia_dx = tk.Button(f_dx, text="Sfoglia", command=self._sfoglia_destra, bg=ACCENT_SFOGLIA, fg="#2a4a6a", font=("Segoe UI", 9), relief=tk.FLAT, padx=10, pady=2, cursor="hand2", activebackground="#5a9ad8", activeforeground="#2a4a6a")
        self.btn_sfoglia_dx.pack(side=tk.LEFT, padx=(0, 6))
        self.label_dx = tk.Label(f_dx, text="Nessun file", bg=BG_CARD, fg="gray", font=("Segoe UI", 9))
        self.label_dx.pack(side=tk.LEFT)

        # bottone per togliere la riga
        frame_btn = tk.Frame(self, bg=BG_CARD)
        frame_btn.grid(row=3, column=0, columnspan=2, pady=(6, 0))
        self.btn_rimuovi = tk.Button(frame_btn, text="Rimuovi materia", command=self._rimuovi, bg=ACCENT_RIMUOVI, fg="#5a3a3a", font=("Segoe UI", 9), relief=tk.FLAT, padx=10, pady=2, cursor="hand2", activebackground="#d89898", activeforeground="#5a3a3a")
        self.btn_rimuovi.pack()
        self.columnconfigure(1, weight=1)

    def _aggiorna_label_sx(self):
        if not self.files_sinistra:
            self.label_sx.config(text="Nessun file", fg="gray")
        else:
            names = [Path(p).name for p in self.files_sinistra]
            self.label_sx.config(text=", ".join(names), fg="#0f172a")

    def _aggiorna_label_dx(self):
        if not self.files_destra:
            self.label_dx.config(text="Nessun file", fg="gray")
        else:
            names = [Path(p).name for p in self.files_destra]
            self.label_dx.config(text=", ".join(names), fg="#0f172a")

    def _sfoglia_sinistra(self):
        paths = filedialog.askopenfilenames(filetypes=PDF_EXT, title="Seleziona PDF lista prenotati")
        if paths:
            self.files_sinistra = [Path(p) for p in paths]
            self._aggiorna_label_sx()

    def _sfoglia_destra(self):
        paths = filedialog.askopenfilenames(filetypes=PDF_EXT, title="Seleziona PDF presenze")
        if paths:
            self.files_destra = [Path(p) for p in paths]
            self._aggiorna_label_dx()

    def _rimuovi(self):
        if self.on_remove:
            self.on_remove(self)

    def get_riga(self):
        # restituisce dict per run_from_righe
        nome = (self.entry_nome.get() or "").strip()
        path_lista = self.files_sinistra[0] if self.files_sinistra else None
        return {
            "nome_esame": nome or "Senza nome",
            "path_lista": path_lista,
            "paths_presenze": list(self.files_destra),
        }


class App:
    def __init__(self):
        self.root = tk.Tk()
        self.root.title("Accademia di Belle Arti - Riepilogo iscritti esami e presenze")
        self.root.minsize(600, 450)
        self.root.geometry("600x450")
        self.root.configure(bg=BG_ROOT)
        self.righe_frames = []

        barra = tk.Frame(self.root, bg=BG_BARRA, padx=12, pady=10)
        barra.pack(fill=tk.X)
        btn_add = tk.Button(barra, text="Aggiungi materia", command=self._aggiungi_riga, bg=ACCENT_ADD, fg="#2a4a6a", font=("Segoe UI", 10, "bold"), relief=tk.FLAT, padx=14, pady=4, cursor="hand2", activebackground="#5a9ad8", activeforeground="#2a4a6a")
        btn_add.pack(side=tk.LEFT, padx=(0, 8))
        self.btn_genera = tk.Button(barra, text="Genera riepilogo", command=self._genera, bg=ACCENT_GENERA, fg="#2a5a4a", font=("Segoe UI", 10, "bold"), relief=tk.FLAT, padx=14, pady=4, cursor="hand2", activebackground="#5ab88a", activeforeground="#2a5a4a")
        self.btn_genera.pack(side=tk.RIGHT)

        self.canvas = tk.Canvas(self.root, highlightthickness=0, bg=BG_ROOT)
        self.scrollbar = ttk.Scrollbar(self.root, orient=tk.VERTICAL, command=self.canvas.yview)
        self.frame_righe = tk.Frame(self.canvas, bg=BG_ROOT)
        self.frame_righe.bind("<Configure>", lambda e: self.canvas.configure(scrollregion=self.canvas.bbox("all")))
        self._canvas_win_id = self.canvas.create_window((0, 0), window=self.frame_righe, anchor="nw")
        self.canvas.bind("<Configure>", self._on_canvas_configure)
        self.canvas.configure(yscrollcommand=self.scrollbar.set)
        self.canvas.pack(side=tk.LEFT, fill=tk.BOTH, expand=True, padx=8, pady=(0, 8))
        self.scrollbar.pack(side=tk.RIGHT, fill=tk.Y, pady=(0, 8))
        self.frame_righe.bind("<Enter>", self._bind_mousewheel)
        self.frame_righe.bind("<Leave>", self._unbind_mousewheel)

        self._aggiungi_riga()
        self.root.mainloop()

    def _on_canvas_configure(self, event):
        # quando ridimensioni la finestra, il canvas si adatta
        self.canvas.itemconfig(self._canvas_win_id, width=event.width)

    def _bind_mousewheel(self, event):
        # scroll solo quando il mouse è sopra l'area righe
        self.canvas.bind_all("<MouseWheel>", self._on_mousewheel)

    def _unbind_mousewheel(self, event):
        self.canvas.unbind_all("<MouseWheel>")

    def _on_mousewheel(self, event):
        self.canvas.yview_scroll(int(-1 * (event.delta / 120)), "units")

    def _aggiungi_riga(self):
        riga = RigaFrame(self.frame_righe, on_remove=self._rimuovi_riga)
        riga.pack(fill=tk.X, expand=True, padx=4, pady=8)
        self.righe_frames.append(riga)

    def _rimuovi_riga(self, riga_frame):
        if riga_frame in self.righe_frames:
            self.righe_frames.remove(riga_frame)
            riga_frame.destroy()

    def _verifica_pdf(self, path):
        # None = ok, altrimenti stringa con il problema
        try:
            r = PdfReader(path)
            if len(r.pages) == 0:
                return "sembra vuoto o non valido"
            return None
        except Exception:
            return "non sembra essere un PDF valido"

    def _genera(self):
        # raccoglie dati dalle righe e controlla che siano validi
        righe = []
        problemi = []
        for i, r in enumerate(self.righe_frames):
            data = r.get_riga()
            nome = (data["nome_esame"] or "").strip() or "Senza nome"
            path_lista = data.get("path_lista")
            paths_presenze = data.get("paths_presenze") or []

            # Ignora righe completamente vuote (né nome né file)
            if nome == "Senza nome" and not path_lista and not paths_presenze:
                continue

            if nome == "Senza nome" and (path_lista or paths_presenze):
                problemi.append(f"Riga {i+1}: manca nome materia (obbligatorio)")
            if not path_lista:
                if nome == "Senza nome":
                    problemi.append(f"Riga {i+1}: manca nome materia e file prenotati")
                else:
                    problemi.append(f"Riga {i+1} ({nome}): manca file prenotati")
            else:
                path_lista = Path(path_lista)
                if not path_lista.exists():
                    problemi.append(f"Riga {i+1} ({nome}): il file prenotati non esiste o non è accessibile")
                else:
                    err = self._verifica_pdf(path_lista)
                    if err:
                        problemi.append(f"Riga {i+1} ({nome}): il file prenotati {path_lista.name} {err}")

            if not paths_presenze:
                if path_lista:
                    problemi.append(f"Riga {i+1} ({nome}): manca file presenze")
            else:
                for p in paths_presenze:
                    p = Path(p)
                    if not p.exists():
                        problemi.append(f"Riga {i+1} ({nome}): il file presenze '{p.name}' non esiste")
                    else:
                        err = self._verifica_pdf(p)
                        if err:
                            problemi.append(f"Riga {i+1} ({nome}): il file presenze '{p.name}' {err}")

            # aggiungiamo solo se ha tutto: nome, prenotati, presenze
            if nome != "Senza nome" and path_lista and paths_presenze:
                path_lista = Path(path_lista)
                if path_lista.exists():
                    righe.append(data)

        if not righe:
            msg = "Per generare il riepilogo serve almeno una riga con:\n• Nome materia\n• File prenotati (lista esame)\n• File presenze\n\n"
            if problemi:
                msg += "Problemi rilevati:\n• " + "\n• ".join(problemi)
            messagebox.showerror("Impossibile generare", msg)
            return

        if problemi:
            msg = "Attenzione, alcuni problemi sono stati rilevati:\n\n• " + "\n• ".join(problemi)
            msg += "\n\nVuoi procedere comunque con le righe valide?"
            if not messagebox.askyesno("Problemi rilevati", msg):
                return
        self.btn_genera.config(text="Generazione...", bg="#b0b0b0", fg="#666", state=tk.DISABLED, cursor="")
        self.root.update()
        try:
            html_path = run_from_righe(righe, BASE)
            webbrowser.open(html_path.resolve().as_uri())
        except Exception as e:
            _log_error(e)
            messagebox.showerror("Errore", f"{e}\n\nDettagli in {ERRORS_LOG}")
        finally:
            self.btn_genera.config(text="Genera riepilogo", bg=ACCENT_GENERA, fg="#2a5a4a", state=tk.NORMAL, cursor="hand2")


if __name__ == "__main__":
    try:
        App()
    except Exception as e:
        _log_error(e)
        raise
