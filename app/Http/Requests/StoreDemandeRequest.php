<?php

namespace App\Http\Requests;

use App\Models\Client;
use App\Models\Commande;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Formulaire de demande V2 (§4.3) : la cliente dépose une demande courte.
 * Elle ne choisit plus d'article — elle dit seulement s'il s'agit d'un
 * tee-shirt My Verse (et fournit alors verset / taille / couleur) ou d'un
 * autre article. La gérante compose et valide ensuite. Le serveur ne
 * calcule aucun total ferme.
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
            'taille' => ['nullable', 'required_if:collection,my_verse', Rule::in(config('revolution.tailles'))],
            'couleur' => ['nullable', Rule::in(config('revolution.couleurs'))],
            'verset_reference' => ['nullable', 'string', 'max:120'],
            'verset_texte' => ['nullable', 'string', 'max:2000'],
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
            'collection.required' => 'Choisissez le type de commande.',
            'collection.in' => 'Choisissez le type de commande.',
            'taille.required_if' => 'Merci de choisir la taille de votre tee-shirt My Verse.',
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
