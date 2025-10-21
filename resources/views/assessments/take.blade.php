<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $attempt->assessment->title }}
            </h2>
            @if($remainingTime !== null)
                <div id="timer" class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Time Remaining: <span id="timer-display">{{ gmdate('H:i:s', $remainingTime) }}</span>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-12" x-data="assessmentTaker()">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Progress Bar -->
                    <div class="mb-6">
                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                            <span>Question <span x-text="currentQuestion + 1"></span> of {{ $attempt->questions->count() }}</span>
                            <span x-text="Math.round(((currentQuestion + 1) / {{ $attempt->questions->count() }}) * 100) + '%'"></span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                                :style="'width: ' + (((currentQuestion + 1) / {{ $attempt->questions->count() }}) * 100) + '%'"></div>
                        </div>
                    </div>

                    <!-- Question Display -->
                    @foreach($attempt->questions as $index => $question)
                        <div x-show="currentQuestion === {{ $index }}" x-cloak>
                            <div class="mb-6">
                                <div class="flex items-center space-x-2 mb-3">
                                    <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-sm font-medium">
                                        {{ ucfirst(str_replace('_', ' ', $question->question_type)) }}
                                    </span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $question->points }} {{ $question->points == 1 ? 'point' : 'points' }}
                                    </span>
                                </div>
                                
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    {{ $question->question_text }}
                                </h3>

                                <!-- Multiple Choice / True False -->
                                @if($question->isMultipleChoice() || $question->isTrueFalse())
                                    <div class="space-y-3">
                                        @foreach($question->options as $option)
                                            <label class="flex items-start p-4 border border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                                <input type="radio" 
                                                    name="question_{{ $question->id }}" 
                                                    value="{{ $option->id }}"
                                                    x-model="responses[{{ $question->id }}]"
                                                    @change="saveResponse({{ $question->id }})"
                                                    class="mt-1 text-blue-600 focus:ring-blue-500">
                                                <span class="ml-3 text-gray-900 dark:text-gray-100">{{ $option->option_text }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @endif

                                <!-- Essay -->
                                @if($question->isEssay())
                                    <textarea 
                                        x-model="responses[{{ $question->id }}]"
                                        @blur="saveResponse({{ $question->id }})"
                                        rows="10"
                                        placeholder="Type your answer here..."
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"></textarea>
                                @endif

                                <!-- Fill in the Blank -->
                                @if($question->isFillInBlank())
                                    <input type="text" 
                                        x-model="responses[{{ $question->id }}]"
                                        @blur="saveResponse({{ $question->id }})"
                                        placeholder="Type your answer here..."
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                @endif
                            </div>

                            <!-- Navigation -->
                            <div class="flex justify-between items-center pt-6 border-t border-gray-200 dark:border-gray-700">
                                <button type="button" 
                                    @click="previousQuestion()"
                                    x-show="currentQuestion > 0"
                                    class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
                                    ← Previous
                                </button>
                                
                                <div class="flex space-x-3">
                                    <button type="button" 
                                        @click="nextQuestion()"
                                        x-show="currentQuestion < {{ $attempt->questions->count() - 1 }}"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                                        Next →
                                    </button>
                                    
                                    <button type="button" 
                                        @click="submitAssessment()"
                                        x-show="currentQuestion === {{ $attempt->questions->count() - 1 }}"
                                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                                        Submit Assessment
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <!-- Question Navigator -->
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Question Navigator</h4>
                        <div class="grid grid-cols-10 gap-2">
                            @foreach($attempt->questions as $index => $question)
                                <button type="button" 
                                    @click="currentQuestion = {{ $index }}"
                                    :class="currentQuestion === {{ $index }} ? 'bg-blue-600 text-white' : (responses[{{ $question->id }}] ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300')"
                                    class="w-10 h-10 rounded-lg font-medium hover:opacity-80">
                                    {{ $index + 1 }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function assessmentTaker() {
            return {
                currentQuestion: 0,
                responses: @json($attempt->responses->pluck('response_data', 'question_id')),
                
                nextQuestion() {
                    if (this.currentQuestion < {{ $attempt->questions->count() - 1 }}) {
                        this.currentQuestion++;
                    }
                },
                
                previousQuestion() {
                    if (this.currentQuestion > 0) {
                        this.currentQuestion--;
                    }
                },
                
                saveResponse(questionId) {
                    fetch(`/attempts/{{ $attempt->id }}/questions/${questionId}/response`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            response: this.responses[questionId]
                        })
                    });
                },
                
                submitAssessment() {
                    if (confirm('Are you sure you want to submit your assessment? You cannot change your answers after submission.')) {
                        document.getElementById('submit-form').submit();
                    }
                }
            }
        }

        @if($remainingTime !== null)
        // Timer countdown
        let remainingSeconds = {{ $remainingTime }};
        const timerDisplay = document.getElementById('timer-display');
        
        const timerInterval = setInterval(() => {
            remainingSeconds--;
            
            if (remainingSeconds <= 0) {
                clearInterval(timerInterval);
                alert('Time is up! Your assessment will be submitted automatically.');
                document.getElementById('submit-form').submit();
            } else {
                const hours = Math.floor(remainingSeconds / 3600);
                const minutes = Math.floor((remainingSeconds % 3600) / 60);
                const seconds = remainingSeconds % 60;
                timerDisplay.textContent = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                
                // Warning at 5 minutes
                if (remainingSeconds === 300) {
                    alert('5 minutes remaining!');
                }
            }
        }, 1000);
        @endif
    </script>

    <form id="submit-form" method="POST" action="{{ route('attempts.submit', $attempt) }}" style="display: none;">
        @csrf
    </form>
</x-app-layout>
