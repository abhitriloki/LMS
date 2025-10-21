@props(['position' => 'bottom-right'])

<div x-data="chatbotWidget()" 
     x-init="init()"
     class="fixed z-50"
     :class="{
         'bottom-4 right-4': '{{ $position }}' === 'bottom-right',
         'bottom-4 left-4': '{{ $position }}' === 'bottom-left',
     }">
    
    <!-- Chat Toggle Button -->
    <button @click="toggleChat()"
            x-show="!isOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-75"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-75"
            class="bg-primary-600 hover:bg-primary-700 text-white rounded-full p-4 shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center group">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
        </svg>
        <span class="ml-2 text-sm font-medium hidden group-hover:inline-block">Chat with AI Assistant</span>
    </button>

    <!-- Chat Window -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 scale-95"
         class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl w-96 h-[600px] flex flex-col overflow-hidden">
        
        <!-- Chat Header -->
        <div class="bg-primary-600 text-white p-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold">AI Learning Assistant</h3>
                    <p class="text-xs text-white/80" x-show="!isTyping">Online</p>
                    <p class="text-xs text-white/80" x-show="isTyping">Typing...</p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <!-- Minimize Button -->
                <button @click="toggleChat()" 
                        class="hover:bg-white/20 rounded p-1 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <!-- New Conversation Button -->
                <button @click="startNewConversation()" 
                        class="hover:bg-white/20 rounded p-1 transition-colors"
                        title="Start new conversation">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Messages Container -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50 dark:bg-gray-900" 
             x-ref="messagesContainer"
             @scroll="handleScroll()">
            
            <!-- Welcome Message -->
            <template x-if="messages.length === 0">
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                        </svg>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Welcome to AI Assistant</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">I can help you with:</p>
                    <div class="space-y-2 text-left max-w-xs mx-auto">
                        <div class="flex items-start space-x-2 text-sm text-gray-700 dark:text-gray-300">
                            <svg class="w-5 h-5 text-primary-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Course recommendations</span>
                        </div>
                        <div class="flex items-start space-x-2 text-sm text-gray-700 dark:text-gray-300">
                            <svg class="w-5 h-5 text-primary-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Progress tracking</span>
                        </div>
                        <div class="flex items-start space-x-2 text-sm text-gray-700 dark:text-gray-300">
                            <svg class="w-5 h-5 text-primary-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Enrollment assistance</span>
                        </div>
                        <div class="flex items-start space-x-2 text-sm text-gray-700 dark:text-gray-300">
                            <svg class="w-5 h-5 text-primary-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Course search</span>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Messages -->
            <template x-for="(message, index) in messages" :key="message.id || index">
                <div :class="message.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div :class="message.role === 'user' 
                        ? 'bg-primary-600 text-white rounded-lg rounded-br-none px-4 py-2 max-w-[80%]' 
                        : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-lg rounded-bl-none px-4 py-2 max-w-[80%] shadow'">
                        <p class="text-sm whitespace-pre-wrap" x-html="formatMessage(message.content)"></p>
                        <p class="text-xs mt-1 opacity-70" x-text="formatTime(message.created_at)"></p>
                    </div>
                </div>
            </template>

            <!-- Typing Indicator -->
            <template x-if="isTyping">
                <div class="flex justify-start">
                    <div class="bg-white dark:bg-gray-800 rounded-lg rounded-bl-none px-4 py-3 shadow">
                        <div class="flex space-x-2">
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Error Message -->
            <template x-if="error">
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <div>
                            <p class="text-sm text-red-800 dark:text-red-200" x-text="error"></p>
                            <button @click="error = null" class="text-xs text-red-600 dark:text-red-400 hover:underline mt-1">Dismiss</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Input Area -->
        <div class="border-t border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
            <form @submit.prevent="sendMessage()" class="flex items-end space-x-2">
                <div class="flex-1">
                    <textarea x-model="inputMessage"
                              x-ref="messageInput"
                              @keydown.enter.prevent="if (!$event.shiftKey) sendMessage()"
                              placeholder="Type your message..."
                              rows="1"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                              :disabled="isTyping || isSending"
                              style="max-height: 120px; overflow-y: auto;"></textarea>
                </div>
                <button type="submit"
                        :disabled="!inputMessage.trim() || isTyping || isSending"
                        class="bg-primary-600 hover:bg-primary-700 disabled:bg-gray-300 dark:disabled:bg-gray-600 disabled:cursor-not-allowed text-white rounded-lg p-2 transition-colors">
                    <svg x-show="!isSending" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                    <svg x-show="isSending" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </form>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Press Enter to send, Shift+Enter for new line</p>
        </div>
    </div>
