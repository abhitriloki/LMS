<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Edit Generated Question') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.questions.generator.update', $generatedQuestion) }}" 
                        x-data="questionEditor('{{ $generatedQuestion->question_type }}', {{ json_encode($generatedQuestion->options ?? []) }}, {{ json_encode($generatedQuestion->correct_answer ?? []) }})">
                        @csrf
                        @method('PUT')

                        <!-- Question Type -->
                        <div class="mb-6">
                            <label for="question_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Question Type
                            </label>
                            <select name="question_type" id="question_type" x-model="questionType" required
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="multiple_choice">Multiple Choice</option>
                                <option value="true_false">True/False</option>
                                <option value="fill_in_blank">Fill in the Blank</option>
                                <option value="essay">Essay</option>
                            </select>
                        </div>

                        <!-- Question Text -->
                        <div class="mb-6">
                            <label for="question_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Question Text
                            </label>
                            <textarea name="question_text" id="question_text" rows="3" required
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">{{ old('question_text', $generatedQuestion->question_text) }}</textarea>
                            @error('question_text')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Options (for MCQ and True/False) -->
                        <div x-show="questionType === 'multiple_choice' || questionType === 'true_false'" class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Answer Options
                            </label>
                            <div class="space-y-3">
                                <template x-for="(option, index) in options" :key="index">
                                    <div class="flex items-center space-x-2">
                                        <input type="checkbox" :name="'correct_answer[]'" :value="index" 
                                            :checked="correctAnswer.includes(index)"
                                            class="rounded border-gray-300 dark:border-gray-600 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                        <input type="text" :name="'options[]'" x-model="options[index]" required
                                            class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                        <button type="button" @click="removeOption(index)" x-show="options.length > 2"
                                            class="px-2 py-1 text-red-600 hover:text-red-700">
                                            Remove
                                        </button>
                                    </div>
                                </template>
                            </div>
                            <button type="button" @click="addOption" x-show="questionType === 'multiple_choice'"
                                class="mt-2 text-sm text-primary-600 hover:text-primary-700">
                                + Add Option
                            </button>
                        </div>

                        <!-- Correct Answer (for Fill in Blank) -->
                        <div x-show="questionType === 'fill_in_blank'" class="mb-6">
                            <label for="correct_answer_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Correct Answer
                            </label>
                            <input type="text" name="correct_answer" id="correct_answer_text"
                                :value="Array.isArray(correctAnswer) ? correctAnswer.join(', ') : correctAnswer"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                For multiple acceptable answers, separate with commas
                            </p>
                        </div>

                        <!-- Explanation -->
                        <div class="mb-6">
                            <label for="explanation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Explanation (Optional)
                            </label>
                            <textarea name="explanation" id="explanation" rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">{{ old('explanation', $generatedQuestion->explanation) }}</textarea>
                        </div>

                        <!-- Points -->
                        <div class="mb-6">
                            <label for="points" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Points
                            </label>
                            <input type="number" name="points" id="points" min="0.5" max="10" step="0.5" 
                                value="{{ old('points', $generatedQuestion->points) }}" required
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end space-x-3">
                            <a href="{{ route('admin.questions.generator.review', $generatedQuestion->job) }}" 
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                Cancel
                            </a>
                            <button type="submit"
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function questionEditor(initialType, initialOptions, initialCorrectAnswer) {
            return {
                questionType: initialType,
                options: initialOptions.length > 0 ? initialOptions : ['', ''],
                correctAnswer: Array.isArray(initialCorrectAnswer) ? initialCorrectAnswer : [],

                addOption() {
                    this.options.push('');
                },

                removeOption(index) {
                    this.options.splice(index, 1);
                    // Update correct answer indices
                    this.correctAnswer = this.correctAnswer.filter(i => i !== index).map(i => i > index ? i - 1 : i);
                }
            }
        }
    </script>
</x-app-layout>
