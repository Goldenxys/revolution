@php
    use App\Support\Francais;
    $communes = config('revolution.communes');
    $tailles = config('revolution.tailles');
    $couleurs = config('revolution.couleurs');
@endphp

<x-public-layout :titre="'RÉVOLUTION — Ma commande'">
    <x-colonne class="pb-24">

        <div class="text-center mb-8 sm:mb-10 px-2">
            <p class="text-xs uppercase tracking-[0.18em] text-or font-semibold mb-2">Ma commande</p>
            <h1 class="text-2xl sm:text-3xl font-semibold text-encre tracking-tight text-balance">On enregistre votre commande</h1>
            <p class="mt-3 text-[15px] leading-relaxed text-encre/80 max-w-[440px] mx-auto text-pretty">
                Reprenez simplement ce qui a été convenu avec nous sur WhatsApp. La gérante confirme le montant définitif juste après.
            </p>
        </div>

        <form
            method="POST"
            action="{{ route('commande.demande.store') }}"
            x-data="commandeDemande({
                communes: @js($communes),
                urlReconnaissance: '{{ route('client.reconnaissance') }}',
                nom: @js(old('nom')),
                telephone: @js(old('telephone')),
                email: @js(old('email')),
                commune: @js(old('commune')),
                quartier: @js(old('quartier')),
                modeLivraison: @js(old('mode_livraison')),
                dateSouhaitee: @js(old('date_souhaitee')),
                heureSouhaitee: @js(old('heure_souhaitee')),
                precisions: @js(old('precisions')),
                collection: @js(old('collection')),
                taille: @js(old('taille')),
                couleur: @js(old('couleur')),
                versetReference: @js(old('verset_reference')),
                versetTexte: @js(old('verset_texte')),
            })"
            @submit="envoi = true"
            class="space-y-8"
        >
            @csrf

            {{-- Bloc 1 — Vous --}}
            <fieldset class="space-y-5">
                <legend class="text-sm uppercase tracking-[0.14em] text-texte-secondaire mb-3">Vous</legend>

                <div>
                    <label for="nom" class="block text-sm mb-2">Nom complet <span class="text-rouille">*</span></label>
                    <input type="text" id="nom" name="nom" required maxlength="120"
                           x-model="nom" @blur="verifierClient()"
                           class="w-full border {{ $errors->has('nom') ? 'border-rouille' : 'border-filet' }} bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                    @error('nom')<p class="mt-1.5 text-xs text-rouille">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="telephone" class="block text-sm mb-2">Téléphone <span class="text-rouille">*</span></label>
                    <input type="tel" id="telephone" name="telephone" required
                           x-model="telephone" @blur="verifierClient()"
                           placeholder="07 00 00 00 00"
                           class="w-full border {{ $errors->has('telephone') ? 'border-rouille' : 'border-filet' }} bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                    @error('telephone')<p class="mt-1.5 text-xs text-rouille">{{ $message }}</p>@enderror
                </div>

                <template x-if="clientConnu && clientMessage">
                    <div class="border border-l-4 border-l-or border-filet bg-creme px-4 py-3 text-[14px]" x-cloak>
                        <span x-text="clientMessage"></span>
                    </div>
                </template>

                <div>
                    <label for="email" class="block text-sm mb-2">E-mail <span class="text-texte-secondaire text-xs">(facultatif)</span></label>
                    <input type="email" id="email" name="email" maxlength="190"
                           x-model="email"
                           class="w-full border {{ $errors->has('email') ? 'border-rouille' : 'border-filet' }} bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                    <p class="mt-1.5 text-[13px] text-texte-secondaire">Pour recevoir votre reçu de commande.</p>
                    @error('email')<p class="mt-1.5 text-xs text-rouille">{{ $message }}</p>@enderror
                </div>
            </fieldset>

            {{-- Bloc 2 — Votre commande --}}
            <fieldset class="space-y-5 border-t border-filet pt-8">
                <legend class="text-sm uppercase tracking-[0.14em] text-texte-secondaire mb-3">Votre commande</legend>

                <div>
                    <p class="block text-sm mb-3">Quel type de commande ? <span class="text-rouille">*</span></p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <button type="button" @click="collection = 'my_verse'"
                                :class="collection === 'my_verse' ? 'border-rouille bg-creme' : 'border-filet bg-carte hover:border-rouille/50'"
                                class="text-left border px-4 py-4 transition">
                            <span class="block text-[13px] sm:text-sm font-medium text-encre">Tee-shirt My Verse</span>
                            <span class="block text-[13px] text-texte-secondaire mt-0.5">À votre verset, écrit par vous.</span>
                        </button>
                        <button type="button" @click="collection = 'autre'"
                                :class="collection === 'autre' ? 'border-rouille bg-creme' : 'border-filet bg-carte hover:border-rouille/50'"
                                class="text-left border px-4 py-4 transition">
                            <span class="block text-[13px] sm:text-sm font-medium text-encre">Un autre article</span>
                            <span class="block text-[13px] text-texte-secondaire mt-0.5">Tout le reste de la collection RÉVOLUTION.</span>
                        </button>
                    </div>
                    <input type="hidden" name="collection" :value="collection">
                    @error('collection')<p class="mt-1.5 text-xs text-rouille">{{ $message }}</p>@enderror
                </div>

                {{-- My Verse : le client fournit tout ce qu'il faut pour composer son tee-shirt --}}
                <template x-if="collection === 'my_verse'">
                    <div class="space-y-5 border border-filet bg-carte p-4" x-cloak>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="taille" class="block text-sm mb-2">Taille <span class="text-rouille">*</span></label>
                                <select id="taille" name="taille" x-model="taille"
                                        class="w-full border {{ $errors->has('taille') ? 'border-rouille' : 'border-filet' }} bg-creme px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                                    <option value="">Choisissez</option>
                                    @foreach ($tailles as $t)
                                        <option value="{{ $t }}">{{ $t }}</option>
                                    @endforeach
                                </select>
                                @error('taille')<p class="mt-1.5 text-xs text-rouille">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="couleur" class="block text-sm mb-2">Couleur</label>
                                <select id="couleur" name="couleur" x-model="couleur"
                                        class="w-full border border-filet bg-creme px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                                    <option value="">Sans préférence</option>
                                    @foreach ($couleurs as $c)
                                        <option value="{{ $c }}">{{ $c }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="verset_reference" class="block text-sm mb-2">Verset choisi</label>
                            <input type="text" id="verset_reference" name="verset_reference" maxlength="120"
                                   x-model="versetReference" placeholder="Ex. Philippiens 4:13"
                                   class="w-full border border-filet bg-creme px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                        </div>

                        <div>
                            <label for="verset_texte" class="block text-sm mb-2">Texte du verset</label>
                            <textarea id="verset_texte" name="verset_texte" rows="3" maxlength="2000"
                                      x-model="versetTexte"
                                      class="w-full border border-filet bg-creme px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none"></textarea>
                            <p class="mt-1.5 text-[13px] text-texte-secondaire">Vérifiez l'orthographe : le verset est imprimé tel que vous l'écrivez.</p>
                        </div>
                    </div>
                </template>

                {{-- Autre collection : rien de plus à saisir, la gérante compose --}}
                <template x-if="collection === 'autre'">
                    <p class="text-[14px] text-texte-secondaire border border-l-4 border-l-or border-filet bg-creme px-4 py-3" x-cloak>
                        Pas besoin de détailler ici : la gérante reprend l'article et le prix convenus sur WhatsApp au moment de valider votre commande.
                    </p>
                </template>

                <div x-show="collection" x-cloak>
                    <label for="precisions" class="block text-sm mb-2">Précisions <span class="text-texte-secondaire text-xs">(modèle, référence d'une photo vue sur WhatsApp…)</span></label>
                    <textarea id="precisions" name="precisions" rows="3" maxlength="500"
                              x-model="precisions"
                              class="w-full border border-filet bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none"></textarea>
                </div>
            </fieldset>

            {{-- Bloc 3 — La livraison --}}
            <fieldset class="space-y-5 border-t border-filet pt-8">
                <legend class="text-sm uppercase tracking-[0.14em] text-texte-secondaire mb-3">La livraison</legend>

                <div>
                    <label for="commune" class="block text-sm mb-2">Commune <span class="text-rouille">*</span></label>
                    <select id="commune" name="commune" required x-model="commune"
                            class="w-full border {{ $errors->has('commune') ? 'border-rouille' : 'border-filet' }} bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                        <option value="" disabled>Choisissez votre commune</option>
                        @foreach ($communes as $nomCommune => $tarif)
                            <option value="{{ $nomCommune }}">{{ $nomCommune }}</option>
                        @endforeach
                    </select>
                    @error('commune')<p class="mt-1.5 text-xs text-rouille">{{ $message }}</p>@enderror
                    <template x-if="tarifCommune !== null">
                        <p class="mt-3 border border-or px-4 py-2.5 text-[14px]" x-cloak>
                            Livraison à <span x-text="commune"></span> : <span x-text="francs(tarifCommune)"></span>
                        </p>
                    </template>
                </div>

                <div>
                    <label for="quartier" class="block text-sm mb-2">Quartier, point de repère</label>
                    <input type="text" id="quartier" name="quartier" maxlength="190" x-model="quartier"
                           class="w-full border border-filet bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                </div>

                <div>
                    <label for="mode_livraison" class="block text-sm mb-2">Mode de livraison <span class="text-rouille">*</span></label>
                    <select id="mode_livraison" name="mode_livraison" required x-model="modeLivraison"
                            class="w-full border {{ $errors->has('mode_livraison') ? 'border-rouille' : 'border-filet' }} bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                        <option value="" disabled>Choisissez un mode de livraison</option>
                        <option value="yango">Yango livraison</option>
                        <option value="livreur">Livreur normal</option>
                    </select>
                    @error('mode_livraison')<p class="mt-1.5 text-xs text-rouille">{{ $message }}</p>@enderror
                </div>

                <template x-if="estYango">
                    <div class="space-y-5" x-cloak>
                        <div>
                            <label for="date_souhaitee" class="block text-sm mb-2">Date souhaitée</label>
                            <input type="date" id="date_souhaitee" name="date_souhaitee"
                                   :min="new Date().toISOString().slice(0,10)" x-model="dateSouhaitee"
                                   class="w-full border {{ $errors->has('date_souhaitee') ? 'border-rouille' : 'border-filet' }} bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                            @error('date_souhaitee')<p class="mt-1.5 text-xs text-rouille">{{ $message }}</p>@enderror
                            <p class="mt-1.5 text-[13px] text-texte-secondaire" x-show="dateLongue" x-text="dateLongue"></p>
                        </div>
                        <div>
                            <label for="heure_souhaitee" class="block text-sm mb-2">Heure souhaitée</label>
                            <input type="time" id="heure_souhaitee" name="heure_souhaitee" x-model="heureSouhaitee"
                                   class="w-full border {{ $errors->has('heure_souhaitee') ? 'border-rouille' : 'border-filet' }} bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                            @error('heure_souhaitee')<p class="mt-1.5 text-xs text-rouille">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </template>

                {{-- Rappel des frais de livraison, en bas de la section --}}
                <template x-if="tarifCommune !== null">
                    <div class="border border-filet bg-creme px-5 py-4 text-[14px]" x-cloak>
                        <div class="flex justify-between gap-3">
                            <span>Frais de livraison (<span x-text="commune"></span>)</span>
                            <span class="font-medium" x-text="francs(tarifCommune)"></span>
                        </div>
                        <p class="mt-2 text-[13px] text-texte-secondaire">
                            Le montant des articles est confirmé par la gérante à la validation de votre commande.
                        </p>
                    </div>
                </template>
            </fieldset>

            <button type="submit" :disabled="envoi"
                    class="w-full bg-rouille text-white py-4 text-sm uppercase tracking-wide font-medium transition hover:bg-rouille/90 disabled:opacity-60 rounded-none">
                <span x-show="!envoi">Valider ma commande</span>
                <span x-show="envoi" x-cloak>Enregistrement…</span>
            </button>
        </form>

    </x-colonne>
</x-public-layout>
