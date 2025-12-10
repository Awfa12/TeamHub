<div
    x-data="{
        scrollToBottom() {
            const container = this.$refs.messagesContainer;
            if (container) {
                this.$nextTick(() => {
                    container.scrollTop = container.scrollHeight;
                });
            }
        }
    }"
    x-on:message-sent.window="scrollToBottom()"
    x-on:message-received.window="scrollToBottom()"
    x-init="scrollToBottom()"
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

    <form wire:submit="sendMessage" class="bg-white p-4 rounded shadow-sm">
        <div>
            <textarea 
                wire:model="body"
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

@script
<script>
    Echo.private('channel.{{ $channelId }}')
        .listen('.message.sent', (e) => {
            $wire.messageReceived(e);
        });
</script>
@endscript
