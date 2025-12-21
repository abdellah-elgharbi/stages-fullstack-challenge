<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Article;
use App\Models\Comment;
use App\Models\User;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test deleting the only comment on an article (Reproduces BUG-002).
     */
    public function test_delete_last_comment_works()
    {
        // 1. Create User and Article
        $user = User::factory()->create();
        $article = Article::factory()->create(['author_id' => $user->id]);
        
        // 2. Create exactly 1 comment
        $comment = Comment::factory()->create([
            'article_id' => $article->id,
            'user_id' => $user->id
        ]);
        
        // 3. Delete the comment
        $response = $this->deleteJson("/api/comments/{$comment->id}");
        
        // 4. Assert success (should be 200, currently 500)
        $response->assertStatus(200);
        
        // 5. Assert database is empty of comments
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    /**
     * Test deleting a comment when multiple exist (Regression test).
     */
    public function test_delete_comment_with_remaining_works()
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['author_id' => $user->id]);
        
        $comment1 = Comment::factory()->create(['article_id' => $article->id, 'user_id' => $user->id]);
        $comment2 = Comment::factory()->create(['article_id' => $article->id, 'user_id' => $user->id]);
        
        $response = $this->deleteJson("/api/comments/{$comment1->id}");
        
        $response->assertStatus(200);
        $this->assertDatabaseMissing('comments', ['id' => $comment1->id]);
        $this->assertDatabaseHas('comments', ['id' => $comment2->id]);
    }
}