</div>

<script>
function chatbotWidget() {
    return {
        isOpen: false,
        messages: [],
        inputMessage: '',
        isTyping: false,
        isSending: false,
        error: null,
        conversationId: null,
        autoScroll: true,

        init() {
            // Load conversation from localStorage if exists
            const savedConversation = localStorage.getItem('chatbot_conversation_id');
            if (savedConversation) {
                this.conversationId = savedConversation;
                this.loadConversation();
            }
        },

        toggleChat() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.$nextTick(() => {
                    this.$refs.messageInput?.focus();
                    this.scrollToBottom();
                });
            }
        },

        async sendMessage() {
            if (!this.inputMessage.trim() || this.isSending) return;

            const message = this.inputMessage.trim();
            this.inputMessage = '';
            this.error = null;

            // Add user message to UI immediately
            this.messages.push({
                id: Date.now(),
                role: 'user',
                content: message,
                created_at: new Date().toISOString(),
            });

            this.scrollToBottom();
            this.isSending = true;
            this.isTyping = true;

            try {
                const response = await fetch('{{ route('chatbot.send-message') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        message: message,
                        conversation_id: this.conversationId,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    // Save conversation ID
                    if (data.conversation_id) {
                        this.conversationId = data.conversation_id;
                        localStorage.setItem('chatbot_conversation_id', data.conversation_id);
                    }

                    // Add bot response
                    this.messages.push({
                        id: data.message.id,
                        role: data.message.role,
                        content: data.message.content,
                        created_at: data.message.created_at,
                    });

                    this.scrollToBottom();
                } else {
                    this.error = data.error || 'Failed to send message';
                }
            } catch (error) {
                console.error('Chatbot error:', error);
                this.error = 'Network error. Please check your connection and try again.';
            } finally {
                this.isSending = false;
                this.isTyping = false;
                this.$nextTick(() => {
                    this.$refs.messageInput?.focus();
                });
            }
        },

        async loadConversation() {
            if (!this.conversationId) return;

            try {
                const response = await fetch(`{{ url('/chatbot/conversations') }}/${this.conversationId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                const data = await response.json();

                if (data.success && data.messages) {
                    this.messages = data.messages;
                    this.$nextTick(() => this.scrollToBottom());
                }
            } catch (error) {
                console.error('Failed to load conversation:', error);
            }
        },

        async startNewConversation() {
            if (confirm('Start a new conversation? Your current conversation will be saved.')) {
                this.conversationId = null;
                this.messages = [];
                localStorage.removeItem('chatbot_conversation_id');
                this.error = null;
                
                this.$nextTick(() => {
                    this.$refs.messageInput?.focus();
                });
            }
        },

        scrollToBottom() {
            if (this.autoScroll) {
                this.$nextTick(() => {
                    const container = this.$refs.messagesContainer;
                    if (container) {
                        container.scrollTop = container.scrollHeight;
                    }
                });
            }
        },

        handleScroll() {
            const container = this.$refs.messagesContainer;
            if (container) {
                const isAtBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 50;
                this.autoScroll = isAtBottom;
            }
        },

        formatMessage(content) {
            // Convert markdown-style bold to HTML
            let formatted = content.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
            
            // Convert bullet points to proper list items
            formatted = formatted.replace(/^• (.+)$/gm, '<li class="ml-4">$1</li>');
            
            // Convert newlines to <br> tags
            formatted = formatted.replace(/\n/g, '<br>');
            
            return formatted;
        },

        formatTime(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const diff = now - date;
            
            // Less than 1 minute
            if (diff < 60000) {
                return 'Just now';
            }
            
            // Less than 1 hour
            if (diff < 3600000) {
                const minutes = Math.floor(diff / 60000);
                return `${minutes} ${minutes === 1 ? 'minute' : 'minutes'} ago`;
            }
            
            // Less than 24 hours
            if (diff < 86400000) {
                const hours = Math.floor(diff / 3600000);
                return `${hours} ${hours === 1 ? 'hour' : 'hours'} ago`;
            }
            
            // Show time
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        },
    };
}
</script>
