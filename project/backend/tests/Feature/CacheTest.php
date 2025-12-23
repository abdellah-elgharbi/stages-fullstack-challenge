<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Article;
use App\Models\Comment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_stats_are_cached()
    {
        // Create data
        Article::factory()->count(5)->create();
        
        // First call - should query DB
        DB::enableQueryLog();
        $this->getJson('/api/stats');
        $initialQueries = count(DB::getQueryLog());
        
        // Second call - should be cached (0 queries ideally, or very few)
        DB::flushQueryLog();
        $this->getJson('/api/stats');
        $cachedQueries = count(DB::getQueryLog());
        
        $this->assertLessThan($initialQueries, $cachedQueries, "Cached request should have fewer queries than initial request");
        $this->assertEquals(0, $cachedQueries, "Cached stats request should have 0 DB queries");
    }

    public function test_articles_list_is_cached()
    {
        // Create data
        Article::factory()->count(5)->create();
        
        // First call
        DB::enableQueryLog();
        $this->getJson('/api/articles');
    
        $initialQueries = count(DB::getQueryLog());
        
        // Second call
        DB::flushQueryLog();
        $this->getJson('/api/articles');
        $cachedQueries = count(DB::getQueryLog());
        
        $this->assertLessThan($initialQueries, $cachedQueries);
        $this->assertEquals(0, $cachedQueries);
    }

    public function test_article_creation_clears_cache()
    {
        $user = User::factory()->create();
        $token = auth()->login($user);
        
        // Warm up cache
        $this->getJson('/api/articles');
        $this->getJson('/api/stats');
        
        $this->assertTrue(Cache::has('articles_list'));
        $this->assertTrue(Cache::has('stats'));
        
        // Create article
        $this->postJson('/api/articles', [
            'title' => 'New Article',
            'content' => 'Content',
            'author_id' => $user->id
        ]);
        
        // Cache should be cleared
        $this->assertFalse(Cache::has('articles_list'), 'articles_list cache should be cleared after creating article');
        $this->assertFalse(Cache::has('stats'), 'stats cache should be cleared after creating article');
    }

    public function test_comment_creation_clears_stats_cache()
    {
        $user = User::factory()->create();
        $article = Article::factory()->create();
        auth()->login($user);
        
        // Warm up cache
        $this->getJson('/api/stats');
        $this->assertTrue(Cache::has('stats'));
        
        // Create comment
        $this->postJson('/api/comments', [
            'article_id' => $article->id,
            'user_id' => $user->id,
            'content' => 'Nice post!',
        ]);
        
        // Cache should be cleared
        $this->assertFalse(Cache::has('stats'), 'stats cache should be cleared after creating comment');
        $this->assertFalse(Cache::has('articles_list'), 'articles_list cache should be cleared after creating comment');
    }
}
