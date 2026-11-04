<div class="space-y-5">
    <p class="text-sm leading-6 text-zinc-600">
        {{ __('To continue, enter your current password and confirm that you understand the effects listed above.') }}
    </p>

    <form wire:submit="delete" class="space-y-5">
        <div>
            <label for="delete-current-password" class="mb-1.5 block text-sm font-semibold text-zinc-700">
                {{ __('Current password') }}
            </label>
            <input id="delete-current-password" wire:model="current_password" type="password" required
                autocomplete="current-password"
                class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-sm text-sf-text outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
            @error('current_password')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-start gap-2.5 text-sm leading-5 text-zinc-700">
            <input wire:model="confirm_deletion" type="checkbox" class="mt-0.5 rounded border-zinc-300 text-red-600 focus:ring-red-500">
            <span>{{ __('I understand that deleting my account is permanent and cannot be undone.') }}</span>
        </label>
        @error('confirm_deletion')
            <p class="-mt-4 text-xs text-red-600">{{ $message }}</p>
        @enderror

        <button type="submit"
            class="rounded-md bg-red-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">
            {{ __('Delete account permanently') }}
        </button>
    </form>
</div>
