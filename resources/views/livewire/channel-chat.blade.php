<div
    x-data="{
        typingUsers: {},
        channel: null,
        currentUserId: {{ auth()->id() }},
        currentUserName: '{{ auth()->user()->name }}',
        channelId: {{ $channelId }},
        
        init() {
            this.scrollToBottom();
            this.setupEcho();
        },
        
        setupEcho() {
            const component = this;
            // Join presence channel (allows whispers for typing indicators)
            this.channel = Echo.join('channel.' + this.channelId)
                .listen('.message.sent', (e) => {
                    // Dispatch event to trigger Livewire update
                    window.dispatchEvent(new CustomEvent('echo-message-received', { detail: e }));
                })
                .listenForWhisper('typing', (e) => {
                    component.userStartedTyping(e.userId, e.name);
                });
        },
        
        scrollToBottom() {
            const container = this.$refs.messagesContainer;
            if (container) {
                this.$nextTick(() => {
                    container.scrollTop = container.scrollHeight;
                });
            }
        },
        
        get typingNames() {
            return Object.values(this.typingUsers).filter(name => name).join(', ');
        },
        
        get isAnyoneTyping() {
            return Object.keys(this.typingUsers).length > 0;
        },
        
        handleTyping() {
            if (this.channel) {
                this.channel.whisper('typing', {
                    userId: this.currentUserId,
                    name: this.currentUserName
                });
            }
        },
        
        userStartedTyping(userId, name) {
            if (userId !== this.currentUserId) {
                this.typingUsers[userId] = name;
                
                // Clear existing timeout for this user
                if (this['typingTimeout_' + userId]) {
                    clearTimeout(this['typingTimeout_' + userId]);
                }
                
                // Remove after 2 seconds of no typing
                this['typingTimeout_' + userId] = setTimeout(() => {
                    delete this.typingUsers[userId];
                }, 2000);
            }
        }
    }"
    x-on:message-sent.window="scrollToBottom(); typingUsers = {}"
    x-on:message-received.window="scrollToBottom()"
    x-on:echo-message-received.window="$wire.messageReceived($event.detail)"
>

    <div 
        x-ref="messagesContainer"
        class="space-y-4 mb-6 max-h-[60vh] overflow-y-auto scroll-smooth"
    >
        @foreach($chatMessages as $message)
            <div class="p-3 bg-white rounded shadow-sm">
                <div class="flex items-center space-x-2 mb-1">
                    <span class="font-semibold text-gray-900">{{ $message->user->name ?? 'Unknown' }}</span>
                    <span class="text-xs text-gray-400">{{ $message->created_at?->diffForHumans() }}</span>
                </div>
                <div class="text-gray-800 whitespace-pre-line">{{ $message->body }}</div>
            </div>
        @endforeach
    </div>

    {{-- Typing indicator --}}
    <div 
        x-show="isAnyoneTyping" 
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform -translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="text-sm text-gray-500 italic mb-2 flex items-center gap-2"
    >
        <span class="flex gap-1">
            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
        </span>
        <span x-text="typingNames + ' is typing...'"></span>
    </div>

    <form wire:submit="sendMessage" class="bg-white p-4 rounded shadow-sm">
        <div>
            <textarea 
                wire:model="body"
                x-on:input.debounce.300ms="handleTyping()"
                class="w-full border-gray-300 rounded-lg p-2 focus:ring focus:ring-indigo-200 min-h-[48px] resize-y"
                placeholder="Write a message..."
                rows="2"
            ></textarea>
            @error('body')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div class="mt-2 flex justify-end">
            <button 
                type="submit"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition"
            >
                Send
            </button>
        </div>
    </form>

</div>
