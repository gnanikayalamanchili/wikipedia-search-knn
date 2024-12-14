<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KNearestNeighborsSearch
{
    // Calculate cosine similarity between two text documents
    private function cosineSimilarity($doc1, $doc2): float|int
    {
        $tokens1 = $this->tokenize($doc1);
        $tokens2 = $this->tokenize($doc2);

        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        $vector1 = array_count_values($tokens1);
        $vector2 = array_count_values($tokens2);

        foreach ($vector1 as $token => $count) {
            $dotProduct += $count * ($vector2[$token] ?? 0);
            $magnitude1 += $count * $count;
        }

        foreach ($vector2 as $count) {
            $magnitude2 += $count * $count;
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        return $magnitude1 * $magnitude2 > 0
            ? $dotProduct / ($magnitude1 * $magnitude2)
            : 0;
    }

    // Basic tokenization and lowercasing
    private function tokenize($text): array|false
    {
        return array_filter(
            preg_split('/\W+/', strtolower($text)),
            fn($token) => strlen($token) > 2
        );
    }

    // Find k most similar articles
    public function findSimilarArticles($query, $k = 5): Collection
    {
        $articles = Article::all();

        $similarities = $articles->map(function ($article) use ($query) {
            return [
                'article' => $article,
                'similarity' => $this->cosineSimilarity($query, $article->body)
            ];
        });

        return $similarities
            ->sortByDesc('similarity')
            ->take($k)
            ->pluck('article')
            ->values(); // Ensures a fresh collection
    }
}
