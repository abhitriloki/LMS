<?php

namespace Tests\Unit\Services\AI;

use Tests\TestCase;
use App\Services\AI\FallbackAIService;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Exceptions\AIServiceException;

class FallbackAIServiceTest extends TestCase
{
    public function test_implements_ai_service_interface()
    {
        $service = new FallbackAIService();
        $this->assertInstanceOf(AIServiceInterface::class, $service);
    }

    public function test_generate_text_returns_fallback_message()
    {
        $service = new FallbackAIService();
        $result = $service->generateText('Test prompt');

        $this->assertIsString($result);
        $this->assertStringContainsString('fallback', strtolower($result));
    }

    public function test_analyze_text_readability_returns_basic_analysis()
    {
        $service = new FallbackAIService();
        $text = 'This is a simple test. It has multiple sentences. Each sentence is short.';
        
        $result = $service->analyzeText($text, 'readability');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('reading_level', $result);
        $this->assertArrayHasKey('complexity_score', $result);
        $this->assertArrayHasKey('word_count', $result);
        $this->assertArrayHasKey('fallback', $result);
        $this->assertTrue($result['fallback']);
    }

    public function test_analyze_text_sentiment_returns_basic_analysis()
    {
        $service = new FallbackAIService();
        $text = 'This is great! I love it. Everything is wonderful and amazing.';
        
        $result = $service->analyzeText($text, 'sentiment');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sentiment', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertEquals('positive', $result['sentiment']);
        $this->assertTrue($result['fallback']);
    }

    public function test_analyze_text_sentiment_detects_negative()
    {
        $service = new FallbackAIService();
        $text = 'This is terrible. I hate it. Everything is awful and bad.';
        
        $result = $service->analyzeText($text, 'sentiment');

        $this->assertEquals('negative', $result['sentiment']);
    }

    public function test_analyze_text_sentiment_detects_neutral()
    {
        $service = new FallbackAIService();
        $text = 'This is a document. It contains information.';
        
        $result = $service->analyzeText($text, 'sentiment');

        $this->assertEquals('neutral', $result['sentiment']);
    }

    public function test_analyze_text_summary_returns_basic_summary()
    {
        $service = new FallbackAIService();
        $text = 'First sentence. Second sentence. Third sentence. Fourth sentence. Fifth sentence.';
        
        $result = $service->analyzeText($text, 'summary');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('key_points', $result);
        $this->assertArrayHasKey('word_count', $result);
        $this->assertTrue($result['fallback']);
    }

    public function test_analyze_text_keywords_returns_basic_keywords()
    {
        $service = new FallbackAIService();
        $text = 'Learning management system for corporate training. The system provides courses and assessments.';
        
        $result = $service->analyzeText($text, 'keywords');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('keywords', $result);
        $this->assertArrayHasKey('topics', $result);
        $this->assertIsArray($result['keywords']);
        $this->assertTrue($result['fallback']);
    }

    public function test_analyze_text_unknown_task_returns_unavailable()
    {
        $service = new FallbackAIService();
        $result = $service->analyzeText('Test text', 'unknown_task');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('unavailable', $result['status']);
    }

    public function test_generate_image_throws_exception()
    {
        $this->expectException(AIServiceException::class);
        $this->expectExceptionMessage('Image generation service is temporarily unavailable');

        $service = new FallbackAIService();
        $service->generateImage('Test prompt');
    }

    public function test_transcribe_audio_throws_exception()
    {
        $this->expectException(AIServiceException::class);
        $this->expectExceptionMessage('Audio transcription service is temporarily unavailable');

        $service = new FallbackAIService();
        $service->transcribeAudio('/path/to/audio.mp3');
    }

    public function test_text_to_speech_throws_exception()
    {
        $this->expectException(AIServiceException::class);
        $this->expectExceptionMessage('Text-to-speech service is temporarily unavailable');

        $service = new FallbackAIService();
        $service->textToSpeech('Test text');
    }

    public function test_is_available_returns_true()
    {
        $service = new FallbackAIService();
        $this->assertTrue($service->isAvailable());
    }

    public function test_get_name_returns_fallback()
    {
        $service = new FallbackAIService();
        $this->assertEquals('Fallback', $service->getName());
    }
}
