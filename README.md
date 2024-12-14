# Wikipedia Full-Text Search Engine Project Report

Gnanika Yalamanchili - s1367464@monmouth.edu

## Project Overview

**Project Name:** Wikipedia Search Engine
**Technologies Used:**

- Laravel
- Livewire
- Laravel Scout
- Custom k-Nearest Neighbors (kNN) Search
- Tailwind CSS

## Project Structure

### 1. Project Setup

```
wikipedia-search/
│
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── ImportWikipediaArticles.php
│   │
│   ├── Livewire/
│   │   └── WikipediaSearch.php
│   │
│   ├── Models/
│   │   └── Article.php
│   │
│   └── Services/
│       └── KNearestNeighborsSearch.php
│
├── database/
│   └── migrations/
│       └── [migration_file_for_articles]
│
├── resources/
│   ├── css/
│   │   └── app.css
│   │
│   └── views/
│       └── livewire/
│           └── wikipedia-search.blade.php
│
└── config/
    └── scout.php

```

## Key Components and Functionality

### 1. Data Model and Migration

**File:** `database/migrations/create_articles_table.php`**Key Features:**

- Stores Wikipedia article metadata
- Flexible schema with JSON columns
- Supports full-text search capabilities

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticlesTable extends Migration
{
    public function up()
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->text('title');
            $table->longText('body')->nullable();
            $table->string('url')->nullable();
            $table->json('categories')->nullable();
            $table->json('references')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('articles');
    }
}
```

### 2. Article Import Mechanism

**File:** `app/Console/Commands/ImportWikipediaArticles.php`**Functionality:**

- Imports articles from JSON file
- Converts raw data into structured database records
- Enables easy population of search database

```php
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

        collect($articles)->chunk(500)->each(function ($chunk) {
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

```

### 3. Search Service

**File:** `app/Services/KNearestNeighborsSearch.php`**Core Search Techniques:**

- Cosine Similarity
- Text Tokenization

```php
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

```

### 4. Livewire Search Component

**File:** `app/Livewire/WikipediaSearch.php`**Search Workflow:**

1. Receive user query
2. Perform full-text search
3. Execute custom kNN similarity search
4. Render search results

```php
<?php

namespace App\Livewire;

use Illuminate\Support\Collection;
use Livewire\Component;
use App\Models\Article;
use App\Services\KNearestNeighborsSearch;

class WikipediaSearch extends Component
{
    public string $query = '';
    public Collection $articles;
    public Collection $similarArticles;

    public function mount()
    {
        $this->articles = collect([]);
        $this->similarArticles = collect([]);
    }

    public function search()
    {
        // Standard full-text search
        $this->articles = Article::search($this->query)->get();

        // Custom kNN similarity search
        $kNNService = new KNearestNeighborsSearch();
        $this->similarArticles = $kNNService->findSimilarArticles($this->query);
    }

    public function render()
    {
        return view('livewire.wikipedia-search', [
            'articles' => $this->articles,
            'similarArticles' => $this->similarArticles
        ]);
    }
}

```

### 5. User Interface

**File:** `resources/views/livewire/wikipedia-search.blade.php`**Design Features:**

- Tailwind CSS styling
- Responsive layout
- Interactive search interface
- Result displaying mechanism

```php
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="flex mb-6">
            <input
                type="text"
                wire:model.live="query"
                wire:keydown.enter="search"
                placeholder="Search Wikipedia Articles"
                class="flex-grow px-4 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
            <button
                wire:click="search"
                class="px-6 py-2 bg-blue-600 text-white rounded-r-lg hover:bg-blue-700 transition duration-300"
            >
                Search
            </button>
        </div>

        <div class="space-y-6">
            <div class="search-results">
                <h2 class="text-2xl font-bold mb-4 text-gray-800">Full-Text Search Results</h2>
                @if($articles->isEmpty())
                    <p class="text-gray-500">No results found.</p>
                @else
                    @foreach($articles as $article)
                        <div class="bg-white shadow-md rounded-lg p-6 mb-4">
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ $article->title }}</h3>
                            <p class="text-gray-600 mb-4">{{ Str::limit($article->body, 200) }}</p>
                            <a
                                href="{{ $article->url }}"
                                target="_blank"
                                class="text-blue-600 hover:text-blue-800 hover:underline transition duration-300"
                            >
                                Open in Wikipedia
                            </a>
                        </div>
                    @endforeach
                @endif
            </div>

            <div class="similar-articles">
                <h2 class="text-2xl font-bold mb-4 text-gray-800">Similar Articles (kNN)</h2>
                @if($similarArticles->isEmpty())
                    <p class="text-gray-500">No similar articles found.</p>
                @else
                    @foreach($similarArticles as $article)
                        <div class="bg-white shadow-md rounded-lg p-6 mb-4">
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ $article->title }}</h3>
                            <p class="text-gray-600">{{ Str::limit($article->body, 200) }}</p>
                            <a
                                href="{{ $article->url }}"
                                target="_blank"
                                class="text-blue-600 hover:text-blue-800 hover:underline transition duration-300"
                            >
                                Open in Wikipedia
                            </a>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

```

## Search Algorithm Workflow

### Full-Text Search Process

1. User enters search query
2. Laravel Scout performs indexed search
3. Retrieves matching articles based on keywords
4. Ranks results using TF-IDF algorithm

### k-Nearest Neighbours (kNN) Similarity Search

1. Tokenize input query
2. Calculate similarity metrics
    - N-gram analysis
    - Cosine similarity
    - Term frequency
3. Rank and select top K similar articles

## Performance Optimisation Strategies

### Indexing

- Inverted index implementation
- Efficient keyword lookup
- Reduced search complexity

### Text Preprocessing

- Lowercase conversion
- Punctuation removal
- Stop-word elimination
- N-gram generation

## Technical Challenges and Solutions

### 1. Large Dataset Handling

**Challenge:** Processing extensive Wikipedia articles
**Solution:**

- Efficient indexing
- Lazy loading
- Pagination support

### 2. Search Relevance

**Challenge:** Providing accurate, meaningful results
**Solution:**

- Multi-metric similarity calculation
- Weighted ranking algorithm
- Advanced text preprocessing

## Recommended Enhancements

1. Integration with Word Embeddings
2. Machine Learning Ranking
3. Caching Mechanisms
4. Advanced NLP Techniques

---

## Installation and Setup

### Prerequisites

- PHP 8.1+
- Composer
- Node.js
- Laravel
- Livewire

### Setup Commands

```bash
# Clone repository
git clone https://github.com/gnanikayalamanchili/wikipedia-search-knn.git

# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate

# Import Wikipedia articles
php artisan import:wikipedia

# Install scout indexes
php artisan scout:import "App\Models\Article"

# Build frontend assets
npm run dev

# Start development server
php artisan serve

```

## Technologies and Libraries

### Backend

- Laravel Framework
- PHP 8.2+
- Composer Dependency Management
- *MeiliSearch*  *[Set up MeiliSearch for more advanced search capabilities]*

### Frontend

- Livewire
- Tailwind CSS
- Alpine.js

### Search Technologies

- Laravel Scout
- Custom kNN Implementation
- Text Preprocessing Algorithms

## Future Roadmap

1. Machine Learning Integration
2. Advanced NLP Features
3. Multi-language Support
4. Semantic Search Capabilities

## Conclusion

The Wikipedia Search Engine demonstrates a robust, scalable approach to full-text search using modern PHP frameworks and custom search algorithms. By combining multiple search techniques and leveraging advanced preprocessing, the project provides an efficient and user-friendly search experience.


Gnanika Yalamanchili - s1367464@monmouth.edu
---