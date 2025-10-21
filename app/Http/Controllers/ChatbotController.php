<?php

namespace App\Http\Controllers;

use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Services\AI\AIChatbotService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    protected AIChatbotService $chatbotService;

    public function __construct(AIChatbotService $chatbotService)
    {
        $this->middleware('auth');
        $this->chatbotService = $chatbotService;
    }

    /**
     * Process a user message and return bot response
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'conversation_id' => 'nullable|exists:chatbot_conversations,id',
        ]);

        $user = Auth::user();
        $message = $request->input('message');
        $conversationId = $request->input('conversation_id');

        try {
            // Get or create conversation
            if ($conversationId) {
                $conversation = ChatbotConversation::where('id', $conversationId)
                    ->where('user_id', $user->id)
                    ->firstOrFail();
            } else {
                $conversation = $this->createConversation($user, $message);
            }

            // Add user message to conversation
            $userMessage = $conversation->addMessage('user', $message);

            // Get conversation context
            $context = $this->buildConversationContext($conversation);

            // Process message and get response
            $result = $this->chatbotService->processMessage($message, $user, $context);

            // Add bot response to conversation
            $botMessage = $conversation->addMessage('assistant', $result['response'], [
                'intent' => $result['intent'],
                'entities' => $result['entities'],
                'confidence' => $result['confidence'],
            ]);

            return response()->json([
                'success' => true,
                'conversation_id' => $conversation->id,
                'message' => [
                    'id' => $botMessage->id,
                    'content' => $botMessage->content,
                    'role' => $botMessage->role,
                    'created_at' => $botMessage->created_at->toIso8601String(),
                ],
                'intent' => $result['intent'],
                'confidence' => $result['confidence'],
            ]);

        } catch (\Exception $e) {
            Log::error('Chatbot message processing failed', [
                'user_id' => $user->id,
                'message' => $message,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to process message. Please try again.',
            ], 500);
        }
    }

    /**
     * Get conversation history
     */
    public function getConversation(Request $request, int $conversationId): JsonResponse
    {
        $user = Auth::user();

        $conversation = ChatbotConversation::with('messages')
            ->where('id', $conversationId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'status' => $conversation->status,
                'started_at' => $conversation->started_at->toIso8601String(),
                'ended_at' => $conversation->ended_at?->toIso8601String(),
                'rating' => $conversation->rating,
                'message_count' => $conversation->getMessageCount(),
            ],
            'messages' => $conversation->messages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'role' => $message->role,
                    'content' => $message->content,
                    'intent' => $message->metadata['intent'] ?? null,
                    'created_at' => $message->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Get all conversations for the authenticated user
     */
    public function getConversations(Request $request): JsonResponse
    {
        $user = Auth::user();

        $conversations = ChatbotConversation::where('user_id', $user->id)
            ->withCount('messages')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'conversations' => $conversations->map(function ($conversation) {
                $lastMessage = $conversation->getLastMessage();
                
                return [
                    'id' => $conversation->id,
                    'title' => $conversation->title,
                    'status' => $conversation->status,
                    'message_count' => $conversation->messages_count,
                    'last_message' => $lastMessage ? [
                        'content' => $lastMessage->content,
                        'created_at' => $lastMessage->created_at->toIso8601String(),
                    ] : null,
                    'started_at' => $conversation->started_at->toIso8601String(),
                    'rating' => $conversation->rating,
                ];
            }),
            'pagination' => [
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
                'per_page' => $conversations->perPage(),
                'total' => $conversations->total(),
            ],
        ]);
    }

    /**
     * Start a new conversation
     */
    public function startConversation(Request $request): JsonResponse
    {
        $user = Auth::user();

        $conversation = ChatbotConversation::create([
            'user_id' => $user->id,
            'title' => 'New Conversation',
            'status' => 'active',
            'started_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'status' => $conversation->status,
                'started_at' => $conversation->started_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * End a conversation
     */
    public function endConversation(Request $request, int $conversationId): JsonResponse
    {
        $user = Auth::user();

        $conversation = ChatbotConversation::where('id', $conversationId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $conversation->end();

        return response()->json([
            'success' => true,
            'message' => 'Conversation ended successfully.',
        ]);
    }

    /**
     * Rate a conversation
     */
    public function rateConversation(Request $request, int $conversationId): JsonResponse
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();

        $conversation = ChatbotConversation::where('id', $conversationId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $conversation->rate(
            $request->input('rating'),
            $request->input('feedback')
        );

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your feedback!',
        ]);
    }

    /**
     * Delete a conversation
     */
    public function deleteConversation(Request $request, int $conversationId): JsonResponse
    {
        $user = Auth::user();

        $conversation = ChatbotConversation::where('id', $conversationId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $conversation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Conversation deleted successfully.',
        ]);
    }

    /**
     * Create a new conversation
     */
    protected function createConversation($user, string $firstMessage): ChatbotConversation
    {
        // Generate a title from the first message (first 50 chars)
        $title = strlen($firstMessage) > 50 
            ? substr($firstMessage, 0, 47) . '...' 
            : $firstMessage;

        return ChatbotConversation::create([
            'user_id' => $user->id,
            'title' => $title,
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    /**
     * Build conversation context from history
     */
    protected function buildConversationContext(ChatbotConversation $conversation): array
    {
        $recentMessages = $conversation->messages()
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->reverse()
            ->values();

        return [
            'conversation_id' => $conversation->id,
            'message_count' => $conversation->getMessageCount(),
            'recent_messages' => $recentMessages->map(function ($message) {
                return [
                    'role' => $message->role,
                    'content' => $message->content,
                    'intent' => $message->metadata['intent'] ?? null,
                ];
            })->toArray(),
        ];
    }
}
