<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeArticle extends Model
{
    protected $table = 'types_articles';

    protected $fillable = [
        'nom',
        'slug',
        'gere_tailles',
        'gere_couleurs',
        'ordre',
        'active',
    ];

    protected $casts = [
        'gere_tailles' => 'boolean',
        'gere_couleurs' => 'boolean',
        'active' => 'boolean',
        'ordre' => 'integer',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
