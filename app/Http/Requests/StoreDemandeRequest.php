<?php

namespace App\Http\Requests;

use App\Models\Client;
use App\Models\Commande;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Formulaire de demande V2 (§4.3). Deux cas :
 *   • My Verse — la cliente renseigne un ou plusieurs versets (référence
 *     et/ou texte). Ni taille ni couleur : la gérante les règle.
 *   • Autre collection — seulement coordonnées + livraison.
 *
 * Le serveur ne calcule aucun total ferme.
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
            // Bloc 1 — Vous
            'nom' => ['required', 'string', 'max:120'],
            'telephone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:190'],

            // Bloc 2 — Votre commande
            'collection' => ['required', Rule::in(['my_verse', 'autre'])],
            'versets' => ['exclude_unless:collection,my_verse', 'required', 'array', 'min:1', 'max:10'],
            'versets.*.reference' => ['nullable', 'string', 'max:120'],
            'versets.*.texte' => ['nullable', 'string', 'max:2000'],
            'precisions' => ['nullable', 'string', 'max:500'],

            // Bloc 3 — La livraison (inchangé)
            'commune' => ['required', Rule::in(array_keys(config('revolution.communes')))],
            'quartier' => ['nullable', 'string', 'max:190'],
            'mode_livraison' => ['required', Rule::in(['yango', 'livreur'])],
            'date_souhaitee' => [
                'prohibited_unless:mode_livraison,yango',
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
            'versets.required' => 'Indiquez au moins un verset pour votre tee-shirt My Verse.',
            'versets.min' => 'Indiquez au moins un verset pour votre tee-shirt My Verse.',
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

            // Chaque verset doit porter une référence OU un texte.
            if ($this->input('collection') === 'my_verse') {
                foreach ((array) $this->input('versets', []) as $i => $verset) {
                    if (blank($verset['reference'] ?? null) && blank($verset['texte'] ?? null)) {
                        $validator->errors()->add("versets.{$i}.texte", 'Indiquez la référence ou le texte de ce verset.');
                    }
                }
            }

            // Protection anti-doublon : un même téléphone ne peut pas
            // déposer deux demandes en moins de 90 secondes (§4.3).
            $cle = Client::cleDepuisTelephone($this->input('telephone'));

            $recente = Commande::query()
                ->where('statut', 'en_attente')
                ->whereHas('client', fn ($q) => $q->where('cle', $cle))
                ->where('created_at', '>=', now()->subSeconds(90))
                ->exists();

            if ($recente) {
                $validator->errors()->add('telephone', 'Une demande vient d\'être enregistrée pour ce numéro. Laissez-nous un instant pour la traiter.');
            }
        });
    }
}
