<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Review Generated Questions') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12" x-data="questionReview()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Lesson Info -->
            <div class="mb-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100 mb-2">
                        {{ $job->lesson->title }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Course: {{ $job->lesson->module->course->title }} | 
                        {{ $questions->count() }} questions pending review
                    </p>
                </div>
            </div>

            <!-- Assessment Selection -->
            <div class="mb-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <label for="assessment_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Assessment to Add Approved Questions
                    </label>
                    <select x-model="selectedAssessment" id="assessment_id"
                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Choose an assessment...</option>
                        @foreach($job->lesson->module->course->assessments as $assessment)
                            <option value="{{ $assessment->id }}">{{ $assessment->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div class="mb-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <label class="inline-flex items-center">
                            <input type="checkbox" @change="toggleSelectAll" :checked="allSelected"
                                class="rounded border-gray-300 dark:border-gray-600 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Select All</span>
                        </label>
                        <span class="text-sm text-gray-600 dark:text-gray-400" x-show="selectedQuestions.length > 0">
                            <span x-text="selectedQuestions.length"></span> selected
                        </span>
                    </div>
                    <button @click="bulkApprove" x-show="selectedQuestions.length > 0 && selectedAssessment"
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm font-medium">
                        Approve Selected
                    </button>
                </div>
            </div>

            <!-- Questions List -->
            <div class="space-y-4">
                @forelse($questions as $question)
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-start">
                                <input type="checkbox" :value="{{ $question->id }}" x-model="selectedQuestions"
                                    class="mt-1 rounded border-gray-300 dark:border-gray-600 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                
                                <div class="ml-4 flex-1">
                                    <!-- Question Header -->
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex-1">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                {{ ucwords(str_replace('_', ' ', $question->question_type)) }}
                                            </span>
                                            <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">
                                                {{ $question->points }} points
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Question Text -->
                                    <div class="mb-4">
                                        <p class="text-base font-medium text-gray-900 dark:text-gray-100">
                                            {{ $question->question_text }}
                                        </p>
                                    </div>

                                    <!-- Options (for MCQ and True/False) -->
                                    @if($question->options && is_array($question->options))
                                        <div class="mb-4 space-y-2">
                                            @foreach($question->options as $index => $option)
                                                <div class="flex items-center p-2 rounded {{ in_array($index, $question->correct_answer ?? []) ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-gray-50 dark:bg-gray-700' }}">
                                                    @if(in_array($index, $question->correct_answer ?? []))
                                                        <svg class="h-5 w-5 text-green-600 dark:text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                        </svg>
                                                    @else
                                                        <span class="h-5 w-5 mr-2"></span>
                                                    @endif
                                                    <span class="text-sm {{ in_array($index, $question->correct_answer ?? []) ? 'text-green-900 dark:text-green-100 font-medium' : 'text-gray-700 dark:text-gray-300' }}">
                                                        {{ $option }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    <!-- Correct Answer (for Fill in Blank) -->
                                    @if($question->question_type === 'fill_in_blank' && $question->correct_answer)
                                        <div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded">
                                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                                <span class="font-medium">Correct Answer:</span>
                                                {{ is_array($question->correct_answer) ? implode(', ', $question->correct_answer) : $question->correct_answer }}
                                            </p>
                                        </div>
                                    @endif

                                    <!-- Explanation -->
                                    @if($question->explanation)
                                        <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded">
                                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                                <span class="font-medium">Explanation:</span>
                                                {{ $question->explanation }}
                                            </p>
                                        </div>
                                    @endif

                                    <!-- Actions -->
                                    <div class="flex items-center space-x-3">
                                        <button @click="approveQuestion({{ $question->id }})" :disabled="!selectedAssessment"
                                            class="px-3 py-1.5 bg-green-600 text-white rounded text-sm font-medium hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                            Approve
                                        </button>
                                        <a href="{{ route('admin.questions.generator.edit', $question) }}"
                                            class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm font-medium hover:bg-blue-700">
                                            Edit
                                        </a>
                                        <button @click="rejectQuestion({{ $question->id }})"
                                            class="px-3 py-1.5 bg-red-600 text-white rounded text-sm font-medium hover:bg-red-700">
                                            Reject
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-center">
                            <p class="text-gray-500 dark:text-gray-400">No questions pending review.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <script>
        function questionReview() {
            return {
                selectedQuestions: [],
                selectedAssessment: '',
                allSelected: false,

                toggleSelectAll() {
                    if (this.allSelected) {
                        this.selectedQuestions = [];
                    } else {
                        this.selectedQuestions = @json($questions->pluck('id'));
                    }
                    this.allSelected = !this.allSelected;
                },

                async approveQuestion(questionId) {
                    if (!this.selectedAssessment) {
                        alert('Please select an assessment first');
                        return;
                    }

                    if (!confirm('Approve this question and add it to the assessment?')) {
                        return;
                    }

                    try {
                        const response = await fetch(`/admin/questions/generator/${questionId}/approve`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                assessment_id: this.selectedAssessment
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            location.reload();
                        } else {
                            alert(data.message || 'Failed to approve question');
                        }
                    } catch (error) {
                        alert('An error occurred');
                        console.error(error);
                    }
                },

                async rejectQuestion(questionId) {
                    const notes = prompt('Reason for rejection (optional):');
                    if (notes === null) return;

                    try {
                        const response = await fetch(`/admin/questions/generator/${questionId}/reject`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                review_notes: notes
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            location.reload();
                        } else {
                            alert(data.message || 'Failed to reject question');
                        }
                    } catch (error) {
                        alert('An error occurred');
                        console.error(error);
                    }
                },

                async bulkApprove() {
                    if (!this.selectedAssessment) {
                        alert('Please select an assessment first');
                        return;
                    }

                    if (!confirm(`Approve ${this.selectedQuestions.length} questions and add them to the assessment?`)) {
                        return;
                    }

                    try {
                        const response = await fetch('/admin/questions/generator/bulk-approve', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                question_ids: this.selectedQuestions,
                                assessment_id: this.selectedAssessment
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            alert(data.message);
                            location.reload();
                        } else {
                            alert(data.message || 'Failed to approve questions');
                        }
                    } catch (error) {
                        alert('An error occurred');
                        console.error(error);
                    }
                }
            }
        }
    </script>
</x-app-layout>
