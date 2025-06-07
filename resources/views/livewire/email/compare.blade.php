<div class="flex flex-col gap-4">
    <x-card>
        <x-slot:header>
            Confronta e unisci due liste email
        </x-slot:header>
        <div class="space-y-2">
            @if ($errorMessage)
                <div class="rounded-md bg-red-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">
                                Errore
                            </h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p>{{ $errorMessage }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-upload wire:model="list1" accept="text/csv">
                        <x-slot:label>
                            <span>Prima Lista (L1)</span>
                            <small class="block text-gray-500">Deve contenere una colonna "email"</small>
                        </x-slot:label>
                    </x-upload>
                </div>
                <div>
                    <x-upload wire:model="list2" accept="text/csv">
                        <x-slot:label>
                            <span>Seconda lista (L2)</span>
                            <small class="block text-gray-500">Deve contenere una colonna "email"</small>
                        </x-slot:label>
                    </x-upload>
                </div>
            </div>
        </div>
    </x-card>

    @if (!empty($comparison))
        <x-card>
            <x-slot:header>
                Statistiche del confronto
            </x-slot:header>
            <div class="space-y-2">
                @php
                    $headers = [['index' => 'stat', 'label' => 'Statistica'], ['index' => 'valore', 'label' => 'Valore'], ['index' => 'azioni', 'label' => 'Azioni']];
                    $rows = [];
                    $rows[] = [
                        'stat' => 'Email in lista 1',
                        'valore' => $comparison['numero_lista1'] ?? 0,
                        'azioni' => '',
                    ];
                    $rows[] = [
                        'stat' => 'Email in lista 2',
                        'valore' => $comparison['numero_lista2'] ?? 0,
                        'azioni' => '',
                    ];
                    $rows[] = [
                        'stat' => 'Email totali (duplicati inclusi)',
                        'valore' => $comparison['totali'] ?? 0,
                        'azioni' => '',
                    ];
                    $rows[] = [
                        'stat' => 'Email uniche (L1 + L2)',
                        'valore' => isset($comparison['uniche']) ? count($comparison['uniche']) : 0,
                        'azioni' => isset($comparison['uniche']) && count($comparison['uniche']) > 0 ? 'download_uniche' : 'no_data',
                    ];
                    $rows[] = [
                        'stat' => 'Email presenti in entrambe le liste (L1 ∩ L2)',
                        'valore' => isset($comparison['ripetute']) ? count($comparison['ripetute']) : 0,
                        'azioni' => isset($comparison['ripetute']) && count($comparison['ripetute']) > 0 ? 'download_comuni' : 'no_data',
                    ];
                    $rows[] = [
                        'stat' => 'Email esclusive della lista 1 (L1 - L2)',
                        'valore' => isset($comparison['solo_lista1']) ? count($comparison['solo_lista1']) : 0,
                        'azioni' => isset($comparison['solo_lista1']) && count($comparison['solo_lista1']) > 0 ? 'download_solo_lista1' : 'no_data',
                    ];
                    $rows[] = [
                        'stat' => 'Email esclusive della lista 2 (L2 - L1)',
                        'valore' => isset($comparison['solo_lista2']) ? count($comparison['solo_lista2']) : 0,
                        'azioni' => isset($comparison['solo_lista2']) && count($comparison['solo_lista2']) > 0 ? 'download_solo_lista2' : 'no_data',
                    ];
                @endphp

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                @foreach ($headers as $header)
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        {{ $header['label'] }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($rows as $row)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                        {{ $row['stat'] }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                        {{ $row['valore'] }}
                                        @if ($row['valore'] == 0 && $row['azioni'] === 'no_data')
                                            <span class="ml-2 text-xs text-gray-500">(Nessun dato)</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                        @if ($row['azioni'] === 'download_uniche')
                                            <x-button sm color="primary" wire:click="downloadUniche" icon="arrow-down-tray" position="right" :disabled="!$list1EmailField || !$list2EmailField">
                                                Scarica
                                            </x-button>
                                        @elseif($row['azioni'] === 'download_comuni')
                                            <x-button sm color="primary" wire:click="downloadRipetute" icon="arrow-down-tray" position="right" :disabled="!$list1EmailField || !$list2EmailField">
                                                Scarica
                                            </x-button>
                                        @elseif($row['azioni'] === 'download_solo_lista1')
                                            <x-button sm color="secondary" wire:click="downloadSoloLista1" icon="arrow-down-tray" position="right" :disabled="!$list1EmailField || !$list2EmailField">
                                                Scarica
                                            </x-button>
                                        @elseif($row['azioni'] === 'download_solo_lista2')
                                            <x-button sm color="secondary" wire:click="downloadSoloLista2" icon="arrow-down-tray" position="right" :disabled="!$list1EmailField || !$list2EmailField">
                                                Scarica
                                            </x-button>
                                        @elseif($row['azioni'] === 'no_data')
                                            <span class="text-xs text-gray-400">Nessun file da scaricare</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </x-card>
    @endif
</div>
