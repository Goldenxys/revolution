@php $record = $getRecord(); @endphp

@if ($record)
    @livewire(\App\Livewire\MatriceDisponibiliteArticle::class, ['article' => $record], key('matrice-disponibilite-'.$record->getKey()))
@else
    <div class="rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-white/5 px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
        Enregistrez d'abord l'article (onglet « Informations ») : vous pourrez ensuite définir dans quelles tailles et couleurs il est disponible.
    </div>
@endif
