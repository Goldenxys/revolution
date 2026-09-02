<?php

namespace Tests\Feature\Catalogue;

use App\Models\Article;
use Database\Seeders\CatalogueInitialSeeder;
use Database\Seeders\CollectionSeeder;
use Database\Seeders\CouleurSeeder;
use Database\Seeders\TailleSeeder;
use Database\Seeders\TypeArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CatalogueSeederTest extends TestCase
{
    use RefreshDatabase;

    private function seederPrealable(): void
    {
        Artisan::call('db:seed', ['--class' => TypeArticleSeeder::class, '--force' => true]);
        Artisan::call('db:seed', ['--class' => TailleSeeder::class, '--force' => true]);
        Artisan::call('db:seed', ['--class' => CouleurSeeder::class, '--force' => true]);
        Artisan::call('db:seed', ['--class' => CollectionSeeder::class, '--force' => true]);
    }

    public function test_le_catalogue_initial_charge_quarante_articles_sans_doublon(): void
    {
        $this->seederPrealable();
        Artisan::call('db:seed', ['--class' => CatalogueInitialSeeder::class, '--force' => true]);

        $this->assertSame(40, Article::count());

        $doublons = Article::query()
            ->selectRaw('slug, count(*) as total')
            ->groupBy('slug')
            ->havingRaw('count(*) > 1')
            ->pluck('slug');

        $this->assertTrue($doublons->isEmpty(), 'Slugs en double : '.$doublons->implode(', '));

        $this->assertTrue(Article::where('nom', 'like', '%Tote bag%')->exists());

        // Chaque article créé démarre avec toute sa grille cochée disponible.
        $article = Article::where('nom', 'Tee-shirt Couronne d\'épine')->first();
        $this->assertNotNull($article);
        $this->assertFalse($article->estEpuise());
    }

    public function test_relancer_le_seeder_est_sans_effet_idempotent(): void
    {
        $this->seederPrealable();
        Artisan::call('db:seed', ['--class' => CatalogueInitialSeeder::class, '--force' => true]);
        Artisan::call('db:seed', ['--class' => CatalogueInitialSeeder::class, '--force' => true]);

        $this->assertSame(40, Article::count());
    }
}
