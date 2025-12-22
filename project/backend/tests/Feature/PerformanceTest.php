<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Article;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that article list uses eager loading (constant number of queries).
     */
    public function test_article_list_uses_eager_loading()
    {
        // Use a persistent DB connection for logging? 
        // In-memory sqlite might behave differently with query logging enable/disable, 
        // but DB::enableQueryLog() works usually.

        $user = User::factory()->create();
        // Create 10 articles
        Article::factory()->count(10)->create(['author_id' => $user->id]);

        DB::enableQueryLog();

        $response = $this->getJson('/api/articles');

        $response->assertStatus(200);

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // Expected queries:
        // 1. Select articles
        // 2. Select authors (users)
        // 3. Select comments
        // Total should be around 3. 
        // Definitely much less than 10 (which would be 1 + 10 authors + 10 comments = 21 if N+1).
        
        // Assert less than 5 to be safe (allow for some overhead if any).
        $this->assertLessThan(5, $queryCount, "Too many queries executed: {$queryCount}. Likely N+1 problem.");
    }
}
