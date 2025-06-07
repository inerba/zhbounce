<?php

namespace App\Livewire\Email;

use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class Compare extends Component
{
    use WithFileUploads;

    public $list1 = null;
    public $list2 = null;

    public $list1Fields = [];
    public $list2Fields = [];
    public $list1EmailField = '';
    public $list2EmailField = '';
    public $errorMessage = '';

    public function mount(): void
    {
        // Nessun caricamento iniziale di file
        $this->list1 = null;
        $this->list2 = null;
        $this->list1Fields = [];
        $this->list2Fields = [];
        $this->list1EmailField = '';
        $this->list2EmailField = '';
        $this->errorMessage = '';
    }

    public function updatedList1($file)
    {
        $this->errorMessage = ''; // Reset errore all'inizio
        $this->list1Fields = $this->extractCsvFields($file);
        $this->list1EmailField = $this->findEmailField($this->list1Fields);

        if (!$this->list1EmailField) {
            $this->errorMessage = 'La Lista 1 non contiene un campo "email". Assicurati che l\'header contenga una colonna chiamata "email".';
            $this->list1 = null;
            return;
        }

        $this->clearErrorIfBothValid();
    }

    public function updatedList2($file)
    {
        $this->errorMessage = ''; // Reset errore all'inizio
        $this->list2Fields = $this->extractCsvFields($file);
        $this->list2EmailField = $this->findEmailField($this->list2Fields);

        if (!$this->list2EmailField) {
            $this->errorMessage = 'La Lista 2 non contiene un campo "email". Assicurati che l\'header contenga una colonna chiamata "email".';
            $this->list2 = null;
            return;
        }

        $this->clearErrorIfBothValid();
    }

    private function clearErrorIfBothValid()
    {
        if ($this->list1EmailField && $this->list2EmailField) {
            $this->errorMessage = '';
        }
    }

    private function extractCsvFields($file)
    {
        if (!$file) {
            return [];
        }
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return [];
        }
        $firstLine = fgets($handle);
        rewind($handle);

        // Rileva il separatore
        $separator = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

        $header = fgetcsv($handle, 0, $separator);
        fclose($handle);
        return $header ?: [];
    }

    private function findEmailField($fields)
    {
        foreach ($fields as $field) {
            if (strtolower(trim($field)) === 'email') {
                return $field;
            }
        }
        return '';
    }

    public function compare(): array
    {
        // Se non sono stati selezionati i campi email, ritorna vuoto
        if (!$this->list1EmailField || !$this->list2EmailField) {
            return [];
        }

        $emails1 = $this->extractEmailsFromCsv($this->list1, $this->list1EmailField);
        $emails2 = $this->extractEmailsFromCsv($this->list2, $this->list2EmailField);

        $ripetute = array_values(array_intersect($emails1, $emails2));
        $soloLista1 = array_values(array_diff($emails1, $emails2));
        $soloLista2 = array_values(array_diff($emails2, $emails1));
        $uniche = array_values(array_unique(array_merge($emails1, $emails2)));
        $totali = count($emails1) + count($emails2);

        return [
            'numero_lista1' => count($emails1),
            'numero_lista2' => count($emails2),
            'ripetute' => $ripetute,
            'solo_lista1' => $soloLista1,
            'solo_lista2' => $soloLista2,
            'uniche' => $uniche,
            'totali' => $totali,
        ];
    }

    private function extractEmailsFromCsv($file, $field)
    {
        if (!$file || !$field) {
            return [];
        }
        $emails = [];
        $seen = []; // Array per tracciare email già viste
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return [];
        }
        $firstLine = fgets($handle);
        rewind($handle);
        $separator = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
        $header = fgetcsv($handle, 0, $separator);
        if (!$header) {
            return [];
        }
        $index = array_search($field, $header);
        if ($index === false) {
            return [];
        }
        while (($row = fgetcsv($handle, 0, $separator)) !== false) {
            if (isset($row[$index])) {
                $email = trim($row[$index]);
                if (!isset($seen[$email])) { // Controlla se l'email è già stata vista
                    $emails[] = $email;
                    $seen[$email] = true; // Marca come vista
                }
            }
        }
        fclose($handle);
        return $emails;
    }

    public function downloadUniche()
    {
        if (!$this->list1 || !$this->list2 || !$this->list1EmailField || !$this->list2EmailField) {
            return;
        }

        // Estrai tutte le righe da entrambe le liste
        $rows1 = $this->extractRowsFromCsv($this->list1);
        $rows2 = $this->extractRowsFromCsv($this->list2);

        // Unisci header e trova il campo email
        $header1 = $rows1[0] ?? [];
        $header2 = $rows2[0] ?? [];
        $header = $header1; // Si assume che i campi siano uguali o simili

        $emailField1 = $this->list1EmailField;
        $emailField2 = $this->list2EmailField;

        // Crea una mappa email => riga per entrambe le liste
        $map = []; // Mappa email => riga (elimina automaticamente duplicati)

        foreach (array_slice($rows1, 1) as $row) {
            $idx = array_search($emailField1, $header1);
            if ($idx !== false && isset($row[$idx])) {
                $map[trim($row[$idx])] = $row; // Sovrascrive duplicati
            }
        }
        foreach (array_slice($rows2, 1) as $row) {
            $idx = array_search($emailField2, $header2);
            if ($idx !== false && isset($row[$idx])) {
                $map[trim($row[$idx])] = $row; // Sovrascrive duplicati
            }
        }

        // Prepara i dati da esportare
        $data = [];
        $data[] = $header;
        foreach ($map as $row) {
            $data[] = $row;
        }

        $separator = $this->detectSeparator($this->list1);

        return response()->streamDownload(function () use ($data, $separator) {
            $handle = fopen('php://output', 'w');
            foreach ($data as $row) {
                fputcsv($handle, $row, $separator);
            }
            fclose($handle);
        }, 'email_uniche.csv');
    }

    public function downloadRipetute()
    {
        if (!$this->list1 || !$this->list2 || !$this->list1EmailField || !$this->list2EmailField) {
            return;
        }

        // Estrai tutte le righe dalla lista 1
        $rows1 = $this->extractRowsFromCsv($this->list1);
        $header1 = $rows1[0] ?? [];
        $emailField1 = $this->list1EmailField;
        $idx1 = array_search($emailField1, $header1);

        // Trova le email in comune
        $emails1 = $this->extractEmailsFromCsv($this->list1, $this->list1EmailField);
        $emails2 = $this->extractEmailsFromCsv($this->list2, $this->list2EmailField);
        $ripetute = array_flip(array_intersect($emails1, $emails2));

        // Prepara i dati da esportare (solo righe di lista 1 con email in comune)
        $data = [];
        $data[] = $header1;
        foreach (array_slice($rows1, 1) as $row) {
            if ($idx1 !== false && isset($row[$idx1]) && isset($ripetute[trim($row[$idx1])])) {
                $data[] = $row;
            }
        }

        $separator = $this->detectSeparator($this->list1);

        return response()->streamDownload(function () use ($data, $separator) {
            $handle = fopen('php://output', 'w');
            foreach ($data as $row) {
                fputcsv($handle, $row, $separator);
            }
            fclose($handle);
        }, 'email_comuni.csv');
    }

    public function downloadSoloLista1()
    {
        if (!$this->list1 || !$this->list2 || !$this->list1EmailField || !$this->list2EmailField) {
            return;
        }

        // Estrai tutte le righe dalla lista 1
        $rows1 = $this->extractRowsFromCsv($this->list1);
        $header1 = $rows1[0] ?? [];
        $emailField1 = $this->list1EmailField;
        $idx1 = array_search($emailField1, $header1);

        // Trova le email presenti solo nella lista 1
        $emails1 = $this->extractEmailsFromCsv($this->list1, $this->list1EmailField);
        $emails2 = $this->extractEmailsFromCsv($this->list2, $this->list2EmailField);
        $soloLista1 = array_flip(array_diff($emails1, $emails2));

        // Prepara i dati da esportare (solo righe di lista 1 con email non presenti in lista 2, eliminando duplicati)
        $data = [];
        $data[] = $header1;
        $seen = []; // Array per tracciare email già processate
        foreach (array_slice($rows1, 1) as $row) {
            if ($idx1 !== false && isset($row[$idx1])) {
                $email = trim($row[$idx1]);
                if (isset($soloLista1[$email]) && !isset($seen[$email])) {
                    $data[] = $row;
                    $seen[$email] = true; // Marca questa email come già processata
                }
            }
        }

        $separator = $this->detectSeparator($this->list1);

        return response()->streamDownload(function () use ($data, $separator) {
            $handle = fopen('php://output', 'w');
            foreach ($data as $row) {
                fputcsv($handle, $row, $separator);
            }
            fclose($handle);
        }, 'email_solo_lista1.csv');
    }

    public function downloadSoloLista2()
    {
        if (!$this->list1 || !$this->list2 || !$this->list1EmailField || !$this->list2EmailField) {
            return;
        }

        // Estrai tutte le righe dalla lista 2
        $rows2 = $this->extractRowsFromCsv($this->list2);
        $header2 = $rows2[0] ?? [];
        $emailField2 = $this->list2EmailField;
        $idx2 = array_search($emailField2, $header2);

        // Trova le email presenti solo nella lista 2
        $emails1 = $this->extractEmailsFromCsv($this->list1, $this->list1EmailField);
        $emails2 = $this->extractEmailsFromCsv($this->list2, $this->list2EmailField);
        $soloLista2 = array_flip(array_diff($emails2, $emails1));

        // Prepara i dati da esportare (solo righe di lista 2 con email non presenti in lista 1, eliminando duplicati)
        $data = [];
        $data[] = $header2;
        $seen = []; // Array per tracciare email già processate
        foreach (array_slice($rows2, 1) as $row) {
            if ($idx2 !== false && isset($row[$idx2])) {
                $email = trim($row[$idx2]);
                if (isset($soloLista2[$email]) && !isset($seen[$email])) {
                    $data[] = $row;
                    $seen[$email] = true; // Marca questa email come già processata
                }
            }
        }

        $separator = $this->detectSeparator($this->list2);

        return response()->streamDownload(function () use ($data, $separator) {
            $handle = fopen('php://output', 'w');
            foreach ($data as $row) {
                fputcsv($handle, $row, $separator);
            }
            fclose($handle);
        }, 'email_solo_lista2.csv');
    }

    private function extractRowsFromCsv($file)
    {
        if (!$file) {
            return [];
        }
        $rows = [];
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return [];
        }
        $firstLine = fgets($handle);
        rewind($handle);
        $separator = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
        while (($row = fgetcsv($handle, 0, $separator)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);
        return $rows;
    }

    private function detectSeparator($file)
    {
        if (!$file) {
            return ',';
        }
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return ',';
        }
        $firstLine = fgets($handle);
        fclose($handle);
        return (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
    }

    public function render()
    {
        return view('livewire.email.compare', [
            'list1' => $this->list1,
            'list2' => $this->list2,
            'list1Fields' => $this->list1Fields,
            'list2Fields' => $this->list2Fields,
            'list1EmailField' => $this->list1EmailField,
            'list2EmailField' => $this->list2EmailField,
            'comparison' => $this->compare(),
            'errorMessage' => $this->errorMessage,
        ]);
    }
}
