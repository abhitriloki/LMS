<?php

namespace App\Services\AI\Contracts;

interface AIServiceInterface
{
    /**
     * Generate text using AI model
     *
     * @param string $prompt
     * @param array $options
     * @return string
     */
    public function generateText(string $prompt, array $options = []): string;

    /**
     * Analyze text for specific task
     *
     * @param string $text
     * @param string $task
     * @return array
     */
    public function analyzeText(string $text, string $task): array;

    /**
     * Generate image from prompt
     *
     * @param string $prompt
     * @return string URL of generated image
     */
    public function generateImage(string $prompt): string;

    /**
     * Transcribe audio to text
     *
     * @param string $audioPath
     * @return string
     */
    public function transcribeAudio(string $audioPath): string;

    /**
     * Convert text to speech
     *
     * @param string $text
     * @return string Path to audio file
     */
    public function textToSpeech(string $text): string;

    /**
     * Check if service is available
     *
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * Get service name
     *
     * @return string
     */
    public function getName(): string;
}
