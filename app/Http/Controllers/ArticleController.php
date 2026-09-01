<?php

namespace App\Http\Controllers;

use App\Models\Article;

class ArticleController extends Controller
{
    public function index()
    {
        $articles = Article::published()->latest('published_at')->get();

        return view('lab', [
            // Falls back to the most recent post when nothing is flagged,
            // so the page is never headless.
            'featured' => $articles->firstWhere('is_featured', true) ?? $articles->first(),
            'articles' => $articles,
        ]);
    }

    public function show(Article $article)
    {
        abort_unless($article->isPublished(), 404);

        $article->load('products.images');

        return view('article', [
            'article' => $article,
            'next' => Article::published()
                ->where('published_at', '<', $article->published_at)
                ->latest('published_at')
                ->first()
                ?? Article::published()->latest('published_at')->where('id', '!=', $article->id)->first(),
        ]);
    }
}
