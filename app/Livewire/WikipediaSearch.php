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
