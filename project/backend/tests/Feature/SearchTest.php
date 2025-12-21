<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Article;
use App\Models\User;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a user for search tests
        $this->user = User::factory()->create();
    }

    /**
     * Test search is case insensitive.
     */
    public function test_search_is_case_insensitive()
    {
        Article::factory()->create([
            'title' => 'Bonjour Monde',
            'author_id' => $this->user->id,
            'content' => 'Content here'
        ]);

        $response = $this->getJson('/api/articles/search?q=bonjour');

        $response->assertStatus(200)
                 ->assertJsonCount(1);
    }

    /**
     * Test search is accent insensitive.
     */
    public function test_search_is_accent_insensitive()
    {
        Article::factory()->create([
            'title' => 'L\'été indien',
            'author_id' => $this->user->id,
            'content' => 'Content here'
        ]);

        // Search "ete" should find "été"
        $response = $this->getJson('/api/articles/search?q=ete');

        $response->assertStatus(200)
                 ->assertJsonCount(1);
        
        // Search "été" should find "été"
        $response2 = $this->getJson('/api/articles/search?q=été');
        $response2->assertStatus(200)
                  ->assertJsonCount(1);
    }

    /**
     * Test search handles special characters properly.
     */
    public function test_search_handles_special_characters()
    {
        Article::factory()->create([
            'title' => 'Romeo & Juliet',
            'author_id' => $this->user->id,
            'content' => 'Content here'
        ]);

        // Search with encoded character
        $response = $this->getJson('/api/articles/search?q=Romeo & Juliet');
        $response->assertStatus(200)
                 ->assertJsonCount(1);
    
    }
    
}
