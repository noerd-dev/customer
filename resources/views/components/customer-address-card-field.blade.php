<?php

new class extends \Noerd\Livewire\RelationFieldComponent {}; ?>

@php($address = $this->relatedModel())

<div>
    <x-noerd::input-label for="{{ $fieldName }}" :value="__($label)" :required="$required" :helpText="$helpText" />

    <div class="relative">
        @if ($address)
            <div
                id="{{ $fieldName }}"
                class="min-h-16 w-full rounded-lg border border-zinc-200 border-b-zinc-300/80 bg-white px-3 py-2 pe-14 text-sm leading-5 text-zinc-700 shadow-xs @unless($readonly) cursor-pointer hover:border-zinc-300 @endunless"
                @unless($readonly)
                    @click="$modal('{{ $listComponent }}', {id: {{ $modelId ?: 'null' }}, context: '{{ $fieldName }}', listActionMethod: 'selectAction'})"
                @endunless
            >
                @if ($address->label)
                    <div class="font-medium">{{ $address->label }}</div>
                @endif
                <div>{{ $address->address_line_1 }}</div>
                @if ($address->address_line_2)
                    <div>{{ $address->address_line_2 }}</div>
                @endif
                <div>{{ mb_trim(($address->postal_code ?? '') . ' ' . ($address->locality ?? '')) }}</div>
                @if ($address->country_code)
                    <div class="text-zinc-400">{{ $address->country_code }}</div>
                @endif
            </div>

            @unless($readonly)
                <div class="absolute top-1.5 right-1.5 flex gap-0.5">
                    <button
                        type="button"
                        wire:click="openDetail"
                        title="{{ __('Edit') }}"
                        class="p-1 text-zinc-400 hover:text-zinc-600"
                    >
                        <x-noerd::icons.pencil class="h-4 w-4"></x-noerd::icons.pencil>
                    </button>
                    <button
                        type="button"
                        wire:click="clear"
                        title="{{ __('Remove') }}"
                        class="p-1 text-zinc-400 hover:text-zinc-600"
                    >
                        <x-noerd::icons.x-mark class="h-4 w-4"></x-noerd::icons.x-mark>
                    </button>
                </div>
            @endunless
        @else
            <div
                id="{{ $fieldName }}"
                class="flex min-h-16 w-full items-center justify-center rounded-lg border border-dashed border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-400 @unless($readonly) cursor-pointer hover:border-zinc-400 hover:text-zinc-500 @endunless"
                @unless($readonly)
                    @click="$modal('{{ $listComponent }}', {id: {{ $modelId ?: 'null' }}, context: '{{ $fieldName }}', listActionMethod: 'selectAction'})"
                @endunless
            >
                {{ __('Select address') }}
            </div>
        @endif
    </div>
</div>
