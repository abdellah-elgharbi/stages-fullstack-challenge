<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    /**
     * Get blog statistics.
     */
    public function index()
    {
        $stats = \Illuminate\Support\Facades\Cache::remember('stats', 300, function () {
            $totalArticles = Article::count();
            $totalComments = Comment::count();
            $totalUsers = User::count();

            // Simplified using withCount to avoid complex group by
            $mostCommented = Article::withCount('comments')
                ->orderBy('comments_count', 'desc')
                ->limit(5)
                ->get();

            $recentArticles = Article::with('author')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            return [
                'total_articles' => $totalArticles,
                'total_comments' => $totalComments,
                'total_users' => $totalUsers,
                'most_commented' => $mostCommented->map(function ($article) {
                    return [
                        'id' => $article->id,
                        'title' => $article->title,
                        'comments_count' => $article->comments_count,
                    ];
                }),
                'recent_articles' => $recentArticles->map(function ($article) {
                    return [
                        'id' => $article->id,
                        'title' => $article->title,
                        'author' => $article->author->name,
                        'created_at' => $article->created_at,
                    ];
                }),
            ];
        });

        return response()->json($stats);
    }
}

