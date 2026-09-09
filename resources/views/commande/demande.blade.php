@php
    use App\Support\Francais;
    $communes = config('revolution.communes');
    $tailles = config('revolution.tailles');
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
                articles: @js($articles),
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
                souhaits: @js(old('souhaits')),
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

                @error('souhaits')<p class="text-xs text-rouille">{{ $message }}</p>@enderror

                <template x-for="(ligne, index) in lignes" :key="index">
                    <div class="border border-filet bg-carte p-4 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-[13px] text-texte-secondaire" x-text="`Article ${index + 1}`"></span>
                            <button type="button" x-show="lignes.length > 1" @click="retirerLigne(index)"
                                    class="text-[13px] text-texte-secondaire hover:text-rouille">Retirer</button>
                        </div>

                        <div>
                            <label class="block text-sm mb-2">Article <span class="text-rouille">*</span></label>
                            <select :name="`souhaits[${index}][article_id]`" x-model="ligne.article_id" required
                                    class="w-full border border-filet bg-creme px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                                <option value="" disabled>Choisissez un article</option>
                                <template x-for="(articlesCol, collection) in articlesParCollection" :key="collection">
                                    <optgroup :label="collection">
                                        <template x-for="a in articlesCol" :key="a.id">
                                            <option :value="a.id" x-text="`${a.nom} — ${francs(a.prix)}`"></option>
                                        </template>
                                    </optgroup>
                                </template>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div x-show="gereTailles(ligne)" x-cloak>
                                <label class="block text-sm mb-2">Taille</label>
                                <select :name="`souhaits[${index}][taille]`" x-model="ligne.taille"
                                        class="w-full border border-filet bg-creme px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                                    <option value="">Indifférent</option>
                                    @foreach ($tailles as $t)
                                        <option value="{{ $t }}">{{ $t }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div x-show="gereCouleurs(ligne)" x-cloak>
                                <label class="block text-sm mb-2">Couleur</label>
                                <select :name="`souhaits[${index}][couleur]`" x-model="ligne.couleur"
                                        class="w-full border border-filet bg-creme px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                                    <option value="">Indifférent</option>
                                    @foreach (config('revolution.couleurs') as $c)
                                        <option value="{{ $c }}">{{ $c }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm mb-2">Quantité</label>
                                <input type="number" min="1" max="10" :name="`souhaits[${index}][quantite]`"
                                       x-model.number="ligne.quantite"
                                       class="w-full border border-filet bg-creme px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                            </div>
                        </div>
                    </div>
                </template>

                <button type="button" @click="ajouterLigne()"
                        class="w-full border border-dashed border-filet text-encre py-3 text-sm hover:border-rouille transition rounded-none">
                    + Ajouter un autre article
                </button>

                <div>
                    <label for="precisions" class="block text-sm mb-2">Précisions <span class="text-texte-secondaire text-xs">(verset, modèle, référence d'une photo vue sur WhatsApp…)</span></label>
                    <textarea id="precisions" name="precisions" rows="3" maxlength="500"
                              x-model="precisions"
                              class="w-full border border-filet bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">{{ old('precisions') }}</textarea>
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
            </fieldset>

            {{-- Bloc 4 — Récapitulatif indicatif --}}
            <template x-if="afficherRecap">
                <div class="border border-filet bg-creme px-5 py-5 text-[14px]" x-cloak>
                    <p class="text-xs uppercase tracking-[0.14em] text-texte-secondaire mb-3">Votre demande</p>
                    <template x-for="(ligne, index) in lignes" :key="index">
                        <div class="flex justify-between gap-3 py-0.5" x-show="articleDe(ligne)">
                            <span>
                                <span x-text="articleDe(ligne)?.nom"></span>
                                <span class="text-texte-secondaire" x-text="[ligne.taille, ligne.couleur].filter(Boolean).join(', ')"></span>
                                <span x-text="`× ${ligne.quantite}`"></span>
                            </span>
                            <span class="whitespace-nowrap text-texte-secondaire" x-text="`env. ${francs(articleDe(ligne).prix * ligne.quantite)}`"></span>
                        </div>
                    </template>
                    <div class="flex justify-between gap-3 py-0.5" x-show="tarifCommune !== null">
                        <span x-text="`Livraison (${commune})`"></span>
                        <span x-text="francs(tarifCommune)"></span>
                    </div>
                    <div class="border-t border-filet my-2"></div>
                    <div class="flex justify-between gap-3 font-semibold">
                        <span>Estimation</span>
                        <span x-text="`env. ${francs(estimationTotale)}`"></span>
                    </div>
                    <p class="mt-3 text-[13px] text-texte-secondaire">Montant définitif confirmé par la gérante à la validation.</p>
                </div>
            </template>

            <button type="submit" :disabled="envoi"
                    class="w-full bg-rouille text-white py-4 text-sm uppercase tracking-wide font-medium transition hover:bg-rouille/90 disabled:opacity-60 rounded-none">
                <span x-show="!envoi">Valider ma commande</span>
                <span x-show="envoi" x-cloak>Enregistrement…</span>
            </button>
        </form>

    </x-colonne>
</x-public-layout>
