<?php

namespace App\Console\Commands;

use App\Models\Article;
use Exception;
use Illuminate\Console\Command;

class ImportWikipediaArticles extends Command
{
    protected $signature = 'import:wikipedia';
    protected $description = 'Import Wikipedia articles from JSON';

    public function handle()
    {
        $jsonContent = file_get_contents(storage_path('app/medium.json'));
        $articles = json_decode($jsonContent, true, 512, JSON_THROW_ON_ERROR);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON file: ' . json_last_error_msg());
        }
        $articles = is_array($articles) ? $articles : [$articles];

        collect($articles)->chunk(100)->each(function ($chunk) {
            $chunk->each(function ($articleData) {
                Article::updateOrCreate([
                    'title' => $articleData['Title'] ?? '',
                    'body' => $articleData['Body'] ?? '',
                    'url' => $articleData['URL'] ?? '',
                    'categories' => $articleData['Categories'] ?? [],
                    'references' => $articleData['References'] ?? []
                ]);
            });
        });

        $this->info('Wikipedia articles imported successfully!');
    }
}
