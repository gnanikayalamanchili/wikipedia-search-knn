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
