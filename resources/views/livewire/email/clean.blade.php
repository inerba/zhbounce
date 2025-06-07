<div>
    <x-card>
        <x-slot:header>
            Pulisci lista
        </x-slot:header>
        <div class="space-y-2">

            <div>
                <x-upload wire:model="list" accept="text/csv">
                    <x-slot:label>
                        <span>Lista principale</span>
                    </x-slot:label>
                </x-upload>
                @if ($listFields)
                    <div class="mt-2">
                        <label class="mb-1 block text-xs">Campo email</label>
                        <x-select.native wire:model="listEmailField" :options="$listFields" class="w-full rounded border px-2 py-1">
                            <option value="">Seleziona campo email</option>
                        </x-select.native>
                    </div>
                @endif
            </div>

            {{-- Opzioni di controllo --}}
            <div class="mt-4 flex flex-col gap-2">
                <x-toggle wire:model="checkFormat" :disabled="$cleaning">
                    <x-slot:label>
                        Verifica che l’indirizzo email sia scritto correttamente (formato valido)
                    </x-slot:label>
                </x-toggle>
                <x-toggle wire:model="checkDuplicate" :disabled="$cleaning">
                    <x-slot:label>
                        Rimuovi indirizzi email presenti più di una volta nella lista
                    </x-slot:label>
                </x-toggle>
                <x-toggle wire:model="checkDisposable" :disabled="$cleaning">
                    <x-slot:label>
                        Escludi indirizzi email temporanei o usa e getta (es: mailinator.com, 10minutemail.com)
                    </x-slot:label>
                </x-toggle>
                <x-toggle wire:model="checkMx" :disabled="$cleaning">
                    <x-slot:label>
                        Verifica se il dominio dell'email esiste e dispone di un record MX valido (operazione lenta)
                    </x-slot:label>
                </x-toggle>
                {{-- <x-toggle wire:model="checkSmtp" :disabled="$cleaning">
                    <x-slot:label>
                        Controlla connessione SMTP (molto lento)
                    </x-slot:label>
                </x-toggle> --}}
            </div>

            <div class="mt-4 flex gap-2">
                <x-button color="white" wire:click="startClean" icon="beaker" position="right" :disabled="!$listEmailField || $cleaning">Pulisci</x-button>
            </div>
            @if ($cleaning)
                <div wire:poll.500ms="processCleanBatch" class="mt-2 text-sm">
                    Pulizia in corso: {{ $cleanProgress }} / {{ $cleanTotal }}<br>

                    @if ($cleanEta)
                        <div class="mt-1 text-sm">
                            Stima tempo rimanente: {{ $cleanEta }}
                        </div>
                    @endif
                </div>
            @endif

            @if (count($feedbackRows) > 0)
                <div class="bg-primary-50 dark:bg-primary-900 mt-4 rounded border">
                    <div class="border-b p-2 text-xs text-gray-600">
                        Email scartate: {{ count($feedbackRows) }}
                    </div>
                    <div class="max-h-64 overflow-auto">
                        <table class="min-w-full text-xs">
                            <thead class="sticky top-0 bg-gray-100 dark:bg-gray-800">
                                <tr>
                                    <th class="px-2 py-1 text-left">Email</th>
                                    <th class="px-2 py-1 text-left">Motivo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($feedbackRows as $row)
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <td class="px-2 py-1 font-mono">{{ $row['email'] }}</td>
                                        <td class="px-2 py-1">
                                            <span class="inline-flex items-center rounded bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">
                                                {{ $row['status'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if (!$cleaning && $cleanProgress > 0)
                <div class="mt-4">
                    @php
                        $headers = [['index' => 'stat', 'label' => 'Statistica'], ['index' => 'valore', 'label' => 'Valore']];
                        $rows = $this->cleanStats;
                    @endphp
                    <x-table :$headers :$rows />
                </div>
            @endif
        </div>
        @if (!$cleaning && count($cleanRows) > 0)
            <x-slot:footer>
                <x-button color="primary" wire:click="downloadCleaned" icon="arrow-down-tray" position="right" :disabled="!count($cleanRows) || count($cleanRows) === 1">
                    Scarica lista pulita
                </x-button>
            </x-slot:footer>
        @endif
    </x-card>
</div>
