<?php

namespace App\Livewire\Email;

use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class Clean extends Component
{
    use WithFileUploads;

    public $list = null;
    public $listFields = [];
    public $listEmailField = '';

    public $cleaning = false;
    public $cleanProgress = 0;
    public $cleanTotal = 0;
    public $cleanCurrentEmail = '';
    public $cleanRows = [];
    public $cleanHeader = [];
    public $cleanIndex = 0;
    public $cleanSeparator = ',';

    public $rowsToProcess = [];
    public $emailIdx = -1;
    public $cleanCurrentStatus = '';
    public $feedbackRows = [];
    public $seenEmails = [];
    public $cleanEta = null;
    public $cleanStartTime = null;
    public $cleanEndTime = null;
    public $fileOffset = 0;

    public static $disposableDomainsCache = null;

    // Opzioni di controllo attivabili da frontend
    public $checkFormat = true;
    public $checkMx = false; // Disabilitato di default
    public $checkSmtp = false; // Disabilitato di default
    public $checkDisposable = true;
    public $checkDuplicate = true;

    public $batchSize = 100; // Fisso il batch size all'inizio

    public $feedbackStats = [
        'ok' => 0,
        'invalid' => 0,
        'noDomain' => 0,
        'noMx' => 0,
        'noSmtp' => 0,
        'disposable' => 0,
        'duplicate' => 0,
        'total' => 0
    ];

    public function mount(): void
    {
        // Nessun caricamento iniziale di file
        $this->list = null;
        $this->listFields = [];
        $this->listEmailField = '';
    }

    public function updatedList($file)
    {
        $this->listFields = $this->extractCsvFields($file);
        $this->listEmailField = $this->guessEmailField($this->listFields);

        // Reset stato interfaccia
        $this->cleaning = false;
        $this->cleanProgress = 0;
        $this->cleanTotal = 0;
        $this->cleanCurrentEmail = '';
        $this->cleanRows = [];
        $this->cleanHeader = [];
        $this->cleanIndex = 0;
        $this->cleanSeparator = ',';
        $this->rowsToProcess = [];
        $this->emailIdx = -1;
        $this->cleanCurrentStatus = '';
        $this->feedbackRows = [];
        $this->seenEmails = [];
        $this->cleanEta = null;
        $this->cleanStartTime = null;
        $this->cleanEndTime = null;
        $this->fileOffset = 0;
        // Reset anche le opzioni di controllo (se vuoi che tornino ai default)
        $this->checkFormat = true;
        $this->checkMx = false; // Disabilitato di default
        $this->checkSmtp = false; // Disabilitato di default
        $this->checkDisposable = true;
        $this->checkDuplicate = true;
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
        $separator = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
        $header = fgetcsv($handle, 0, $separator);
        fclose($handle);
        return $header ?: [];
    }

    private function guessEmailField($fields)
    {
        foreach ($fields as $field) {
            if (strtolower($field) === 'email') {
                return $field;
            }
        }
        return '';
    }

    public function clean()
    {
        if (!$this->list || !$this->listEmailField) {
            return;
        }

        $rows = $this->extractRowsFromCsv($this->list);
        $header = $rows[0] ?? [];
        $emailField = $this->listEmailField;
        $idx = array_search($emailField, $header);

        $cleanRows = [];
        $cleanRows[] = $header;

        $seenEmails = [];

        foreach (array_slice($rows, 1) as $row) {
            if ($idx !== false && isset($row[$idx])) {
                $email = trim($row[$idx]);
                $emailLower = strtolower($email);

                // 1. Controllo formato email (più veloce)
                if ($this->checkFormat && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                // 2. Controllo duplicati (secondo più veloce)
                if ($this->checkDuplicate && isset($seenEmails[$emailLower])) {
                    continue;
                }

                // Se NON serve nessun controllo dominio, aggiungi subito la riga
                if (!$this->checkDisposable && !$this->checkMx && !$this->checkSmtp) {
                    $cleanRows[] = $row;
                    if ($this->checkDuplicate) {
                        $seenEmails[$emailLower] = true;
                    }
                    continue;
                }

                // Serve almeno un controllo dominio: calcola dominio
                $domain = null;
                if (strpos($email, '@') !== false) {
                    $domain = substr(strrchr($email, "@"), 1);
                }

                // Se manca il dominio, scarta
                if (!$domain) {
                    continue;
                }

                // 3. Controllo dominio usa e getta (veloce - lookup array)
                if ($this->checkDisposable && $this->isDisposableDomain($domain)) {
                    continue;
                }

                // 4. Controllo MX (lento - DNS query)
                if ($this->checkMx && !checkdnsrr($domain, 'MX')) {
                    continue;
                }

                // 5. Controllo SMTP (molto lento - connessione TCP)
                if ($this->checkSmtp && !$this->canConnectToSmtp($domain)) {
                    continue;
                }

                $cleanRows[] = $row;
                if ($this->checkDuplicate) {
                    $seenEmails[$emailLower] = true;
                }
            }
        }

        $separator = $this->detectSeparator($this->list);

        return response()->streamDownload(function () use ($cleanRows, $separator) {
            $handle = fopen('php://output', 'w');
            foreach ($cleanRows as $row) {
                fputcsv($handle, $row, $separator);
            }
            fclose($handle);
        }, 'lista_pulita.csv');
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

    public function startClean()
    {
        if (!$this->list || !$this->listEmailField) {
            return;
        }

        // Imposta il batch size in base ai controlli attivi
        $this->batchSize = ($this->checkMx || $this->checkSmtp) ? 10 : 100;

        $handle = fopen($this->list->getRealPath(), 'r');
        if ($handle === false) {
            return;
        }
        $firstLine = fgets($handle);
        rewind($handle);
        $separator = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
        $header = fgetcsv($handle, 0, $separator);

        // Conta le righe totali senza caricarle tutte in memoria
        $total = 0;
        while (($row = fgetcsv($handle, 0, $separator)) !== false) {
            $total++;
        }
        fclose($handle);

        $this->cleanRows = [];
        $this->cleanHeader = $header;
        $this->cleanRows[] = $header;
        $this->cleanIndex = 0;
        $this->cleaning = true;
        $this->cleanProgress = 0;
        $this->cleanTotal = $total;
        $this->cleanSeparator = $separator;
        $this->emailIdx = array_search($this->listEmailField, $header);

        $this->feedbackRows = [];
        $this->feedbackStats = [
            'ok' => 0,
            'invalid' => 0,
            'noDomain' => 0,
            'noMx' => 0,
            'noSmtp' => 0,
            'disposable' => 0,
            'duplicate' => 0,
            'total' => 0
        ];
        $this->seenEmails = [];
        $this->cleanEta = null;
        $this->cleanStartTime = microtime(true);
        $this->cleanEndTime = null;
        $this->fileOffset = 0;

        $this->rowsToProcess = null;
    }

    private function canConnectToSmtp($domain)
    {
        // Solo controllo connessione TCP, senza HELO/MAIL commands
        $mxRecords = [];
        if (getmxrr($domain, $mxRecords)) {
            $mx = $mxRecords[0];
            $context = stream_context_create([
                'socket' => ['timeout' => 5]
            ]);

            $connection = @stream_socket_client(
                "tcp://{$mx}:25",
                $errno,
                $errstr,
                5,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if ($connection) {
                fclose($connection);
                return true;
            }
        }
        return false;
    }

    private function isDisposableDomain($domain)
    {
        // Carica la lista solo una volta per request
        if (is_null(self::$disposableDomainsCache)) {
            $url = 'https://raw.githubusercontent.com/disposable/disposable-email-domains/master/domains.txt';
            $list = @file_get_contents($url);
            if ($list !== false) {
                $domains = array_filter(array_map('trim', explode("\n", $list)));
                self::$disposableDomainsCache = array_map('strtolower', $domains);
            } else {
                self::$disposableDomainsCache = [];
            }
        }
        return in_array(strtolower($domain), self::$disposableDomainsCache);
    }

    public function processCleanBatch()
    {
        if (!$this->cleaning) {
            return;
        }

        $handle = fopen($this->list->getRealPath(), 'r');
        if ($handle === false) {
            $this->cleaning = false;
            return;
        }

        // Vai alla posizione corretta nel file
        if ($this->cleanIndex === 0) {
            // Prima volta: salta header e imposta offset
            $firstLine = fgets($handle);
            rewind($handle);
            $separator = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
            $this->cleanSeparator = $separator;
            fgetcsv($handle, 0, $separator);
            $this->fileOffset = ftell($handle);
        } else {
            // Usa l'offset salvato
            fseek($handle, $this->fileOffset);
        }

        $processed = 0;

        // Processa il batch di righe
        while ($processed < $this->batchSize && $this->cleaning) {
            $row = fgetcsv($handle, 0, $this->cleanSeparator);

            if ($row === false) {
                // Fine file
                $this->cleaning = false;
                $this->cleanCurrentEmail = '';
                $this->cleanCurrentStatus = '';
                $this->cleanEta = null;
                $this->cleanEndTime = microtime(true);
                break;
            }

            $email = isset($row[$this->emailIdx]) ? trim($row[$this->emailIdx]) : '';
            $this->cleanCurrentEmail = $email;

            $isValid = true;
            $status = 'OK';

            // Controllo formato email (prioritario e veloce)
            if ($this->checkFormat && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $isValid = false;
                $status = 'Scartata: formato non valido';
            }

            // Controllo duplicati solo se necessario e se email è ancora valida
            $emailLower = null;
            if ($isValid && $this->checkDuplicate) {
                $emailLower = strtolower($email);
                if (isset($this->seenEmails[$emailLower])) {
                    $isValid = false;
                    $status = 'Scartata: duplicato';
                }
            }

            // Se la riga è valida e non servono controlli dominio, aggiungi subito
            if ($isValid && !$this->checkDisposable && !$this->checkMx && !$this->checkSmtp) {
                $this->cleanRows[] = $row;
                if ($this->checkDuplicate && $emailLower) {
                    $this->seenEmails[$emailLower] = true;
                }
            }
            // Controlli dominio solo se necessari
            elseif ($isValid && ($this->checkDisposable || $this->checkMx || $this->checkSmtp)) {
                $domain = null;
                if (strpos($email, '@') !== false) {
                    $domain = substr(strrchr($email, "@"), 1);
                }

                if (!$domain) {
                    $isValid = false;
                    $status = 'Scartata: dominio mancante';
                } else {
                    // 3. Controllo usa e getta (veloce - lookup array)
                    if ($this->checkDisposable && $this->isDisposableDomain($domain)) {
                        $isValid = false;
                        $status = 'Scartata: dominio usa e getta';
                    }
                    // 4. Controllo MX (lento - DNS query)
                    elseif ($this->checkMx && !checkdnsrr($domain, 'MX')) {
                        $isValid = false;
                        $status = 'Scartata: dominio senza record MX';
                    }
                    // 5. Controllo SMTP (molto lento - connessione TCP)
                    elseif ($this->checkSmtp && !$this->canConnectToSmtp($domain)) {
                        $isValid = false;
                        $status = 'Scartata: server SMTP non raggiungibile';
                    } else {
                        $this->cleanRows[] = $row;
                        if ($this->checkDuplicate) {
                            if (!$emailLower) {
                                $emailLower = strtolower($email);
                            }
                            $this->seenEmails[$emailLower] = true;
                        }
                    }
                }
            }

            $this->cleanCurrentStatus = $status;

            // Aggiorna statistiche in tempo reale
            $this->feedbackStats['total']++;
            switch ($status) {
                case 'OK':
                    $this->feedbackStats['ok']++;
                    break;
                case 'Scartata: formato non valido':
                    $this->feedbackStats['invalid']++;
                    break;
                case 'Scartata: dominio mancante':
                    $this->feedbackStats['noDomain']++;
                    break;
                case 'Scartata: dominio senza record MX':
                    $this->feedbackStats['noMx']++;
                    break;
                case 'Scartata: server SMTP non raggiungibile':
                    $this->feedbackStats['noSmtp']++;
                    break;
                case 'Scartata: dominio usa e getta':
                    $this->feedbackStats['disposable']++;
                    break;
                case 'Scartata: duplicato':
                    $this->feedbackStats['duplicate']++;
                    break;
            }

            // Salva nel feedback solo le email scartate
            if ($status !== 'OK') {
                $this->feedbackRows[] = [
                    'email' => $email,
                    'status' => $status
                ];
            }

            $this->cleanIndex++;
            $this->cleanProgress = $this->cleanIndex;
            $processed++;

            if ($this->cleanIndex >= $this->cleanTotal) {
                $this->cleaning = false;
                $this->cleanCurrentEmail = '';
                $this->cleanCurrentStatus = '';
                $this->cleanEta = null;
                $this->cleanEndTime = microtime(true);
                break;
            }
        }

        // Salva la posizione nel file per la prossima chiamata
        $this->fileOffset = ftell($handle);
        fclose($handle);

        // Calcolo ETA
        if ($this->cleanIndex > 0 && isset($this->cleanStartTime)) {
            $elapsed = microtime(true) - $this->cleanStartTime;
            $perItem = $elapsed / $this->cleanIndex;
            $remaining = $this->cleanTotal - $this->cleanIndex;
            $etaSeconds = intval(round($perItem * $remaining));
            if ($etaSeconds > 3600) {
                $this->cleanEta = sprintf('%dh %02dm', floor($etaSeconds / 3600), floor(($etaSeconds % 3600) / 60));
            } elseif ($etaSeconds > 60) {
                $this->cleanEta = sprintf('%dm %02ds', floor($etaSeconds / 60), $etaSeconds % 60);
            } else {
                $this->cleanEta = sprintf('%ds', $etaSeconds);
            }
        } else {
            $this->cleanEta = null;
        }
    }

    public function downloadCleaned()
    {
        $data = $this->cleanRows;
        $separator = $this->cleanSeparator;

        return response()->streamDownload(function () use ($data, $separator) {
            $handle = fopen('php://output', 'w');
            foreach ($data as $row) {
                fputcsv($handle, $row, $separator);
            }
            fclose($handle);
        }, 'lista_pulita.csv');
    }

    public function getCleanStatsProperty()
    {
        // Usa le statistiche ottimizzate invece di contare feedbackRows
        $totalTime = 'N/D';
        if ($this->cleanStartTime) {
            $endTime = $this->cleanEndTime ?: microtime(true);
            $elapsed = $endTime - $this->cleanStartTime;
            if ($elapsed > 60) {
                $minutes = floor($elapsed / 60);
                $seconds = round($elapsed % 60);
                $totalTime = sprintf('%dm %02ds', $minutes, $seconds);
            } else {
                $totalTime = sprintf('%.1fs', $elapsed);
            }
        }

        return [
            ['stat' => 'Totale email processate', 'valore' => $this->feedbackStats['total']],
            ['stat' => 'Email valide', 'valore' => $this->feedbackStats['ok']],
            ['stat' => 'Formato non valido', 'valore' => $this->feedbackStats['invalid']],
            ['stat' => 'Dominio mancante', 'valore' => $this->feedbackStats['noDomain']],
            ['stat' => 'Dominio senza record MX', 'valore' => $this->feedbackStats['noMx']],
            ['stat' => 'Server SMTP non raggiungibile', 'valore' => $this->feedbackStats['noSmtp']],
            ['stat' => 'Dominio usa e getta', 'valore' => $this->feedbackStats['disposable']],
            ['stat' => 'Duplicati', 'valore' => $this->feedbackStats['duplicate']],
            ['stat' => 'Tempo totale', 'valore' => $totalTime],
        ];
    }

    public function render()
    {
        return view('livewire.email.clean');
    }
}
