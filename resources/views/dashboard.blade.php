<x-app-layout>
    <x-card class="max-w-3xl">
        <x-slot:header>
            Benvenuto su <strong>Zero Hard Bounce</strong>
        </x-slot:header>
        <div class="prose">
            <p>
                <strong>Zero Hard Bounce</strong> è uno strumento intuitivo per la pulizia e la gestione intelligente delle tue liste email. Grazie a controlli avanzati e un’interfaccia semplice, puoi migliorare la deliverability delle tue
                campagne email ed eliminare contatti inutili o dannosi.
            </p>

            <div class="mt-6 grid grid-cols-2 gap-6">
                <div class="prose space-y-2">
                    <a href="{{ route('email.clean') }}" class="bg-primary-500 text-primary-100 mb-4 flex flex-col items-center justify-center rounded-xl p-4">
                        <div class="mb-2 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-22">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M19 22.5a4.75 4.75 0 0 1 3.5 -3.5a4.75 4.75 0 0 1 -3.5 -3.5a4.75 4.75 0 0 1 -3.5 3.5a4.75 4.75 0 0 1 3.5 3.5" />
                                <path d="M11.5 19h-6.5a2 2 0 0 1 -2 -2v-10a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v5" />
                                <path d="M3 7l9 6l9 -6" />
                            </svg>
                        </div>
                        <h2 class="mb-2 text-center text-xl font-bold">Pulisci lista</h2>
                    </a>
                    <p>
                        Nella sezione <strong>"Pulisci lista"</strong> puoi caricare un file CSV e scegliere una o più azioni da eseguire sulla tua lista:
                    </p>
                    <ul>
                        <li><strong>Controlla formato email:</strong> rimuove gli indirizzi con un formato non valido.</li>
                        <li><strong>Rimuovi duplicati:</strong> elimina gli indirizzi ripetuti.</li>
                        <li><strong>Blocca domini usa e getta:</strong> scarta email temporanee o sospette.</li>
                        <li><strong>Controlla dominio MX:</strong> verifica se il dominio ha un server di posta valido (lento).</li>
                        <li><strong>Controlla connessione SMTP:</strong> tenta una connessione al server per confermare l'esistenza dell'indirizzo (molto lento).</li>
                    </ul>
                    <p>
                        Una volta selezionate le opzioni desiderate, clicca su <strong>"Pulisci"</strong> per avviare il processo.
                    </p>
                </div>
                <div class="prose space-y-2">
                    <a href="{{ route('email.compare') }}" class="bg-primary-500 text-primary-100 mb-4 flex flex-col items-center justify-center rounded-xl p-4">
                        <div class="mb-2 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-22">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M8 4m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z" />
                                <path d="M4 8m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z" />
                                <path d="M9 15l6 -6" />
                            </svg>
                        </div>
                        <h2 class="mb-2 text-center text-xl font-bold">Unisci liste</h2>
                    </a>
                    <p>
                        Qui puoi confrontare due file contenenti indirizzi email. Carica i due file e seleziona il campo da confrontare (di default "EMAIL").
                    </p>
                    <p>Il sistema ti mostrerà:</p>
                    <ul>
                        <li><strong>Email totali</strong> nei due file</li>
                        <li><strong>Email uniche</strong> (non duplicate)</li>
                        <li><strong>Email comuni</strong> (ripetute in entrambi i file)</li>
                        <li><strong>Email presenti solo nella prima lista</strong></li>
                        <li><strong>Email presenti solo nella seconda lista</strong></li>
                    </ul>
                    <p>
                        Alla fine potrai <strong>scaricare le email uniche</strong> o <strong>le email comuni</strong> con un solo clic.
                    </p>
                </div>
            </div>

            <h2 class="mb-2 mt-6 text-xl font-bold">Suggerimenti</h2>
            <ul>
                <li>I file devono essere in formato CSV con una colonna di intestazione "EMAIL".</li>
                <li>Le operazioni più lente (MX e SMTP) sono consigliate solo per liste piccole.</li>
            </ul>

        </div>
        {{-- <x-slot:footer>
            <span class="text-xs">
                ⚠️ <x-link href="https://tallstackui.com/docs/v2/starter-kit" bold blank sm>Make sure to read the docs about the starter kit!</x-link>
            </span>
        </x-slot:footer> --}}
    </x-card>
</x-app-layout>
