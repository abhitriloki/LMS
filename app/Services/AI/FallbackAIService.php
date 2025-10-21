<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\AIServiceInterface;
use App\Exceptions\AIServiceException;
use Illuminate\Support\Facades\Log;

class FallbackAIService implements AIServiceInterface
{
    /**
     * Generate text using fallback logic
     */
    public function generateText(string $prompt, array $options = []): string
    {
        Log::warning('Using fallback AI service for text generation');
        
        // Return a generic response indicating AI is unavailable
        return "AI service is currently unavailable. This is a fallback response.";
    }

    /**
     * Analyze text using fallback logic
     */
    public function analyzeText(string $text, string $task): array
    {
        Log::warning('Using fallback AI service for text analysis');
        
        // Return basic analysis based on simple heuristics
        return match($task) {
            'readability' => $this->basicReadabilityAnalysis($text),
            'sentiment' => $this->basicSentimentAnalysis($text),
            'summary' => $this->basicSummary($text),
            'keywords' => $this->basicKeywordExtraction($text),
            default => [
                'status' => 'unavailable',
                'message' => 'AI analysis service is temporarily unavailable'
            ]
        };
    }

    /**
     * Generate image - not supported in fallback
     */
    public function generateImage(string $prompt): string
    {
        Log::warning('Image generation not available in fallback service');
        throw new AIServiceException('Image generation service is temporarily unavailable');
    }

    /**
     * Transcribe audio - not supported in fallback
     */
    public function transcribeAudio(string $audioPath): string
    {
        Log::warning('Audio transcription not available in fallback service');
        throw new AIServiceException('Audio transcription service is temporarily unavailable');
    }

    /**
     * Text to speech - not supported in fallback
     */
    public function textToSpeech(string $text): string
    {
        Log::warning('Text-to-speech not available in fallback service');
        throw new AIServiceException('Text-to-speech service is temporarily unavailable');
    }

    /**
     * Check if service is available
     */
    public function isAvailable(): bool
    {
        return true; // Fallback is always "available"
    }

    /**
     * Get service name
     */
    public function getName(): string
    {
        return 'Fallback';
    }

    /**
     * Basic readability analysis
     */
    protected function basicReadabilityAnalysis(string $text): array
    {
        $wordCount = str_word_count($text);
        $sentenceCount = preg_match_all('/[.!?]+/', $text);
        $avgWordsPerSentence = $sentenceCount > 0 ? $wordCount / $sentenceCount : $wordCount;
        
        // Simple readability score
        $score = 100 - ($avgWordsPerSentence * 2);
        $score = max(0, min(100, $score));
        
        $level = match(true) {
            $score >= 80 => 'Easy',
            $score >= 60 => 'Moderate',
            $score >= 40 => 'Difficult',
            default => 'Very Difficult'
        };

        return [
            'reading_level' => $level,
            'complexity_score' => round($score, 2),
            'word_count' => $wordCount,
            'sentence_count' => $sentenceCount,
            'avg_words_per_sentence' => round($avgWordsPerSentence, 2),
            'suggestions' => [
                'This is a basic analysis. Full AI analysis is temporarily unavailable.'
            ],
            'fallback' => true
        ];
    }

    /**
     * Basic sentiment analysis
     */
    protected function basicSentimentAnalysis(string $text): array
    {
        $positiveWords = ['good', 'great', 'excellent', 'amazing', 'wonderful', 'fantastic', 'positive', 'success'];
        $negativeWords = ['bad', 'poor', 'terrible', 'awful', 'negative', 'fail', 'failure', 'problem'];
        
        $text = strtolower($text);
        $positiveCount = 0;
        $negativeCount = 0;
        
        foreach ($positiveWords as $word) {
            $positiveCount += substr_count($text, $word);
        }
        
        foreach ($negativeWords as $word) {
            $negativeCount += substr_count($text, $word);
        }
        
        $sentiment = 'neutral';
        if ($positiveCount > $negativeCount) {
            $sentiment = 'positive';
        } elseif ($negativeCount > $positiveCount) {
            $sentiment = 'negative';
        }
        
        $confidence = 0.5; // Low confidence for basic analysis

        return [
            'sentiment' => $sentiment,
            'confidence' => $confidence,
            'positive_indicators' => $positiveCount,
            'negative_indicators' => $negativeCount,
            'fallback' => true
        ];
    }

    /**
     * Basic summary
     */
    protected function basicSummary(string $text): array
    {
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentences = array_map('trim', $sentences);
        
        // Take first 3 sentences as summary
        $summary = implode('. ', array_slice($sentences, 0, 3)) . '.';
        
        return [
            'summary' => $summary,
            'key_points' => array_slice($sentences, 0, 5),
            'word_count' => str_word_count($text),
            'fallback' => true
        ];
    }

    /**
     * Basic keyword extraction
     */
    protected function basicKeywordExtraction(string $text): array
    {
        // Remove common words
        $commonWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'is', 'are', 'was', 'were', 'be', 'been', 'being'];
        
        $words = str_word_count(strtolower($text), 1);
        $words = array_filter($words, fn($word) => !in_array($word, $commonWords) && strlen($word) > 3);
        
        $wordFreq = array_count_values($words);
        arsort($wordFreq);
        
        $keywords = array_slice(array_keys($wordFreq), 0, 10);
        
        return [
            'keywords' => $keywords,
            'topics' => array_slice($keywords, 0, 5),
            'fallback' => true
        ];
    }
}
