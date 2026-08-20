<x-filament-panels::page>
    <div class="space-y-4">
        @foreach ($this->record->messages as $message)
            <div @class([
                'rounded-xl p-5 shadow-sm ring-1',
                'bg-white ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10' => ! $message->is_staff,
                'bg-primary-50 ring-primary-600/10 dark:bg-primary-500/10 dark:ring-primary-400/20' => $message->is_staff,
            ])>
                <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <span class="font-semibold text-gray-700 dark:text-gray-200">
                        {{ $message->author?->name ?? __('Unknown') }}
                        @if ($message->is_staff)
                            <span class="ml-1 rounded-full bg-primary-600 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">{{ __('Staff') }}</span>
                        @endif
                    </span>
                    <span>{{ $message->created_at->format('d M Y, H:i') }}</span>
                </div>
                <div class="mt-3 whitespace-pre-line text-sm leading-relaxed text-gray-700 dark:text-gray-200">{{ $message->message }}</div>
            </div>
        @endforeach

        @if ($this->record->status === \App\Enums\TicketStatus::Closed)
            <div class="rounded-xl bg-gray-50 p-4 text-center text-sm text-gray-500 dark:bg-white/5 dark:text-gray-400">
                {{ __('This ticket is closed. Replying will reopen it.') }}
            </div>
        @endif
    </div>
</x-filament-panels::page>
