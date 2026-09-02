<?php

namespace App\Http\Requests;

use App\Models\Article;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommandeCatalogueRequest extends FormRequest
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
            'article_id' => ['required', 'integer', 'exists:articles,id'],
            'taille_id' => ['nullable', 'integer', 'exists:tailles,id'],
            'couleur_id' => ['nullable', 'integer', 'exists:couleurs,id'],
            'quantite' => ['required', 'integer', 'min:1', 'max:10'],
            'verset' => ['nullable', 'string', 'max:200'],
            'modele' => ['nullable', 'string', 'max:120'],

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
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'article_id.required' => 'Merci de choisir un article.',
            'article_id.exists' => 'Cet article n\'existe pas.',
            'quantite.min' => 'La quantité doit être d\'au moins 1.',
            'quantite.max' => 'Merci de nous contacter directement pour plus de 10 pièces.',
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
        ];
    }

    /**
     * Validations qui dépendent du contenu de l'article choisi (pas
     * exprimables en règles déclaratives simples) : téléphone à 8 chiffres
     * minimum, taille/couleur exigées seulement si le type d'article les
     * gère, et — règle non négociable — la combinaison taille/couleur
     * demandée doit être réellement disponible au moment de la validation,
     * pas seulement au moment où la page a été chargée (un article peut
     * s'épuiser pendant que la cliente remplit le formulaire).
     */
    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $telephone = (string) $this->input('telephone');
            $chiffres = preg_replace('/\D+/', '', $telephone) ?? '';

            if (strlen($chiffres) < 8) {
                $validator->errors()->add('telephone', 'Le numéro de téléphone doit contenir au moins 8 chiffres.');
            }

            $article = Article::with('typeArticle')->find($this->input('article_id'));

            if (! $article || ! $article->active) {
                $validator->errors()->add('article_id', 'Cet article n\'est plus disponible.');

                return;
            }

            if ($article->gere_tailles && ! $this->input('taille_id')) {
                $validator->errors()->add('taille_id', 'Merci de choisir une taille.');
            }

            if ($article->gere_couleurs && ! $this->input('couleur_id')) {
                $validator->errors()->add('couleur_id', 'Merci de choisir une couleur.');
            }

            if ($validator->errors()->has('taille_id') || $validator->errors()->has('couleur_id')) {
                return;
            }

            $disponible = $article->variantes()
                ->where('taille_id', $article->gere_tailles ? $this->input('taille_id') : null)
                ->where('couleur_id', $article->gere_couleurs ? $this->input('couleur_id') : null)
                ->where('disponible', true)
                ->exists();

            if (! $disponible) {
                $validator->errors()->add('taille_id', 'Cette combinaison vient de s\'épuiser. Merci de choisir une autre taille ou couleur.');
            }
        });
    }
}
