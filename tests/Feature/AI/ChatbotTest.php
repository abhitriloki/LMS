<?php

namespace Tests\Feature\AI;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Enrollment;
use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\AIRecommendation;
use App\Services\AI\Contracts\AIServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected $mockAIService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'employee',
        ]);

        // Mock AI service
        $this->mockAIService = Mockery::mock(AIServiceInterface::class);
        $this->app->instance(AIServiceInterface::class, $this->mockAIService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function user_can_send_message_to_chatbot()
    {
        $this->mockAIService->shouldReceive('generateText')
            ->andReturn('greeting');

        $response = $this->actingAs($this->user)
            ->postJson('/api/chatbot/message', [
                'message' => 'Hello!',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'conversation_id',
                'message' => [
                    'id',
                    'content',
                    'role',
                    'created_at',
                ],
                'intent',
                'confidence',
            ]);

        $this->assertDatabaseHas('chatbot_conversations', [
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('chatbot_messages', [
            'role' => 'user',
            'content' => 'Hello!',
        ]);

        $this->assertDatabaseHas('chatbot_messages', [
            'role' => 'assistant',
        ]);
    }

    /** @test */
    public function user_can_continue_existing_conversation()
    {
        $this->mockAIService->shouldReceive('generateText')
            ->andReturn('greeting');

        $conversation = ChatbotConversation::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/chatbot/message', [
                'message' => 'How are you?',
                'conversation_id' => $conversation->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'conversation_id' => $conversation->id,
            ]);

        $this->assertEquals(2, $conversation->fresh()->getMessageCount());
    }

    /** @test */
    public function user_can_start_new_conversation()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/chatbot/start');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'conversation' => [
                    'id',
                    'title',
                    'status',
                    'started_at',
                ],
            ]);

        $this->assertDatabaseHas('chatbot_conversations', [
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function user_can_get_conversation_history()
    {
        $conversation = ChatbotConversation::factory()->create([
            'user_id' => $this->user->id,
        ]);

        ChatbotMessage::factory()->count(5)->create([
            'conversation_id' => $conversation->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/chatbot/conversations/{$conversation->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'conversation' => [
                    'id',
                    'title',
                    'status',
                    'message_count',
                ],
                'messages',
            ])
            ->assertJsonCount(5, 'messages');
    }

    /** @test */
    public function user_can_get_all_conversations()
    {
        ChatbotConversation::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/chatbot/conversations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'conversations',
                'pagination',
            ])
            ->assertJsonCount(3, 'conversations');
    }

    /** @test */
    public function user_can_end_conversation()
    {
        $conversation = ChatbotConversation::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/chatbot/conversations/{$conversation->id}/end");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('chatbot_conversations', [
            'id' => $conversation->id,
            'status' => 'ended',
        ]);

        $this->assertNotNull($conversation->fresh()->ended_at);
    }

    /** @test */
    public function user_can_rate_conversation()
    {
        $conversation = ChatbotConversation::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/chatbot/conversations/{$conversation->id}/rate", [
                'rating' => 5,
                'feedback' => 'Very helpful!',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('chatbot_conversations', [
            'id' => $conversation->id,
            'rating' => 5,
            'feedback' => 'Very helpful!',
        ]);
    }

    /** @test */
    public function user_can_delete_conversation()
    {
        $conversation = ChatbotConversation::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/chatbot/conversations/{$conversation->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('chatbot_conversations', [
            'id' => $conversation->id,
        ]);
    }

    /** @test */
    public function chatbot_provides_course_recommendations()
    {
        $this->mockAIService->shouldReceive('generateText')
            ->andReturn('course_recommendation');

        $category = CourseCategory::factory()->create();
        $course = Course::factory()->create([
            'category_id' => $category->id,
            'is_published' => true,
        ]);

        AIRecommendation::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/chatbot/message', [
                'message' => 'Can you recommend some courses?',
            ]);

        $response->assertStatus(200);
        
        $content = $response->json('message.content');
        $this->assertStringContainsString($course->title, $content);
    }

    /** @test */
    public function chatbot_shows_user_progress()
    {
        $this->mockAIService->shouldReceive('generateText')
            ->andReturn('progress_inquiry');

        $category = CourseCategory::factory()->create();
        $course = Course::factory()->create([
            'category_id' => $category->id,
        ]);

        Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'status' => 'in_progress',
            'progress_percentage' => 75,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/chatbot/message', [
                'message' => 'What is my progress?',
            ]);

        $response->assertStatus(200);
        
        $content = $response->json('message.content');
        $this->assertStringContainsString('75', $content);
        $this->assertStringContainsString('progress', strtolower($content));
    }

    /** @test */
    public function chatbot_helps_with_enrollment()
    {
        $this->mockAIService->shouldReceive('generateText')
            ->andReturn('enrollment_assistance');

        $category = CourseCategory::factory()->create();
        $course = Course::factory()->create([
            'category_id' => $category->id,
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/chatbot/message', [
                'message' => "I want to enroll in course {$course->id}",
            ]);

        $response->assertStatus(200);
        
        $content = $response->json('message.content');
        $this->assertStringContainsString($course->title, $content);
    }

    /** @test */
    public function chatbot_detects_already_enrolled_courses()
    {
        $this->mockAIService->shouldReceive('generateText')
            ->andReturn('enrollment_assistance');

        $category = CourseCategory::factory()->create();
        $course = Course::factory()->create([
            'category_id' => $category->id,
            'is_published' => true,
        ]);

        Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'status' => 'in_progress',
            'progress_percentage' => 40,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/chatbot/message', [
                'message' => "Enroll me in course {$course->id}",
            ]);

        $response->assertStatus(200);
        
        $content = $response->json('message.content');
        $this->assertStringContainsString('already enrolled', $content);
        $this->assertStringContainsString('40', $content);
    }

    /** @test */
    public function chatbot_checks_prerequisites()
    {
        $this->mockAIService->shouldReceive('generateText')
            ->andReturn('enrollment_assistance');

        $category = CourseCategory::factory()->create();
        
        $prerequisite = Course::factory()->create([
            'category_id' => $category->id,
            'title' => 'Prerequisite Course',
        ]);

        $course = Course::factory()->create([
            'category_id' => $category->id,
            'is_published' => true,
            'prerequisites' => [$prerequisite->id],
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/chatbot/message', [
                'message' => "Enroll in course {$course->id}",
            ]);

        $response->assertStatus(200);
        
        $content = $response->json('message.content');
        $this->assertStringContainsString('prerequisite', $content);
        $this->assertStringContainsString($prerequisite->title, $content);
    }

    /** @test */
    public function user_cannot_access_other_users_conversations()
    {
        $otherUser = User::factory()->create();
        $conversation = ChatbotConversation::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/chatbot/conversations/{$conversation->id}");

        $response->assertStatus(404);
    }

    /** @test */
    public function message_validation_requires_message_content()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/chatbot/message', [
                'message' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    /** @test */
    public function message_validation_limits_message_length()
    {
        $this->mockAIService->shouldReceive('generateText')
            ->andReturn('general_inquiry');

        $longMessage = str_repeat('a', 1001);

        $response = $this->actingAs($this->user)
            ->postJson('/api/chatbot/message', [
                'message' => $longMessage,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    /** @test */
    public function rating_validation_requires_valid_rating()
    {
        $conversation = ChatbotConversation::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/chatbot/conversations/{$conversation->id}/rate", [
                'rating' => 6,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    /** @test */
    public function guest_cannot_access_chatbot()
    {
        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'Hello',
        ]);

        $response->assertStatus(401);
    }
}
