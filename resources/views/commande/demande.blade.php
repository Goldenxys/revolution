@php
    use App\Support\Francais;
    $communes = config('revolution.communes');
    $estMyVerse = $type === 'my_verse';
    $surtitre = $estMyVerse ? 'My Verse' : 'Autre collection';
@endphp

<x-public-layout :titre="'RÉVOLUTION — Ma commande '.$surtitre">
    <x-colonne class="pb-24">

        <div class="text-center mb-8 sm:mb-10 px-2">
            <p class="text-xs uppercase tracking-[0.18em] text-or font-semibold mb-2">{{ $surtitre }}</p>
            <h1 class="text-2xl sm:text-3xl font-semibold text-encre tracking-tight text-balance">
                {{ $estMyVerse ? 'Je passe ma commande My Verse' : 'Je passe ma commande' }}
            </h1>
            <p class="mt-3 text-[15px] leading-relaxed text-encre/80 max-w-[440px] mx-auto text-pretty">
                @if ($estMyVerse)
                    Indiquez votre verset. La gérante règle la taille, la couleur et confirme le montant juste après.
                @else
                    Prenez un petit instant pour remplir ce formulaire en vérifiant vos informations, votre facture vous sera ensuite envoyée.
                @endif
            </p>
        </div>

        <form
            method="POST"
            action="{{ route('commande.demande.store') }}"
            x-data="commandeDemande({
                type: @js($type),
                communes: @js($communes),
                tailles: @js($tailles),
                couleurs: @js($couleurs),
                urlReconnaissance: '{{ route('client.reconnaissance') }}',
                urlCatalogue: '{{ route('commande.catalogue.json') }}',
                nom: @js(old('nom')),
                telephone: @js(old('telephone')),
                email: @js(old('email')),
                commune: @js(old('commune')),
                quartier: @js(old('quartier')),
                modeLivraison: @js(old('mode_livraison')),
                dateSouhaitee: @js(old('date_souhaitee')),
                heureSouhaitee: @js(old('heure_souhaitee')),
                precisions: @js(old('precisions')),
                versets: @js(old('versets')),
                articles: @js(old('articles')),
            })"
            @submit="envoi = true"
            class="space-y-8"
        >
            @csrf
            <input type="hidden" name="collection" value="{{ $type }}">

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
                    <p class="mt-1.5 text-[13px] text-texte-secondaire">Pour recevoir votre reçu de commande et votre carte de fidélité.</p>
                    @error('email')<p class="mt-1.5 text-xs text-rouille">{{ $message }}</p>@enderror
                </div>
            </fieldset>

            @if ($estMyVerse)
                {{-- Bloc 2 — Vos versets (un tee-shirt = un verset, ajout possible) --}}
                <fieldset class="space-y-4 border-t border-filet pt-8">
                    <legend class="text-sm uppercase tracking-[0.14em] text-texte-secondaire mb-1">Votre / vos versets</legend>
                    <p class="text-[13px] text-texte-secondaire">Un tee-shirt = un verset. Ajoutez-en autant que vous voulez commander.</p>

                    @error('versets')<p class="text-xs text-rouille">{{ $message }}</p>@enderror

                    <template x-for="(verset, index) in versets" :key="index">
                        <div class="border border-filet bg-carte p-4 space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-[13px] text-texte-secondaire" x-text="`Tee-shirt My Verse ${index + 1}`"></span>
                                <button type="button" x-show="versets.length > 1" @click="retirerVerset(index)"
                                        class="text-[13px] text-texte-secondaire hover:text-rouille">Retirer</button>
                            </div>

                            <div>
                                <label class="block text-sm mb-2">Verset choisi</label>
                                <input type="text" :name="`versets[${index}][reference]`" x-model="verset.reference"
                                       maxlength="120" placeholder="Ex. Philippiens 4:13"
                                       class="w-full border border-filet bg-creme px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                            </div>

                            <div>
                                <label class="block text-sm mb-2">Texte du verset</label>
                                <textarea :name="`versets[${index}][texte]`" x-model="verset.texte" rows="3" maxlength="2000"
                                          class="w-full border border-filet bg-creme px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none"></textarea>
                                <p class="mt-1.5 text-[13px] text-texte-secondaire">Vérifiez l'orthographe : le verset est imprimé tel que vous l'écrivez.</p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm mb-2">Taille</label>
                                    <select :name="`versets[${index}][taille_id]`" x-model="verset.taille_id"
                                            class="w-full border border-filet bg-creme px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                                        <option value="">—</option>
                                        @foreach ($tailles as $taille)
                                            <option value="{{ $taille->id }}">{{ $taille->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm mb-2">Couleur</label>
                                    <select :name="`versets[${index}][couleur_id]`" x-model="verset.couleur_id"
                                            class="w-full border border-filet bg-creme px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                                        <option value="">—</option>
                                        @foreach ($couleurs as $couleur)
                                            <option value="{{ $couleur->id }}">{{ $couleur->nom }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </template>

                    <button type="button" @click="ajouterVerset()"
                            class="w-full border border-dashed border-filet text-encre py-3 text-sm hover:border-rouille transition rounded-none">
                        + Ajouter un autre tee-shirt My Verse
                    </button>
                </fieldset>
            @endif

            @unless ($estMyVerse)
                {{-- Bloc 2 — Vos articles (recherche dans le catalogue, plusieurs articles possibles) --}}
                <fieldset class="space-y-4 border-t border-filet pt-8">
                    <legend class="text-sm uppercase tracking-[0.14em] text-texte-secondaire mb-1">Vos articles</legend>
                    <p class="text-[13px] text-texte-secondaire">Cherchez un article de notre catalogue, ou indiquez-en un autre. Ajoutez-en autant que vous voulez commander.</p>

                    @php $erreursArticles = collect($errors->keys())->filter(fn ($cle) => str_starts_with($cle, 'articles.'))->map(fn ($cle) => $errors->first($cle))->unique(); @endphp
                    @foreach ($erreursArticles as $message)
                        <p class="text-xs text-rouille">{{ $message }}</p>
                    @endforeach

                    <template x-for="(article, index) in articles" :key="index">
                        <div class="border border-filet bg-carte p-4 space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-[13px] text-texte-secondaire" x-text="`Article ${index + 1}`"></span>
                                <button type="button" x-show="articles.length > 1" @click="retirerArticle(index)"
                                        class="text-[13px] text-texte-secondaire hover:text-rouille">Retirer</button>
                            </div>

                            <div class="relative">
                                <label class="block text-sm mb-2">Nom de l'article</label>
                                <input type="text" :name="`articles[${index}][nom]`" x-model="article.nom"
                                       @input="rechercherArticle(index)" @focus="article.rechercheOuverte = true"
                                       @blur="fermerRechercheDifferee(index)"
                                       maxlength="190" autocomplete="off" placeholder="Ex. Tee-shirt God's Daughter"
                                       class="w-full border border-filet bg-creme px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                                <input type="hidden" :name="`articles[${index}][article_id]`" x-model="article.article_id">

                                <template x-if="article.rechercheOuverte && suggestionsPour(article.nom).length">
                                    <ul class="absolute z-10 left-0 right-0 mt-1 border border-filet bg-carte shadow-lg max-h-56 overflow-auto" x-cloak>
                                        <template x-for="suggestion in suggestionsPour(article.nom)" :key="suggestion.id">
                                            <li @mousedown.prevent="choisirArticle(index, suggestion)"
                                                class="px-4 py-2.5 text-[14px] cursor-pointer hover:bg-creme">
                                                <span x-text="suggestion.nom"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </template>
                                <p class="mt-1.5 text-[13px] text-texte-secondaire" x-show="!article.article_id && article.nom">
                                    Pas encore dans notre catalogue en ligne ? Ce n'est pas grave, indiquez la taille/couleur si vous les connaissez déjà.
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div x-show="optionsTailles(article).length">
                                    <label class="block text-sm mb-2">Taille</label>
                                    <select :name="`articles[${index}][taille_id]`" x-model="article.taille_id"
                                            class="w-full border border-filet bg-creme px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                                        <option value="">—</option>
                                        <template x-for="taille in optionsTailles(article)" :key="taille.id">
                                            <option :value="taille.id" x-text="taille.libelle"></option>
                                        </template>
                                    </select>
                                </div>
                                <div x-show="optionsCouleurs(article).length">
                                    <label class="block text-sm mb-2">Couleur</label>
                                    <select :name="`articles[${index}][couleur_id]`" x-model="article.couleur_id"
                                            class="w-full border border-filet bg-creme px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                                        <option value="">—</option>
                                        <template x-for="couleur in optionsCouleurs(article)" :key="couleur.id">
                                            <option :value="couleur.id" x-text="couleur.nom"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm mb-2">Quantité</label>
                                <input type="number" :name="`articles[${index}][quantite]`" x-model.number="article.quantite"
                                       min="1" max="20"
                                       class="w-24 border border-filet bg-creme px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                            </div>
                        </div>
                    </template>

                    <button type="button" @click="ajouterArticle()"
                            class="w-full border border-dashed border-filet text-encre py-3 text-sm hover:border-rouille transition rounded-none">
                        + Ajouter un autre article
                    </button>
                </fieldset>
            @endunless

            {{-- Précisions (facultatif, commun) --}}
            <fieldset class="space-y-4 {{ $estMyVerse ? '' : 'border-t border-filet pt-8' }}">
                <div>
                    <label for="precisions" class="block text-sm mb-2">
                        Précisions
                        <span class="text-texte-secondaire text-xs">
                            {{ $estMyVerse ? '(couleur souhaitée, modèle, détail convenu…)' : '(note complémentaire, facultatif)' }}
                        </span>
                    </label>
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
