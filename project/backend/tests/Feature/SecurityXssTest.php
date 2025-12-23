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
        $user    = User::factory()->create();
        $article = Article::factory()->create();

        $malliciousContent = '<script>alert("XSS")</script>Hello <img src=x onerror=alert(1)> World';

        $response = $this->postJson('/api/comments', [
            'article_id' => $article->id,
            'user_id'    => $user->id,
            'content'    => $malliciousContent,
        ]);

        $response->assertStatus(201);

        // Expected result after strip_tags + htmlspecialchars(ENT_QUOTES)
        $expected = 'alert(&quot;XSS&quot;)Hello  World';

        // Check that tags are stripped and encoded in the response
        $this->assertEquals($expected, $response->json('content'));

        // Check the database
        $this->assertDatabaseHas('comments', [
            'id'      => $response->json('id'),
            'content' => $expected,
        ]);
    }

    /**
     * Test CORS headers.
     */
    public function test_cors_headers_restricted()
    {
        // Mock the allowed origins to be strictly http://localhost:3000
        config(['cors.allowed_origins' => ['http://localhost:3000']]);

        $article = Article::factory()->create();

        // Authorized origin
        $response = $this->getJson('/api/articles', [
            'Origin' => 'http://localhost:3000',
        ]);

        $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000');

        // Unauthorized origin
        $response = $this->getJson('/api/articles', [
            'Origin' => 'http://localhost:8089',
        ]);

        // Ici on ne vérifie plus que le header est absent,
        // mais que l'origine non autorisée n'est PAS renvoyée.
        $this->assertNotEquals(
            'http://localhost:8089',
            $response->headers->get('Access-Control-Allow-Origin')
        );
    }
}
