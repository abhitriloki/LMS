<?php

namespace App\Services\AI;

use App\Models\CourseLesson;
use App\Models\AIQuestionJob;
use App\Models\GeneratedQuestion;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Exceptions\AIServiceException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;

class AIQuestionGeneratorService
{
    protected AIServiceInterface $aiService;
    protected array $supportedTypes = ['multiple_choice', 'true_false', 'fill_in_blank', 'essay'];
    protected array $difficultyLevels = ['easy', 'medium', 'hard'];

    public function __construct(AIServiceInterface $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Generate questions from lesson content
     */
    public function generateQuestionsFromLesson(
        CourseLesson $lesson,
        array $parameters
    ): array {
        // Extract content text
        $contentText = $this->extractContentText($lesson);

        if (empty($contentText)) {
            throw new AIServiceException('No content text could be extracted from the lesson');
        }

        // Validate parameters
        $validatedParams = $this->validateParameters($parameters);

        // Build AI prompt
        $prompt = $this->buildQuestionGenerationPrompt($contentText, $validatedParams);

        // Generate questions using AI
        $aiResponse = $this->aiService->generateText($prompt, [
            'temperature' => 0.7,
            'max_tokens' => 3000,
        ]);

        // Parse and validate AI response
        $questions = $this->parseAIResponse($aiResponse);

        // Format questions
        return $this->formatQuestions($questions, $validatedParams);
    }

    /**
     * Extract text content from lesson
     */
    public function extractContentText(CourseLesson $lesson): string
    {
        $text = '';

        try {
            if ($lesson->isText()) {
                // Text content is stored directly
                $text = $lesson->content_path ?? '';
            } elseif ($lesson->isPdf()) {
                // Extract text from PDF
                $text = $this->extractTextFromPdf($lesson->content_path);
            } elseif ($lesson->isVideo()) {
                // Extract text from video metadata or transcription
                $text = $this->extractTextFromVideo($lesson);
            }

            // Include lesson title and description
            $text = trim($lesson->title . "\n\n" . ($lesson->description ?? '') . "\n\n" . $text);

            return $text;
        } catch (\Exception $e) {
            Log::error('Failed to extract content text', [
                'lesson_id' => $lesson->id,
                'error' => $e->getMessage()
            ]);
            throw new AIServiceException('Failed to extract content text: ' . $e->getMessage());
        }
    }

    /**
     * Extract text from PDF file
     */
    protected function extractTextFromPdf(string $path): string
    {
        try {
            $fullPath = Storage::path($path);
            
            if (!file_exists($fullPath)) {
                throw new \Exception('PDF file not found');
            }

            $parser = new PdfParser();
            $pdf = $parser->parseFile($fullPath);
            $text = $pdf->getText();

            return trim($text);
        } catch (\Exception $e) {
            Log::warning('PDF text extraction failed', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }

    /**
     * Extract text from video (metadata or transcription)
     */
    protected function extractTextFromVideo(CourseLesson $lesson): string
    {
        // Check if transcription exists in metadata
        $metadata = $lesson->metadata ?? [];
        
        if (isset($metadata['transcription'])) {
            return $metadata['transcription'];
        }

        // Check if there's a separate transcription file
        if (isset($metadata['transcription_path'])) {
            try {
                return Storage::get($metadata['transcription_path']);
            } catch (\Exception $e) {
                Log::warning('Failed to read transcription file', [
                    'path' => $metadata['transcription_path'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Return description as fallback
        return $lesson->description ?? '';
    }

    /**
     * Validate generation parameters
     */
    protected function validateParameters(array $parameters): array
    {
        $defaults = [
            'count' => 5,
            'types' => ['multiple_choice'],
            'difficulty' => 'medium',
            'include_explanation' => true,
            'points_per_question' => 1,
        ];

        $params = array_merge($defaults, $parameters);

        // Validate count
        $params['count'] = max(1, min(20, (int) $params['count']));

        // Validate types
        if (!is_array($params['types'])) {
            $params['types'] = [$params['types']];
        }
        $params['types'] = array_intersect($params['types'], $this->supportedTypes);
        if (empty($params['types'])) {
            $params['types'] = ['multiple_choice'];
        }

        // Validate difficulty
        if (!in_array($params['difficulty'], $this->difficultyLevels)) {
            $params['difficulty'] = 'medium';
        }

        // Validate points
        $params['points_per_question'] = max(0.5, min(10, (float) $params['points_per_question']));

        return $params;
    }

    /**
     * Build AI prompt for question generation
     */
    protected function buildQuestionGenerationPrompt(string $content, array $parameters): string
    {
        $count = $parameters['count'];
        $types = implode(', ', $parameters['types']);
        $difficulty = $parameters['difficulty'];
        $includeExplanation = $parameters['include_explanation'] ? 'yes' : 'no';

        $prompt = <<<PROMPT
You are an expert educational content creator. Generate {$count} assessment questions based on the following content.

Requirements:
- Question types: {$types}
- Difficulty level: {$difficulty}
- Include explanations: {$includeExplanation}
- Ensure questions test understanding, not just memorization
- Make questions clear and unambiguous
- For multiple choice questions, provide 4 options with only one correct answer
- For true/false questions, provide a clear statement
- For fill in the blank questions, indicate the blank with [BLANK]
- For essay questions, provide clear instructions and grading criteria

Return the questions as a JSON array with the following structure:
[
  {
    "question_text": "The question text",
    "question_type": "multiple_choice|true_false|fill_in_blank|essay",
    "options": ["Option 1", "Option 2", "Option 3", "Option 4"], // For multiple choice and true/false
    "correct_answer": "The correct answer or option index",
    "explanation": "Why this is the correct answer",
    "difficulty": "easy|medium|hard"
  }
]

Content to generate questions from:
{$content}

Generate exactly {$count} questions. Return ONLY the JSON array, no additional text.
PROMPT;

        return $prompt;
    }

    /**
     * Parse AI response into questions array
     */
    protected function parseAIResponse(string $response): array
    {
        try {
            // Clean response - remove markdown code blocks if present
            $response = preg_replace('/```json\s*/', '', $response);
            $response = preg_replace('/```\s*$/', '', $response);
            $response = trim($response);

            $questions = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
            }

            if (!is_array($questions)) {
                throw new \Exception('Response is not an array');
            }

            return $questions;
        } catch (\Exception $e) {
            Log::error('Failed to parse AI response', [
                'error' => $e->getMessage(),
                'response' => substr($response, 0, 500)
            ]);
            throw new AIServiceException('Failed to parse AI response: ' . $e->getMessage());
        }
    }

    /**
     * Format and validate generated questions
     */
    protected function formatQuestions(array $questions, array $parameters): array
    {
        $formatted = [];

        foreach ($questions as $index => $question) {
            try {
                $formattedQuestion = $this->formatSingleQuestion($question, $parameters);
                if ($formattedQuestion) {
                    $formatted[] = $formattedQuestion;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to format question', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                    'question' => $question
                ]);
            }
        }

        return $formatted;
    }

    /**
     * Format a single question
     */
    protected function formatSingleQuestion(array $question, array $parameters): ?array
    {
        // Validate required fields
        if (empty($question['question_text']) || empty($question['question_type'])) {
            return null;
        }

        // Validate question type
        if (!in_array($question['question_type'], $this->supportedTypes)) {
            return null;
        }

        $formatted = [
            'question_text' => trim($question['question_text']),
            'question_type' => $question['question_type'],
            'explanation' => $question['explanation'] ?? null,
            'points' => $parameters['points_per_question'],
        ];

        // Format based on question type
        switch ($question['question_type']) {
            case 'multiple_choice':
                $formatted = $this->formatMultipleChoice($question, $formatted);
                break;
            case 'true_false':
                $formatted = $this->formatTrueFalse($question, $formatted);
                break;
            case 'fill_in_blank':
                $formatted = $this->formatFillInBlank($question, $formatted);
                break;
            case 'essay':
                $formatted = $this->formatEssay($question, $formatted);
                break;
        }

        return $formatted;
    }

    /**
     * Format multiple choice question
     */
    protected function formatMultipleChoice(array $question, array $formatted): array
    {
        if (empty($question['options']) || !is_array($question['options'])) {
            throw new \Exception('Multiple choice question must have options');
        }

        if (count($question['options']) < 2) {
            throw new \Exception('Multiple choice question must have at least 2 options');
        }

        $formatted['options'] = array_values($question['options']);
        
        // Determine correct answer
        if (isset($question['correct_answer'])) {
            if (is_numeric($question['correct_answer'])) {
                // Index provided
                $formatted['correct_answer'] = [(int) $question['correct_answer']];
            } else {
                // Text provided, find index
                $index = array_search($question['correct_answer'], $formatted['options']);
                $formatted['correct_answer'] = $index !== false ? [$index] : [0];
            }
        } else {
            $formatted['correct_answer'] = [0];
        }

        return $formatted;
    }

    /**
     * Format true/false question
     */
    protected function formatTrueFalse(array $question, array $formatted): array
    {
        $formatted['options'] = ['True', 'False'];
        
        // Determine correct answer
        $correctAnswer = $question['correct_answer'] ?? 'true';
        $correctAnswer = strtolower(trim($correctAnswer));
        
        $formatted['correct_answer'] = [
            in_array($correctAnswer, ['true', '1', 'yes', 't']) ? 0 : 1
        ];

        return $formatted;
    }

    /**
     * Format fill in the blank question
     */
    protected function formatFillInBlank(array $question, array $formatted): array
    {
        $correctAnswer = $question['correct_answer'] ?? '';
        
        if (is_array($correctAnswer)) {
            $formatted['correct_answer'] = $correctAnswer;
        } else {
            $formatted['correct_answer'] = [$correctAnswer];
        }

        $formatted['options'] = null;

        return $formatted;
    }

    /**
     * Format essay question
     */
    protected function formatEssay(array $question, array $formatted): array
    {
        $formatted['options'] = null;
        $formatted['correct_answer'] = null;
        
        // Store grading criteria if provided
        if (isset($question['grading_criteria'])) {
            $formatted['grading_criteria'] = $question['grading_criteria'];
        }

        return $formatted;
    }

    /**
     * Save generated questions to database
     */
    public function saveGeneratedQuestions(AIQuestionJob $job, array $questions): int
    {
        $count = 0;

        foreach ($questions as $questionData) {
            try {
                GeneratedQuestion::create([
                    'job_id' => $job->id,
                    'question_text' => $questionData['question_text'],
                    'question_type' => $questionData['question_type'],
                    'options' => $questionData['options'],
                    'correct_answer' => $questionData['correct_answer'],
                    'explanation' => $questionData['explanation'],
                    'points' => $questionData['points'],
                    'status' => 'pending',
                ]);
                $count++;
            } catch (\Exception $e) {
                Log::error('Failed to save generated question', [
                    'job_id' => $job->id,
                    'error' => $e->getMessage(),
                    'question' => $questionData
                ]);
            }
        }

        return $count;
    }

    /**
     * Get supported question types
     */
    public function getSupportedTypes(): array
    {
        return $this->supportedTypes;
    }

    /**
     * Get difficulty levels
     */
    public function getDifficultyLevels(): array
    {
        return $this->difficultyLevels;
    }
}
