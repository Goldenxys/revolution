<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommandeRequest extends FormRequest
{
    /**
     * Formulaire public de prise de commande : toute cliente peut envoyer
     * une commande.
     */
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
            'collection' => ['required', Rule::in(['my_verse', 'autre'])],

            // Cliente
            'nom' => ['required', 'string', 'max:120'],
            'telephone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:190'],

            // Livraison
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

            // Article — sans objet pour une casquette (taille unique).
            'taille' => [
                Rule::requiredIf(fn () => $this->input('type_article') !== 'Casquette'),
                'nullable',
                Rule::in(config('revolution.tailles')),
            ],
            'couleur' => ['nullable', Rule::in(config('revolution.couleurs'))],

            // Variante « Autre collection »
            'type_article' => ['required_if:collection,autre', Rule::in(config('revolution.types'))],
            'nom_article' => ['required_if:collection,autre', 'string', 'max:190'],

            // Variante « MY VERSE »
            'verset_reference' => ['nullable', 'string', 'max:120'],
            'verset_texte' => ['nullable', 'string', 'max:2000'],
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
            'email.email' => 'Cet email n\'a pas l\'air valide.',
            'commune.required' => 'Merci de choisir votre commune de livraison.',
            'commune.in' => 'Cette commune n\'est pas dans notre liste de livraison.',
            'mode_livraison.required' => 'Merci de choisir un mode de livraison.',
            'date_souhaitee.required_if' => 'Merci d\'indiquer la date qui vous arrange pour Yango.',
            'date_souhaitee.prohibited_unless' => 'La date n\'est utile qu\'avec Yango livraison.',
            'date_souhaitee.after_or_equal' => 'La date de livraison ne peut pas être dans le passé.',
            'heure_souhaitee.required_if' => 'Merci d\'indiquer l\'heure qui vous arrange pour Yango.',
            'heure_souhaitee.prohibited_unless' => 'L\'heure n\'est utile qu\'avec Yango livraison.',
            'taille.required' => 'Merci de choisir une taille.',
            'taille.in' => 'Cette taille n\'est pas disponible (M, L, XL, XXL uniquement).',
            'type_article.required_if' => 'Merci de choisir le type d\'article.',
            'nom_article.required_if' => 'Merci d\'indiquer le nom de l\'article, ex. « Couronne d\'épines ».',
        ];
    }

    /**
     * Contrôle du nombre de chiffres du téléphone (8 minimum), en plus des
     * règles déclaratives ci-dessus.
     */
    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $telephone = (string) $this->input('telephone');
            $chiffres = preg_replace('/\D+/', '', $telephone) ?? '';

            if (strlen($chiffres) < 8) {
                $validator->errors()->add(
                    'telephone',
                    'Le numéro de téléphone doit contenir au moins 8 chiffres.'
                );
            }
        });
    }
}
