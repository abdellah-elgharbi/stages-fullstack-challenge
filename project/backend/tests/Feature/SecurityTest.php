<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Article;
use App\Models\User;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that search is not vulnerable to SQL Injection 1=1.
     * Payload: ' OR '1'='1
     */
    public function test_search_is_secure_against_sql_injection_1_equals_1()
    {
        $user = User::factory()->create();
        Article::factory()->count(3)->create(['author_id' => $user->id]);

        $maliciousQuery = "' OR '1'='1";

        $response = $this->getJson("/api/articles/search?q=" . urlencode($maliciousQuery));

        $response->assertStatus(200);
        
        // Should find 0 results because no article contains that specific string
        // If vulnerable, it would return all 3 articles
        $response->assertJsonCount(0);
    }

    /**
     * Test that search is not vulnerable to UNION SELECT injection.
     * Payload: ' UNION SELECT ...
     */
    public function test_search_is_secure_against_union_select()
    {
        // Create an admin user with a specific email
        $user = User::factory()->create(['email' => 'admin@example.com']);
        Article::factory()->create(['title' => 'Safe Article']);

        $maliciousQuery = "' UNION SELECT id, email, password, 1, null, null, now(), now() FROM users #";

        $response = $this->getJson("/api/articles/search?q=" . urlencode($maliciousQuery));

        $response->assertStatus(200);
        
        // Should find 0 results
        $response->assertJsonCount(0);
        
        // The admin email should NOT be in the response
        $response->assertJsonMissing(['admin@example.com']);
    }
}
