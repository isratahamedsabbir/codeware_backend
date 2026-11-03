<div class="flex flex-col h-full">
    <div class="mb-3 shrink-0 flex items-center justify-between gap-4 flex-wrap">
        <div>
            @include('partials._admin-breadcrumbs', ['routeName' => 'admin.chat'])
        </div>
        @can('access-admin-system')
            <flux:button size="sm" variant="outline" icon="bolt" wire:click="openReverbSettings">
                Reverb Settings
            </flux:button>
        @endcan
    </div>

    <div
        class="flex-1 min-h-0 flex flex-col md:flex-row gap-4"
        x-data="{
        authId: {{ auth()->id() }},
        activeId: @entangle('conversationId'),
        channel: null,
        panelHeight: null,
        init() {
            // The admin layout is a normal scrolling page (no fixed-height app
            // shell), so flex-1/h-full never actually bound this panel — it just
            // kept growing taller with every message instead of scrolling within
            // a fixed height. Pin it to the remaining viewport height instead.
            //
            // This is stored as reactive Alpine state bound via :style below,
            // rather than set directly on $el.style — a plain imperative style
            // mutation gets wiped the next time Livewire morphs this element
            // (its freshly server-rendered HTML never had that attribute), so
            // the box would silently revert to unbounded on every re-render
            // (new message, markConversationRead, etc). Alpine's own bindings
            // are reapplied after each morph, so this survives it.
            //
            // Done before the Echo subscription below so a broken/unavailable
            // Reverb connection can never stop the layout fix from applying.
            this.syncHeight();
            window.addEventListener('resize', () => this.syncHeight());

            try {
                this.channel = window.Echo.private('App.Models.User.' + this.authId)
                    .listen('.message.sent', (event) => this.handleIncoming(event));
            } catch (e) {
                console.error('Chat: failed to subscribe to the private channel', e);
            }
        },
        syncHeight() {
            const top = this.$el.getBoundingClientRect().top;
            this.panelHeight = `calc(100dvh - ${top}px - 1rem)`;
        },
        handleIncoming(event) {
            if (String(event.conversation_id) === String(this.activeId)) {
                this.$wire.call('markConversationRead', event.conversation_id);
            } else {
                this.$wire.$refresh();

                if (window.toastr) {
                    toastr.info(event.body, event.sender_name);
                }
            }
        },
    }"
    :style="panelHeight ? `height: ${panelHeight}; max-height: ${panelHeight}` : ''"
