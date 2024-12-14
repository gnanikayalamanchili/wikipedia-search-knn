<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Nicolaslopezj\Searchable\SearchableTrait;

class Article extends Model
{
    /** @use HasFactory<\Database\Factories\ArticleFactory> */
    use HasFactory;
    use Searchable, SearchableTrait;

    protected $fillable = [
        'title', 'body', 'url', 'categories', 'references'
    ];

    protected $casts = [
        'categories' => 'array',
        'references' => 'array'
    ];

    // Searchable configuration
    protected $searchable = [
        'columns' => [
            'articles.title' => 10,
            'articles.body' => 5,
            'articles.categories' => 3
        ]
    ];

    // Laravel Scout search configuration
    public function toSearchableArray(): array
    {
        return [
            'id' => (int) $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'categories' => $this->categories,
        ];
    }
}
