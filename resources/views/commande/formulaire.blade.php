@php
    $estMyVerse = $variante === 'my_verse';
    $surtitre = $estMyVerse ? 'My Verse' : 'Autre collection';
    $tailles = config('revolution.tailles');
    $types = config('revolution.types');
    $couleurs = config('revolution.couleurs');
    $communes = config('revolution.communes');
@endphp

<x-public-layout :titre="'RÉVOLUTION — '.$surtitre">
    <x-colonne>

        <div class="text-center mb-8 sm:mb-10 px-2">
            <p class="text-xs uppercase tracking-[0.18em] text-or font-semibold mb-2">{{ $surtitre }}</p>
            <h1 class="text-2xl sm:text-3xl font-semibold text-encre tracking-tight text-balance">Je passe ma commande</h1>
        </div>

        <form
            method="POST"
            action="{{ route('commande.store') }}"
            x-data="commandeForm({
                communes: @js($communes),
                typesSansTaille: @js(config('revolution.types_sans_taille')),
                urlReconnaissance: '{{ route('client.reconnaissance') }}',
                nom: '{{ old('nom') }}',
                telephone: '{{ old('telephone') }}',
                email: '{{ old('email') }}',
                commune: '{{ old('commune') }}',
                modeLivraison: '{{ old('mode_livraison') }}',
                dateSouhaitee: '{{ old('date_souhaitee') }}',
                heureSouhaitee: '{{ old('heure_souhaitee') }}',
                typeArticle: '{{ old('type_article') }}',
            })"
            @submit="envoi = true"
            class="space-y-8"
        >
            @csrf
            <input type="hidden" name="collection" value="{{ $variante }}">

            {{-- Cliente --}}
            <fieldset class="space-y-5">
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

            {{-- Livraison --}}
            <fieldset class="space-y-5 border-t border-filet pt-8">
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

            {{-- Article --}}
            <fieldset class="space-y-5 border-t border-filet pt-8">
                @if ($estMyVerse)
                    <div>
                        <label for="taille" class="block text-sm mb-2">Taille du tee-shirt <span class="text-rouille">*</span></label>
                        <select id="taille" name="taille" required
                                class="w-full border {{ $errors->has('taille') ? 'border-rouille' : 'border-filet' }} bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                            <option value="" disabled {{ old('taille') ? '' : 'selected' }}>Choisissez une taille</option>
                            @foreach ($tailles as $taille)
                                <option value="{{ $taille }}" @selected(old('taille') === $taille)>{{ $taille }}</option>
                            @endforeach
                        </select>
                        @error('taille')<p class="mt-1.5 text-xs text-rouille">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="couleur" class="block text-sm mb-2">Couleur du tee-shirt</label>
                        <select id="couleur" name="couleur"
                                class="w-full border border-filet bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                            <option value="" {{ old('couleur') ? '' : 'selected' }}>Sans préférence</option>
                            @foreach ($couleurs as $couleur)
                                <option value="{{ $couleur }}" @selected(old('couleur') === $couleur)>{{ $couleur }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="verset_reference" class="block text-sm mb-2">Verset choisi</label>
                        <input type="text" id="verset_reference" name="verset_reference" maxlength="120"
                               value="{{ old('verset_reference') }}" placeholder="Ex. Philippiens 4:13"
                               class="w-full border border-filet bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                    </div>

                    <div>
                        <label for="verset_texte" class="block text-sm mb-2">Texte du verset</label>
                        <textarea id="verset_texte" name="verset_texte" rows="4" maxlength="2000"
                                  class="w-full border border-filet bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">{{ old('verset_texte') }}</textarea>
                        <p class="mt-1.5 text-[13px] text-texte-secondaire">
                            Vérifiez bien l'orthographe : le verset est imprimé tel que vous l'écrivez.
                        </p>
                    </div>
                @else
                    <div>
                        <label for="type_article" class="block text-sm mb-2">Type d'article <span class="text-rouille">*</span></label>
                        <select id="type_article" name="type_article" required
                                x-model="typeArticle"
                                class="w-full border {{ $errors->has('type_article') ? 'border-rouille' : 'border-filet' }} bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                            <option value="" disabled {{ old('type_article') ? '' : 'selected' }}>Choisissez un type d'article</option>
                            @foreach ($types as $type)
                                <option value="{{ $type }}" @selected(old('type_article') === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                        @error('type_article')<p class="mt-1.5 text-xs text-rouille">{{ $message }}</p>@enderror
                    </div>

                    <div x-show="!sansTaille" x-cloak>
                        <label for="taille" class="block text-sm mb-2">Taille <span class="text-rouille">*</span></label>
                        <select id="taille" name="taille" :required="!sansTaille" :disabled="sansTaille"
                                class="w-full border {{ $errors->has('taille') ? 'border-rouille' : 'border-filet' }} bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                            <option value="" disabled {{ old('taille') ? '' : 'selected' }}>Choisissez une taille</option>
                            @foreach ($tailles as $taille)
                                <option value="{{ $taille }}" @selected(old('taille') === $taille)>{{ $taille }}</option>
                            @endforeach
                        </select>
                        @error('taille')<p class="mt-1.5 text-xs text-rouille">{{ $message }}</p>@enderror
                    </div>

                    <template x-if="sansTaille">
                        <p class="text-[14px] text-texte-secondaire revo-apparition" x-cloak>
                            Cet article est en taille unique : pas besoin de préciser une taille.
                        </p>
                    </template>

                    <div>
                        <label for="couleur" class="block text-sm mb-2">Couleur</label>
                        <select id="couleur" name="couleur"
                                class="w-full border border-filet bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                            <option value="" {{ old('couleur') ? '' : 'selected' }}>Sans préférence</option>
                            @foreach ($couleurs as $couleur)
                                <option value="{{ $couleur }}" @selected(old('couleur') === $couleur)>{{ $couleur }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="nom_article" class="block text-sm mb-2">Nom de l'article <span class="text-rouille">*</span></label>
                        <input type="text" id="nom_article" name="nom_article" required maxlength="190"
                               value="{{ old('nom_article') }}" placeholder="Ex. Couronne d'épines"
                               class="w-full border {{ $errors->has('nom_article') ? 'border-rouille' : 'border-filet' }} bg-carte px-4 py-3 text-base focus:border-rouille focus:ring-0 rounded-none">
                        @error('nom_article')<p class="mt-1.5 text-xs text-rouille">{{ $message }}</p>@enderror
                    </div>
                @endif
            </fieldset>

            <button type="submit" :disabled="envoi"
                    class="w-full bg-rouille text-white py-4 text-sm uppercase tracking-wide font-medium transition hover:bg-rouille/90 disabled:opacity-60 rounded-none">
                <span x-show="!envoi">Valider ma commande</span>
                <span x-show="envoi" x-cloak>Enregistrement…</span>
            </button>
        </form>

    </x-colonne>
</x-public-layout>
