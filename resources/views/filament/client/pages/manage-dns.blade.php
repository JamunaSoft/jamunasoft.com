<x-filament-panels::page>
    @if ($loadError)
        <div class="rounded-xl bg-warning-50 p-4 text-sm text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">
            {{ $loadError }}
            <button type="button" wire:click="loadRecords" class="ml-2 font-semibold underline">
                {{ __('Retry') }}
            </button>
        </div>
    @elseif (count($records) === 0)
        <div class="rounded-xl bg-gray-50 p-6 text-center text-sm text-gray-500 dark:bg-white/5 dark:text-gray-400">
            {{ __('No DNS records yet — add your first record above.') }}
        </div>
    @else
        <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                        <th class="px-4 py-3">{{ __('Type') }}</th>
                        <th class="px-4 py-3">{{ __('Name') }}</th>
                        <th class="px-4 py-3">{{ __('Value') }}</th>
                        <th class="px-4 py-3">{{ __('TTL') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach ($records as $index => $record)
                        <tr>
                            <td class="px-4 py-3 font-semibold">{{ $record['type'] ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $record['name'] ?? '@' }}</td>
                            <td class="max-w-md break-all px-4 py-3 text-gray-600 dark:text-gray-300">{{ $this->recordValue($record) }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $record['ttl'] ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    type="button"
                                    wire:click="removeRecord({{ $index }})"
                                    wire:confirm="{{ __('Delete this DNS record?') }}"
                                    class="text-sm font-semibold text-danger-600 hover:text-danger-500"
                                >
                                    {{ __('Delete') }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ __('DNS changes can take up to a few hours to propagate worldwide.') }}
        </p>
    @endif
</x-filament-panels::page>
