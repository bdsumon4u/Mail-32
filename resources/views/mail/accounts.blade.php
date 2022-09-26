<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Email Accounts') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="w-full mx-auto text-center">
                        <Link
                            slideover
                            type="button"
                            href="{{ route('mail.create') }}"
                            class="px-4 py-2 m-2 text-white transition duration-500 bg-indigo-500 border border-indigo-500 rounded-md select-none ease hover:bg-indigo-600 focus:outline-none focus:shadow-outline"
                        >
                            Connect Shared Account
                        </Link>
                        <Link
                            slideover
                            type="button"
                            href="{{ route('mail.create') }}"
                            class="px-4 py-2 m-2 text-white transition duration-500 bg-green-500 border border-green-500 rounded-md select-none ease hover:bg-green-600 focus:outline-none focus:shadow-outline"
                        >
                            Connect Personal Account
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
