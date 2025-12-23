<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityXssTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test XSS sanitization in comments.
     */
    public function test_comment_content_is_sanitized()
    {
        $user = User::factory()->create();
        $article = Article::factory()->create();

        $malliciousContent = '<script>alert("XSS")</script>Hello <img src=x onerror=alert(1)> World';
        
        $response = $this->postJson('/api/comments', [
            'article_id' => $article->id,
            'user_id' => $user->id,
            'content' => $malliciousContent,
        ]);

        $response->assertStatus(201);
        
        // Check that tags are stripped in the response
        $this->assertEquals('alert("XSS")Hello  World', $response->json('content'));

        // Check the database
        $this->assertDatabaseHas('comments', [
            'id' => $response->json('id'),
            'content' => 'alert("XSS")Hello  World',
        ]);
    }

    /**
     * Test CORS headers.
     */
    public function test_cors_headers_restricted()
    {
        $article = Article::factory()->create();

        // Standard origin
        $response = $this->getJson('/api/articles', [
            'Origin' => 'http://localhost:3000'
        ]);

        $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000');

        // Unauthorized origin
        $response = $this->getJson('/api/articles', [
            'Origin' => 'http://localhost:8089'
        ]);

     
        $this->assertFalse($response->headers->has('Access-Control-Allow-Origin') || 
                           $response->headers->get('Access-Control-Allow-Origin') === 'http://localhost:8089');
    }
}
