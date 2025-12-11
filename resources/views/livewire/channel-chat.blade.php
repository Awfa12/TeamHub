<div
    x-data="{
        typingUsers: {},
        onlineUsers: [],
        channel: null,
        currentUserId: {{ auth()->id() }},
        currentUserName: '{{ auth()->user()->name }}',
        channelId: {{ $channelId }},
        
        // Delete confirmation modal
        showDeleteModal: false,
        deleteMessageId: null,
        
        init() {
            this.scrollToBottom();
            this.setupEcho();
        },
        
        setupEcho() {
            const component = this;
            // Join presence channel (allows whispers for typing indicators + online presence)
            this.channel = Echo.join('channel.' + this.channelId)
                .here((users) => {
                    // Called when first joining - get all current users
                    component.onlineUsers = users;
                })
                .joining((user) => {
                    // Called when a new user joins
                    component.onlineUsers.push(user);
                })
                .leaving((user) => {
                    // Called when a user leaves
                    component.onlineUsers = component.onlineUsers.filter(u => u.id !== user.id);
                })
                .listen('.message.sent', (e) => {
                    // Dispatch event to trigger Livewire update
                    window.dispatchEvent(new CustomEvent('echo-message-received', { detail: e }));
                })
                .listen('.message.updated', (e) => {
                    // Dispatch event for message update
                    window.dispatchEvent(new CustomEvent('echo-message-updated', { detail: e }));
                })
                .listen('.message.deleted', (e) => {
                    // Dispatch event for message deletion
                    window.dispatchEvent(new CustomEvent('echo-message-deleted', { detail: e }));
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
        
        get onlineCount() {
            return this.onlineUsers.length;
        },
        
        get otherOnlineUsers() {
            return this.onlineUsers.filter(u => u.id !== this.currentUserId);
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
        },
        
        confirmDelete(messageId) {
            this.deleteMessageId = messageId;
            this.showDeleteModal = true;
        },
        
        cancelDelete() {
            this.showDeleteModal = false;
            this.deleteMessageId = null;
        },
        
        executeDelete() {
            if (this.deleteMessageId) {
                $wire.deleteMessage(this.deleteMessageId);
            }
            this.cancelDelete();
        }
    }"
    x-on:message-sent.window="scrollToBottom(); typingUsers = {}"
    x-on:message-received.window="scrollToBottom()"
    x-on:echo-message-received.window="$wire.messageReceived($event.detail)"
    x-on:echo-message-updated.window="$wire.messageUpdatedReceived($event.detail)"
    x-on:echo-message-deleted.window="$wire.messageDeletedReceived($event.detail)"
>

    {{-- Online users indicator --}}
    <div class="flex items-center justify-between mb-4 p-3 bg-white rounded shadow-sm">
        <div class="flex items-center gap-2">
            <span class="flex items-center gap-1">
                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                <span class="text-sm text-gray-600" x-text="onlineCount + ' online'"></span>
            </span>
        </div>
        <div class="flex items-center gap-2" x-show="otherOnlineUsers.length > 0">
            <template x-for="user in otherOnlineUsers.slice(0, 5)" :key="user.id">
                <div 
                    class="w-8 h-8 bg-indigo-500 rounded-full flex items-center justify-center text-white text-xs font-semibold"
                    :title="user.name"
                    x-text="user.name.charAt(0).toUpperCase()"
                ></div>
            </template>
            <span 
                x-show="otherOnlineUsers.length > 5" 
                class="text-sm text-gray-500"
                x-text="'+' + (otherOnlineUsers.length - 5) + ' more'"
            ></span>
        </div>
    </div>

    <div 
        x-ref="messagesContainer"
        class="space-y-4 mb-6 max-h-[60vh] overflow-y-auto scroll-smooth"
    >
        @foreach($chatMessages as $message)
            <div class="p-3 {{ $message->deleted_at ? 'bg-gray-100' : 'bg-white' }} rounded shadow-sm group" wire:key="message-{{ $message->id }}">
                @if($message->deleted_at)
                    {{-- Deleted message placeholder --}}
                    <div class="text-gray-400 italic text-sm flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                        This message was deleted
                    </div>
                @else
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center space-x-2">
                            <span class="font-semibold text-gray-900">{{ $message->user->name ?? 'Unknown' }}</span>
                            <span class="text-xs text-gray-400">{{ $message->created_at?->diffForHumans() }}</span>
                            @if($message->edited_at)
                                <span class="text-xs text-gray-400 italic">(edited)</span>
                            @endif
                        </div>
                        @if($message->user_id === auth()->id() && $editingMessageId !== $message->id)
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                {{-- Edit button --}}
                                <button 
                                    wire:click="startEditing({{ $message->id }})"
                                    class="text-gray-400 hover:text-indigo-600 p-1"
                                    title="Edit"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                {{-- Delete button --}}
                                <button 
                                    @click="confirmDelete({{ $message->id }})"
                                    class="text-gray-400 hover:text-red-600 p-1"
                                    title="Delete"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        @endif
                    </div>
                    
                    @if($editingMessageId === $message->id)
                        {{-- Edit mode --}}
                        <form wire:submit="updateMessage" class="mt-2">
                            <textarea 
                                wire:model="editBody"
                                class="w-full border-gray-300 rounded-lg p-2 focus:ring focus:ring-indigo-200 text-sm resize-y"
                                rows="2"
                                autofocus
                            ></textarea>
                            @error('editBody')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            <div class="flex gap-2 mt-2">
                                <button 
                                    type="submit"
                                    class="px-3 py-1 bg-indigo-600 text-white text-xs rounded hover:bg-indigo-700 transition"
                                >
                                    Save
                                </button>
                                <button 
                                    type="button"
                                    wire:click="cancelEditing"
                                    class="px-3 py-1 bg-gray-200 text-gray-700 text-xs rounded hover:bg-gray-300 transition"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>
                    @else
                        {{-- Display mode --}}
                        @if($message->body)
                            <div class="text-gray-800 whitespace-pre-line">{{ $message->body }}</div>
                        @endif
                        
                        {{-- File attachment --}}
                        @if($message->file_path)
                            <div class="mt-2">
                                @if($message->is_image)
                                    {{-- Image preview --}}
                                    <a href="{{ $message->file_url }}" target="_blank" class="block">
                                        <img 
                                            src="{{ $message->file_url }}" 
                                            alt="{{ $message->file_name }}"
                                            class="max-w-xs max-h-64 rounded-lg shadow-sm hover:shadow-md transition cursor-pointer"
                                        >
                                    </a>
                                    <div class="flex items-center gap-2 mt-1">
                                        <p class="text-xs text-gray-400">{{ $message->file_name }} ({{ $message->formatted_file_size }})</p>
                                        <a 
                                            href="{{ $message->download_url }}" 
                                            class="text-xs text-indigo-500 hover:text-indigo-700 flex items-center gap-1"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            Download
                                        </a>
                                    </div>
                                @else
                                    {{-- File download link --}}
                                    <a 
                                        href="{{ $message->download_url }}" 
                                        class="inline-flex items-center gap-2 px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        <div>
                                            <p class="text-sm font-medium text-gray-700">{{ $message->file_name }}</p>
                                            <p class="text-xs text-gray-400">{{ $message->formatted_file_size }} • Click to download</p>
                                        </div>
                                    </a>
                                @endif
                            </div>
                        @endif
                    @endif
                @endif
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

    <form wire:submit="sendMessage" class="bg-white p-4 rounded shadow-sm" x-data="{ fileName: null }">
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
        
        {{-- File upload section --}}
        <div class="mt-3">
            {{-- File preview --}}
            @if($file)
                <div class="flex items-center gap-2 p-2 bg-indigo-50 rounded-lg mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                    <span class="text-sm text-indigo-700 flex-1 truncate">{{ $file->getClientOriginalName() }}</span>
                    <button 
                        type="button" 
                        wire:click="$set('file', null)"
                        class="text-indigo-400 hover:text-red-500 transition"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            @endif
            
            @error('file')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
        
        <div class="mt-2 flex justify-between items-center">
            {{-- File upload button --}}
            <label class="cursor-pointer inline-flex items-center gap-2 text-gray-500 hover:text-indigo-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                </svg>
                <span class="text-sm">Attach file</span>
                <input 
                    type="file" 
                    wire:model="file"
                    class="hidden"
                    accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip"
                >
            </label>
            
            {{-- Send button --}}
            <button 
                type="submit"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-50 cursor-not-allowed"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition"
            >
                <span wire:loading.remove wire:target="sendMessage">Send</span>
                <span wire:loading wire:target="sendMessage">Sending...</span>
            </button>
        </div>
        
        {{-- Upload progress --}}
        <div wire:loading wire:target="file" class="mt-2">
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Uploading file...
            </div>
        </div>
    </form>

    {{-- Delete Confirmation Modal --}}
    <div 
        x-show="showDeleteModal"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center"
        style="display: none;"
    >
        {{-- Backdrop --}}
        <div 
            class="absolute inset-0 bg-black/50 backdrop-blur-sm"
            @click="cancelDelete()"
        ></div>
        
        {{-- Modal --}}
        <div 
            x-show="showDeleteModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative bg-white rounded-xl shadow-2xl p-6 max-w-sm w-full mx-4"
        >
            {{-- Icon --}}
            <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 rounded-full bg-red-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            
            {{-- Title --}}
            <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">
                Delete Message
            </h3>
            
            {{-- Description --}}
            <p class="text-gray-500 text-center text-sm mb-6">
                Are you sure you want to delete this message? This action cannot be undone.
            </p>
            
            {{-- Buttons --}}
            <div class="flex gap-3">
                <button 
                    @click="cancelDelete()"
                    class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition"
                >
                    Cancel
                </button>
                <button 
                    @click="executeDelete()"
                    class="flex-1 px-4 py-2.5 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition"
                >
                    Delete
                </button>
            </div>
        </div>
    </div>

</div>