>
    {{-- Conversation list --}}
    <div class="w-full md:w-[340px] shrink-0 min-h-0 bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-700 shadow-sm flex-col overflow-hidden {{ $this->activeConversation ? 'hidden md:flex' : 'flex' }}">
        <div class="px-4 pt-4 pb-3 border-b border-zinc-100 dark:border-zinc-800">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Inbox') }}</h2>
                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ trans_choice(':count conversation|:count conversations', $this->conversations->count(), ['count' => $this->conversations->count()]) }}</p>
                </div>
                <span class="flex size-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">
                    <flux:icon.chat-bubble-left-right class="size-4" />
                </span>
            </div>
            <flux:input
                wire:model.live.debounce.300ms="userSearch"
                placeholder="Start a chat — search by name or email"
                icon="magnifying-glass"
            />

            @if ($this->searchResults->isNotEmpty())
                <div class="absolute left-3 right-3 top-full mt-2 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-xl z-20 max-h-72 overflow-y-auto">
                    @foreach ($this->searchResults as $user)
                        <button
                            type="button"
                            wire:click="startConversationWith({{ $user->id }})"
                            wire:key="search-result-{{ $user->id }}"
                            class="w-full flex items-center gap-3 px-3 py-2.5 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-start transition-colors"
                        >
                            <flux:avatar size="sm" :name="$user->name" :initials="$user->initials()" />
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-zinc-800 dark:text-zinc-100 truncate">{{ $user->name }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ $user->email }}</div>
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="flex-1 min-h-0 overflow-y-auto chat-scroll">
            @forelse ($this->conversations as $conversation)
                @php $other = $conversation->otherUser(auth()->user()); @endphp
                <button
                    type="button"
                    wire:click="openConversation({{ $conversation->id }})"
                    wire:key="conversation-{{ $conversation->id }}"
                    class="w-full flex items-center gap-3 px-4 py-3 border-b border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/70 text-start transition-colors {{ $conversation->id === $this->conversationId ? 'bg-indigo-50/70 dark:bg-indigo-500/10' : '' }}"
                >
                    <flux:avatar :name="$other->name" :initials="$other->initials()" />
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <div class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 truncate">{{ $other->name }}</div>
                            @if ($conversation->latestMessage)
                                <div class="text-[11px] text-zinc-400 shrink-0">{{ $conversation->latestMessage->created_at->diffForHumans(null, true) }}</div>
                            @endif
                        </div>
                        <div class="flex items-center justify-between gap-2 mt-0.5">
                            <div class="text-xs text-zinc-500 dark:text-zinc-400 truncate">
                                {{ $conversation->latestMessage->body ?? __('No messages yet') }}
                            </div>
                            @if ($conversation->unread_count > 0)
                                <flux:badge size="sm" color="indigo">{{ $conversation->unread_count }}</flux:badge>
                            @endif
                        </div>
                    </div>
                </button>
            @empty
                <div class="px-5 py-12 text-center">
                    <span class="mx-auto flex size-11 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300">
                        <flux:icon.chat-bubble-left-right class="size-5" />
                    </span>
                    <p class="mt-3 text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Your inbox is clear') }}</p>
                    <p class="mt-1 text-xs leading-5 text-zinc-500 dark:text-zinc-400">{{ __('Search for a customer above to start a conversation.') }}</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Thread --}}
    <div class="flex-1 min-h-0 bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-700 shadow-sm flex-col overflow-hidden {{ $this->activeConversation ? 'flex' : 'hidden md:flex' }}">
        @if ($this->activeConversation)
            @php $other = $this->activeConversation->otherUser(auth()->user()); @endphp
            <div class="px-5 py-3.5 border-b border-zinc-100 dark:border-zinc-800 flex items-center gap-3 shrink-0">
                <button type="button" wire:click="closeConversation" class="md:hidden -ml-2 p-2 rounded-lg text-zinc-500 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800 shrink-0" aria-label="{{ __('Back to conversations') }}">
                    <flux:icon.chevron-left class="size-5" />
                </button>
                <flux:avatar size="sm" :name="$other->name" :initials="$other->initials()" />
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 truncate">{{ $other->name }}</div>
                    <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ $other->email }}</div>
                </div>
            </div>

            <div
                x-ref="messageList"
                x-init="
                    const scrollToBottom = () => { $refs.messageList.scrollTop = $refs.messageList.scrollHeight; };
                    scrollToBottom();
                    new MutationObserver(() => $nextTick(scrollToBottom)).observe($refs.messageList, { childList: true });
                "
                class="flex-1 min-h-0 overflow-y-auto chat-scroll px-5 py-5 flex flex-col space-y-3 bg-zinc-50/70 dark:bg-zinc-950/30"
            >
                @forelse ($this->threadMessages as $message)
                    @php $isMine = $message->sender_id === auth()->id(); @endphp
                    <div wire:key="message-{{ $message->id }}" class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[min(78%,42rem)] rounded-2xl px-4 py-2.5 text-sm leading-relaxed shadow-sm {{ $isMine ? 'bg-secondary text-white rounded-br-md' : 'bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-100 border border-zinc-200 dark:border-zinc-700 rounded-bl-md' }}">
                            <div class="whitespace-pre-wrap wrap-break-word">{{ $message->body }}</div>
                            <div class="text-[10px] mt-1 {{ $isMine ? 'text-white/70' : 'text-zinc-400' }}">
                                {{ $message->created_at->toDisplay('g:i A') }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="h-full flex items-center justify-center text-sm text-zinc-400">
                        {{ __('Say hello 👋') }}
                    </div>
                @endforelse
            </div>

            <form wire:submit="sendMessage" class="p-4 md:px-5 border-t border-zinc-100 dark:border-zinc-800 flex items-end gap-3 shrink-0 bg-white dark:bg-zinc-900">
                <div class="flex-1">
                    <flux:textarea
                        wire:model="messageBody"
                        rows="2"
                        placeholder="{{ __('Write a message...') }}"
                        x-on:keydown.enter.prevent="if (!$event.shiftKey) { $wire.sendMessage(); }"
                    />
                    <flux:error name="messageBody" />
                </div>
                <flux:button size="sm" type="submit" variant="primary" icon="paper-airplane" class="mb-0.5">
                    {{ __('Send') }}
                </flux:button>
            </form>
        @else
            <div class="flex-1 flex items-center justify-center px-6 py-12 text-center">
                <div class="max-w-sm">
                    <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">
                        <flux:icon.chat-bubble-left-right class="size-6" />
                    </span>
                    <h3 class="mt-4 text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Your conversations, all in one place') }}</h3>
                    <p class="mt-1.5 text-sm leading-6 text-zinc-500 dark:text-zinc-400">{{ __('Choose a conversation from your inbox, or search for a customer to start a new one.') }}</p>
                </div>
            </div>
        @endif
    </div>
    </div>

    @can('access-admin-system')
        <flux:modal name="reverb-settings" class="md:w-120"
            x-on:open-modal.window="if ($event.detail.name === 'reverb-settings') $flux.modal('reverb-settings').show()"
            x-on:close-modal.window="if ($event.detail.name === 'reverb-settings') $flux.modal('reverb-settings').close()">
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-amber-50 flex items-center justify-center shrink-0">
                        <flux:icon.bolt class="w-5 h-5 text-amber-500" />
                    </div>
                    <flux:heading>{{ __('Reverb Settings') }}</flux:heading>
                </div>
                <flux:text class="text-sm text-zinc-500">
                    {{ __('Powers real-time chat updates. These write straight to the live .env file — a wrong value can break live updates until fixed.') }}
                </flux:text>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <flux:field>
                        <flux:label>App ID</flux:label>
                        <flux:input wire:model="reverbSettings.REVERB_APP_ID" />
                        <flux:error name="reverbSettings.REVERB_APP_ID" />
                    </flux:field>
                    <flux:field>
                        <flux:label>App Key</flux:label>
                        <flux:input wire:model="reverbSettings.REVERB_APP_KEY" />
                        <flux:error name="reverbSettings.REVERB_APP_KEY" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>App Secret</flux:label>
                    <flux:input type="password" wire:model="reverbSettings.REVERB_APP_SECRET" />
                    <flux:error name="reverbSettings.REVERB_APP_SECRET" />
                </flux:field>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <flux:field class="sm:col-span-2">
                        <flux:label>Host</flux:label>
                        <flux:input wire:model="reverbSettings.REVERB_HOST" />
                        <flux:error name="reverbSettings.REVERB_HOST" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Port</flux:label>
                        <flux:input type="number" wire:model="reverbSettings.REVERB_PORT" />
                        <flux:error name="reverbSettings.REVERB_PORT" />
                    </flux:field>
                </div>

                <flux:field class="max-w-35">
                    <flux:label>Scheme</flux:label>
                    <select wire:model="reverbSettings.REVERB_SCHEME"
                        class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                        <option value="https">https</option>
                        <option value="http">http</option>
                    </select>
                    <flux:error name="reverbSettings.REVERB_SCHEME" />
                </flux:field>

                <div class="flex gap-2 pt-1">
                    <button wire:click="saveReverbSettings" wire:loading.attr="disabled" wire:target="saveReverbSettings"
                        class="inline-flex items-center gap-2 px-4 h-8 text-sm font-medium rounded-lg text-white bg-amber-600 hover:bg-amber-700 transition-colors border-none cursor-pointer disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveReverbSettings">{{ __('Save') }}</span>
                        <span wire:loading wire:target="saveReverbSettings">{{ __('Saving...') }}</span>
                    </button>
                    <flux:modal.close>
                        <flux:button size="sm" variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>
    @endcan
</div>
