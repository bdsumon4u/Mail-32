<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Temp Mail') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto space-y-5 max-w-7xl sm:px-6 lg:px-8">
            <div class="p-2 my-2">Email: {{ $email }}</div>
            @foreach ($messages as $message)
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="w-full mx-auto text-center border" @class([$message->html_body ? 'border-green-500' : 'border-red-500'])>
                        {!! $message->html_body ?? $message->text_body !!}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
