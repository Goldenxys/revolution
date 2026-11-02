<?php

namespace App\Http\Requests;

use App\Models\Article;
use App\Models\Client;
use App\Models\Commande;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Formulaire de demande V2 (§4.3). Deux cas :
 *   • My Verse — la cliente renseigne un ou plusieurs versets (référence
 *     et/ou texte), avec une taille/couleur souhaitées par verset.
 *   • Autre collection — un ou plusieurs articles (nom libre ou choisi dans
 *     le catalogue, taille/couleur, quantité).
 *
 * Dans les deux cas, ce n'est qu'un souhait : aucune vérification de stock
 * ni de cohérence catalogue ici (la gérante revérifie tout au compositeur,
 * voir ComposerDemande). Le serveur ne calcule aucun total ferme.
 */
class StoreDemandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Reprise d'une demande existante (lien de reprise envoyé par
            // la gérante) — vide pour un dépôt normal.
            'reference' => ['nullable', 'string', 'size:6'],

            // Bloc 1 — Vous
            'nom' => ['required', 'string', 'max:120'],
            'telephone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:190'],

            // Bloc 2 — Votre commande
            'collection' => ['required', Rule::in(['my_verse', 'autre'])],
            'versets' => ['exclude_unless:collection,my_verse', 'required', 'array', 'min:1', 'max:10'],
            'versets.*.reference' => ['nullable', 'string', 'max:120'],
            'versets.*.texte' => ['nullable', 'string', 'max:2000'],
            // Vêtement souhaité — à la différence de taille/couleur, un
            // verset sans vêtement choisi n'a pas de sens : obligatoire.
            'versets.*.type_vetement' => ['required', Rule::in(['tee_shirt', 'pull'])],
            'versets.*.taille_id' => ['nullable', 'integer', 'exists:tailles,id'],
            'versets.*.couleur_id' => ['nullable', 'integer', 'exists:couleurs,id'],

            // Articles « Autre collection » — un souhait, jamais bloquant :
            // volontairement aucune vérification de stock/disponibilité ici,
            // la gérante revérifie tout (stock inclus) au compositeur.
            'articles' => ['exclude_unless:collection,autre', 'nullable', 'array', 'max:20'],
            'articles.*.nom' => ['nullable', 'string', 'max:190'],
            'articles.*.article_id' => ['nullable', 'integer', 'exists:articles,id'],
            'articles.*.taille_id' => ['nullable', 'integer', 'exists:tailles,id'],
            'articles.*.couleur_id' => ['nullable', 'integer', 'exists:couleurs,id'],
            'articles.*.quantite' => ['nullable', 'integer', 'min:1', 'max:20'],

            'precisions' => ['nullable', 'string', 'max:500'],

            // Date de naissance (jour + mois seulement, jamais l'année) —
            // facultative, rattachée à la cliente (clients.naissance_jour/
            // naissance_mois), jamais à une commande précise : une seule
            // valeur par cliente, jamais redemandée/dupliquée à chaque
            // dépôt (voir ResoutClientEtNotifie::trouverOuCreerClient()).
            'naissance_jour' => ['nullable', 'required_with:naissance_mois', 'integer', 'between:1,31'],
            'naissance_mois' => ['nullable', 'required_with:naissance_jour', 'integer', 'between:1,12'],

            // Bloc 3 — La livraison
            'commune' => ['required', Rule::in(array_keys(config('revolution.communes')))],
            'quartier' => ['nullable', 'string', 'max:190'],
            'mode_livraison' => ['required', Rule::in(['yango', 'livreur'])],
            // Facultative pour un livreur normal (jour qui arrange la
            // cliente), obligatoire pour Yango (créneau précis avec l'heure).
            'date_souhaitee' => [
                'nullable',
                'required_if:mode_livraison,yango',
                'date',
                'after_or_equal:today',
            ],
            'heure_souhaitee' => [
                'prohibited_unless:mode_livraison,yango',
                'required_if:mode_livraison,yango',
                'date_format:H:i',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Merci d\'indiquer votre nom et prénom.',
            'telephone.required' => 'Merci d\'indiquer votre numéro de téléphone.',
            'email.email' => 'Cet e-mail n\'a pas l\'air valide.',
            'collection.required' => 'Type de commande manquant.',
            'collection.in' => 'Type de commande manquant.',
            'versets.required' => 'Indiquez au moins un verset pour votre vêtement My Verse.',
            'versets.min' => 'Indiquez au moins un verset pour votre vêtement My Verse.',
            'versets.*.type_vetement.required' => 'Indiquez si vous voulez un tee-shirt ou un pull pour ce verset.',
            'commune.required' => 'Merci de choisir votre commune de livraison.',
            'commune.in' => 'Cette commune n\'est pas dans notre liste de livraison.',
            'mode_livraison.required' => 'Merci de choisir un mode de livraison.',
            'date_souhaitee.required_if' => 'Merci d\'indiquer la date qui vous arrange pour Yango.',
            'heure_souhaitee.required_if' => 'Merci d\'indiquer l\'heure qui vous arrange pour Yango.',
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $chiffres = preg_replace('/\D+/', '', (string) $this->input('telephone')) ?? '';

            if (strlen($chiffres) < 8) {
                $validator->errors()->add('telephone', 'Le numéro doit contenir au moins 8 chiffres.');

                return;
            }

            // Combinaison jour/mois impossible (31 février…) — testée sur
            // une année bissextile pour laisser passer le 29 février.
            $jour = $this->input('naissance_jour');
            $mois = $this->input('naissance_mois');
            if (filled($jour) && filled($mois) && ! checkdate((int) $mois, (int) $jour, 2024)) {
                $validator->errors()->add('naissance_jour', 'Cette date n\'existe pas.');
            }

            // Chaque verset doit porter une référence OU un texte.
            if ($this->input('collection') === 'my_verse') {
                foreach ((array) $this->input('versets', []) as $i => $verset) {
                    if (blank($verset['reference'] ?? null) && blank($verset['texte'] ?? null)) {
                        $validator->errors()->add("versets.{$i}.texte", 'Indiquez la référence ou le texte de ce verset.');
                    }
                }
            }

            // My Verse a son propre formulaire : un article de cette
            // collection ne doit jamais pouvoir être rattaché à une demande
            // « Autre collection » (contournement du champ caché article_id).
            if ($this->input('collection') === 'autre') {
                foreach ((array) $this->input('articles', []) as $i => $article) {
                    $articleId = $article['article_id'] ?? null;

                    if (blank($articleId)) {
                        continue;
                    }

                    if (Article::query()->whereKey($articleId)->whereHas('collection', fn ($q) => $q->where('slug', 'my_verse'))->exists()) {
                        $validator->errors()->add("articles.{$i}.article_id", 'Cet article fait partie de My Verse : utilisez le formulaire My Verse pour le commander.');
                    }
                }
            }

            // Protection anti-doublon : un même téléphone ne peut pas
            // déposer deux demandes en moins de 90 secondes (§4.3). Une
            // reprise explicite (lien envoyé par la gérante, `reference`
            // renseignée) n'est jamais un doublon accidentel : elle n'est
            // pas soumise à cette garde.
            if (blank($this->input('reference'))) {
                $cle = Client::cleDepuisTelephone($this->input('telephone'));

                $recente = Commande::query()
                    ->where('statut', 'en_attente')
                    ->whereHas('client', fn ($q) => $q->where('cle', $cle))
                    ->where('created_at', '>=', now()->subSeconds(90))
                    ->exists();

                if ($recente) {
                    $validator->errors()->add('telephone', 'Une demande vient d\'être enregistrée pour ce numéro. Laissez-nous un instant pour la traiter.');
                }
            }
        });
    }
}
