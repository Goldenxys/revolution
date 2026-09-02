@php
    $communes = config('revolution.communes');
@endphp

<x-public-layout :titre="'RÉVOLUTION — Je passe ma commande'">
    <x-colonne class="pb-40">

        <div class="text-center mb-8 sm:mb-10 px-2">
            <p class="text-xs uppercase tracking-[0.18em] text-or font-semibold mb-2">Nouvelle commande</p>
            <h1 class="text-2xl sm:text-3xl font-semibold text-encre tracking-tight text-balance">Je passe ma commande</h1>
        </div>

        <form
            method="POST"
            action="{{ route('commande.catalogue.store') }}"
            x-data="commandeCatalogue({
                collections: @js($catalogue['collections']),
                articles: @js($catalogue['articles']),
                tailles: @js($catalogue['tailles']),
                couleurs: @js($catalogue['couleurs']),
                communes: @js($communes),
                urlReconnaissance: '{{ route('client.reconnaissance') }}',
                slugCollectionPreselectionnee: @js($collectionPreselectionnee),
            })"
            @submit="envoi = true"
            class="space-y-8"
        >
            @csrf
            <input type="hidden" name="article_id" :value="articleId">
            <input type="hidden" name="taille_id" :value="tailleId">
            <input type="hidden" name="couleur_id" :value="couleurId">
            <input type="hidden" name="quantite" :value="quantite">
            <input type="hidden" name="verset" :value="verset">
            <input type="hidden" name="modele" :value="modele">

            {{-- Bloc 1 — Article --}}
            <fieldset class="space-y-5" x-show="collections.length > 1" x-cloak>
                <div>
                    <p class="block text-sm mb-3">Collection <span class="text-rouille">*</span></p>
                    <div class="grid grid-cols-2 gap-3">
                        <template x-for="c in collections" :key="c.id">
                            <button type="button" @click="choisirCollection(c.id)"
                                    :class="collectionId === c.id ? 'border-rouille bg-creme' : 'border-filet bg-carte hover:border-rouille/50'"
                                    class="text-left border px-4 py-4 transition">
                                <span class="block text-[13px] sm:text-sm font-medium text-encre" x-text="c.nom"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </fieldset>

            <fieldset class="space-y-5" :class="{ 'border-t border-filet pt-8': collections.length > 1 }" x-show="collectionId" x-cloak>
                <div>
                    <p class="block text-sm mb-3">Article <span class="text-rouille">*</span></p>

                    <template x-if="articlesDeLaCollection.length === 0">
                        <p class="text-[14px] text-texte-secondaire">Aucun article disponible dans cette collection pour le moment.</p>
                    </template>

                    <div class="grid grid-cols-2 gap-3">
                        <template x-for="a in articlesDeLaCollection" :key="a.id">
                            <button type="button" @click="choisirArticle(a.id)"
                                    :class="articleId === a.id ? 'border-rouille' : 'border-filet hover:border-rouille/50'"
                                    class="text-left border bg-carte overflow-hidden transition">
                                <div class="aspect-square bg-creme flex items-center justify-center overflow-hidden">
                                    <template x-if="a.photo">
                                        <img :src="a.photo" :alt="a.nom" class="w-full h-full object-cover" loading="lazy">
                                    </template>
                                    <template x-if="!a.photo">
                                        <span class="text-texte-secondaire text-[11px] px-2 text-center">Photo à venir</span>
                                    </template>
                                </div>
                                <div class="px-3 py-2.5">
                                    <p class="text-[13px] font-medium text-encre leading-snug" x-text="a.nom"></p>
                                    <p class="text-[13px] text-rouille mt-1" x-text="formatPrix(a.prix)"></p>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>
            </fieldset>

            <fieldset class="space-y-5 border-t border-filet pt-8" x-show="articleSelectionne" x-cloak>
                <template x-if="articleSelectionne?.gere_tailles">
                    <div>
                        <p class="block text-sm mb-3">Taille <span class="text-rouille">*</span></p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="t in tailles" :key="t.id">
                                <button type="button"
                                        @click="taillesDisponiblesPourArticle.some(d => d.id === t.id) && choisirTaille(t.id)"
                                        :disabled="!taillesDisponiblesPourArticle.some(d => d.id === t.id)"
                                        :class="tailleId === t.id
                                            ? 'border-rouille bg-rouille text-white'
                                            : (taillesDisponiblesPourArticle.some(d => d.id === t.id)
                                                ? 'border-filet bg-carte hover:border-rouille/50'
                                                : 'border-filet bg-carte text-texte-secondaire/50 line-through cursor-not-allowed')"
                                        class="min-w-[52px] min-h-[44px] px-3 border text-sm font-medium transition">
                                    <span x-text="t.libelle"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                <template x-if="articleSelectionne?.gere_couleurs">
                    <div>
                        <p class="block text-sm mb-3">Couleur <span class="text-rouille">*</span></p>
                        <div class="flex flex-wrap gap-3">
                            <template x-for="c in couleurs" :key="c.id">
                                <button type="button"
                                        @click="couleursDisponiblesPourArticle.some(d => d.id === c.id) && (couleurId = c.id)"
                                        :disabled="!couleursDisponiblesPourArticle.some(d => d.id === c.id)"
                                        :class="!couleursDisponiblesPourArticle.some(d => d.id === c.id) ? 'opacity-30 cursor-not-allowed' : ''"
                                        class="flex flex-col items-center gap-1.5 min-h-[44px] transition">
                                    <span class="w-8 h-8 rounded-full border-2 transition"
                                          :class="couleurId === c.id ? 'border-rouille' : 'border-filet'"
                                          :style="`background-color: ${c.code_hex || '#E9E0D5'}`"></span>
                                    <span class="text-[11px] text-texte-secondaire" x-text="c.nom"></span>
                                </button>
                            </template>
                        </div>
                        <template x-if="messageCouleurIndisponible">
                            <p class="mt-2 text-[13px] text-rouille revo-apparition" x-text="messageCouleurIndisponible" x-cloak></p>
                        </template>
                    </div>
                </template>

                <div>
                    <p class="block text-sm mb-3">Quantité</p>
                    <div class="inline-flex items-center border border-filet">
                        <button type="button" @click="quantite = Math.max(1, quantite - 1)"
                                class="w-11 h-11 flex items-center justify-center text-lg hover:bg-creme transition">−</button>
                        <span class="w-12 text-center text-sm font-medium tabular-nums" x-text="quantite"></span>
                        <button type="button" @click="quantite = Math.min(10, quantite + 1)"
                                class="w-11 h-11 flex items-center justify-center text-lg hover:bg-creme transition">+</button>
                    </div>
                </div>

                <template x-if="afficherVerset">
                    <div class="space-y-5 revo-apparition" x-cloak>
                        <template x-if="modelesDisponibles.length > 0">
                            <div>
                                <label for="modele" class="block text-sm mb-2">Modèle</label>
                                <select id="modele" x-model="modele"
                                        class="w-full border border-filet bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                                    <option value="">Sans préférence</option>
                                    <template x-for="m in modelesDisponibles" :key="m">
                                        <option :value="m" x-text="m"></option>
                                    </template>
                                </select>
                            </div>
                        </template>

                        <div>
                            <label for="verset" class="block text-sm mb-2">Verset choisi</label>
                            <textarea id="verset" x-model="verset" rows="4" maxlength="200"
                                      placeholder="Ex. Philippiens 4:13 — Je puis tout par celui qui me fortifie."
                                      class="w-full border border-filet bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none"></textarea>
                            <p class="mt-1.5 text-[13px] text-texte-secondaire">
                                Vérifiez bien l'orthographe : le verset est imprimé tel que vous l'écrivez. <span x-text="verset.length"></span>/200
                            </p>
                        </div>
                    </div>
                </template>
            </fieldset>

            {{-- Bloc 2 — Coordonnées --}}
            <fieldset class="space-y-5 border-t border-filet pt-8" x-show="articleSelectionne" x-cloak>
                <div>
                    <label for="nom" class="block text-sm mb-2">Nom et prénom <span class="text-rouille">*</span></label>
                    <input type="text" id="nom" name="nom" required maxlength="120"
                           x-model="nom" @blur="verifierClient()"
                           class="w-full border {{ $errors->has('nom') ? 'border-rouille' : 'border-filet' }} bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                    @error('nom')<p class="mt-1.5 text-xs text-rouille">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="telephone" class="block text-sm mb-2">Numéro de téléphone <span class="text-rouille">*</span></label>
                    <input type="tel" id="telephone" name="telephone" required
                           x-model="telephone" @blur="verifierClient()"
                           placeholder="07 00 00 00 00"
                           class="w-full border {{ $errors->has('telephone') ? 'border-rouille' : 'border-filet' }} bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                    @error('telephone')<p class="mt-1.5 text-xs text-rouille">{{ $message }}</p>@enderror
                </div>

                <template x-if="clientConnu && clientMessage">
                    <div class="border border-l-4 border-l-or border-filet bg-creme px-4 py-3 text-[14px] revo-apparition" x-cloak>
                        <span x-text="clientMessage"></span>
                    </div>
                </template>

                <div>
                    <label for="email" class="block text-sm mb-2">Email <span class="text-texte-secondaire text-xs">(newsletter, facultatif)</span></label>
                    <input type="email" id="email" name="email" maxlength="190"
                           x-model="email"
                           class="w-full border {{ $errors->has('email') ? 'border-rouille' : 'border-filet' }} bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                    @error('email')<p class="mt-1.5 text-xs text-rouille">{{ $message }}</p>@enderror
                </div>
            </fieldset>

            {{-- Bloc 3 — Livraison --}}
            <fieldset class="space-y-5 border-t border-filet pt-8" x-show="articleSelectionne" x-cloak>
                <div>
                    <label for="commune" class="block text-sm mb-2">Commune de livraison <span class="text-rouille">*</span></label>
                    <select id="commune" name="commune" required x-model="commune"
                            class="w-full border {{ $errors->has('commune') ? 'border-rouille' : 'border-filet' }} bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                        <option value="" disabled selected>Choisissez votre commune</option>
                        @foreach ($communes as $nomCommune => $tarif)
                            <option value="{{ $nomCommune }}">{{ $nomCommune }}</option>
                        @endforeach
                    </select>
                    @error('commune')<p class="mt-1.5 text-xs text-rouille">{{ $message }}</p>@enderror

                    <template x-if="tarifCommune !== null">
                        <div class="mt-3 border border-or px-4 py-2.5 text-[14px] revo-apparition" x-cloak>
                            Livraison à <span x-text="commune"></span> : <span x-text="tarifFormate"></span>
                        </div>
                    </template>
                </div>

                <div>
                    <label for="quartier" class="block text-sm mb-2">Quartier, point de repère</label>
                    <input type="text" id="quartier" name="quartier" maxlength="190" value="{{ old('quartier') }}"
                           placeholder="Ex. Angré, carrefour de la pharmacie"
                           class="w-full border border-filet bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                </div>

                <div>
                    <label for="mode_livraison" class="block text-sm mb-2">Mode de livraison <span class="text-rouille">*</span></label>
                    <select id="mode_livraison" name="mode_livraison" required x-model="modeLivraison"
                            class="w-full border {{ $errors->has('mode_livraison') ? 'border-rouille' : 'border-filet' }} bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                        <option value="" disabled selected>Choisissez un mode de livraison</option>
                        <option value="yango">Je choisis Yango livraison</option>
                        <option value="livreur">J'attends le livreur normal</option>
                    </select>
                    @error('mode_livraison')<p class="mt-1.5 text-xs text-rouille">{{ $message }}</p>@enderror
                </div>

                <template x-if="estYango">
                    <div class="space-y-5 revo-apparition" x-cloak>
                        <p class="text-[14px] text-texte-secondaire">
                            Avec Yango livraison, vous lancez la livraison à l'heure qui vous sied : indiquez ci-dessous la date et l'heure qui vous arrangent.
                        </p>

                        <div>
                            <label for="date_souhaitee" class="block text-sm mb-2">Date souhaitée</label>
                            <input type="date" id="date_souhaitee" name="date_souhaitee"
                                   :min="new Date().toISOString().slice(0,10)"
                                   x-model="dateSouhaitee"
                                   class="w-full border {{ $errors->has('date_souhaitee') ? 'border-rouille' : 'border-filet' }} bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                            @error('date_souhaitee')<p class="mt-1.5 text-xs text-rouille">{{ $message }}</p>@enderror
                            <p class="mt-1.5 text-[13px] text-texte-secondaire" x-show="dateLongue" x-text="dateLongue"></p>
                        </div>

                        <div>
                            <label for="heure_souhaitee" class="block text-sm mb-2">Heure souhaitée</label>
                            <input type="time" id="heure_souhaitee" name="heure_souhaitee"
                                   x-model="heureSouhaitee"
                                   class="w-full border {{ $errors->has('heure_souhaitee') ? 'border-rouille' : 'border-filet' }} bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                            @error('heure_souhaitee')<p class="mt-1.5 text-xs text-rouille">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </template>

                <template x-if="estLivreur">
                    <p class="text-[14px] text-texte-secondaire revo-apparition" x-cloak>
                        Avec le livreur normal, il n'y a pas de date de livraison précise : il livre selon les zones. Nous vous appelons dès qu'il passe dans votre secteur.
                    </p>
                </template>
            </fieldset>

            <button type="submit" :disabled="envoi || !peutValider"
                    x-show="articleSelectionne" x-cloak
                    class="w-full bg-rouille text-white py-4 text-sm uppercase tracking-wide font-medium transition hover:bg-rouille/90 disabled:opacity-60 rounded-none">
                <span x-show="!envoi">Valider ma commande</span>
                <span x-show="envoi" x-cloak>Enregistrement…</span>
            </button>

            {{-- Bloc 4 — Récapitulatif, collé en bas de l'écran dès qu'un article est choisi --}}
            <div x-show="articleSelectionne" x-cloak
                 class="fixed inset-x-0 bottom-0 z-40 border-t border-filet bg-creme/95 backdrop-blur-sm">
                <div class="mx-auto w-full max-w-colonne px-6">
                    <template x-if="recapOuvert">
                        <div class="pt-3 pb-2 space-y-1.5 text-[13px] revo-apparition" x-cloak>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-texte-secondaire truncate">
                                    <span x-text="articleSelectionne?.nom"></span><span x-show="tailleSelectionnee"> — <span x-text="tailleSelectionnee?.libelle"></span></span><span x-show="couleurSelectionnee">, <span x-text="couleurSelectionnee?.nom"></span></span> × <span x-text="quantite"></span>
                                </span>
                                <span class="tabular-nums shrink-0" x-text="formatPrix(sousTotal)"></span>
                            </div>
                            <div class="flex items-center justify-between gap-3" x-show="commune">
                                <span class="text-texte-secondaire">Livraison<span x-show="commune"> (<span x-text="commune"></span>)</span></span>
                                <span class="tabular-nums shrink-0" x-text="formatPrix(fraisLivraison)"></span>
                            </div>
                            <div class="flex items-center justify-between gap-3" x-show="avantagePourcentage > 0">
                                <span class="text-texte-secondaire">Fidélité − <span x-text="avantagePourcentage"></span> %</span>
                                <span class="tabular-nums shrink-0 text-rouille" x-text="'− ' + formatPrix(remiseMontant)"></span>
                            </div>
                            <div class="border-t border-filet"></div>
                        </div>
                    </template>

                    <button type="button" @click="recapOuvert = !recapOuvert"
                            class="w-full flex items-center justify-between gap-3 py-3 min-h-[44px]">
                        <span class="flex items-center gap-1.5 text-[13px] text-texte-secondaire">
                            Total
                            <span class="transition-transform" :class="recapOuvert ? 'rotate-180' : ''">⌃</span>
                        </span>
                        <span class="text-base font-semibold text-encre tabular-nums" x-text="totalFormate"></span>
                    </button>
                </div>
            </div>
        </form>

    </x-colonne>
</x-public-layout>
