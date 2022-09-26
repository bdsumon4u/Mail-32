<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Create Email Account') }}
        </h2>
    </x-slot>

    <x-splade-modal max-width="2xl">
        <div class="">
            <h3 class="py-2 text-xl font-semibold">Create Email Account</h3>
            <x-splade-form action="{{ route('mail.store') }}" class="mt-2" default="{connection_type: 'Imap', initial_sync_from: '-1 month', email: 'support@rialtobd.com', username: 'support@rialtobd.com', password: '@Cyber32.com', imap_server: 'mail.rialtobd.com', imap_port: 993, imap_encryption: 'ssl', smtp_server: 'mail.rialtobd.com', smtp_port: 465, smtp_encryption: 'ssl'}">
                <x-splade-select
                    class="mb-3"
                    name="connection_type"
                    label="* Account Type"
                    :options="$connection_types"
                    placeholder="Select Account Type"
                />
                <x-splade-radios
                    name="initial_sync_from"
                    inline
                    label="Sync emails from"
                    :options="['now' => 'Now', '-1 month' => '1 month ago', '-3 months' =>  '3 months ago', '-6 months' => '6 months ago']"
                />
                <div
                    class="mt-3"
                    :class="{'blur-sm pointer-events-none': form.connection_type != 'Imap'}"
                >
                    <x-splade-input
                        type="email"
                        class="mb-3"
                        name="email"
                        label="Email Address"
                    />
                    <x-splade-input
                        type="password"
                        class="mb-3"
                        name="password"
                        label="Password"
                    />
                    <x-splade-input
                        class="mb-3"
                        name="username"
                        label="Username (Optional)"
                    />
                    <div class="mb-3">
                        <h5
                            class="mb-3 font-medium text-neutral-700 dark:text-neutral-100"
                        >
                            Incoming Mail (IMAP)
                        </h5>
                        <x-splade-input
                            class="mb-3"
                            name="imap_server"
                            label="* Server"
                        />

                        <div class="grid grid-cols-6 gap-6">
                            <div class="col-span-2">
                                <x-splade-input
                                    name="imap_port"
                                    label="* Port"
                                />
                            </div>
                            <div class="col-span-4">
                                <x-splade-select
                                    name="imap_encryption"
                                    label="Encryption"
                                    :options="['ssl' => 'SSL', 'tls' => 'TLS']"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <h5
                            class="mb-3 font-medium text-neutral-700 dark:text-neutral-100"
                        >
                            Outgoing Mail (SMTP)
                        </h5>
                        <x-splade-input
                            class="mb-3"
                            name="smtp_server"
                            label="* Server"
                        />

                        <div class="grid grid-cols-6 gap-6">
                            <div class="col-span-2">
                                <x-splade-input
                                    name="smtp_port"
                                    label="* Port"
                                />
                            </div>
                            <div class="col-span-4">
                                <x-splade-select
                                    name="smtp_encryption"
                                    label="Encryption"
                                    :options="['ssl' => 'SSL', 'tls' => 'TLS']"
                                />
                            </div>
                        </div>
                    </div>
                    <x-splade-checkbox
                        name="validate_cert"
                        label="Allow non-secure certificate"
                    />
                    @if ($folders = session('imapFolders'))
                    <div class="mb-3">
                        <label
                            class="block mb-1 text-sm font-medium text-neutral-700 dark:text-neutral-100"
                            >Active folders</label
                        >
                        <div class="mt-3">
                            @foreach ($folders as $folder)
                                <x-splade-checkbox name="folders[{{ $loop->index }}].syncable" label="Folder" />
                            @endforeach
                        </div>
                        <!---->
                    </div>
                    @endif
                </div>
                <div class="mt-4 shrink-0 dark:bg-neutral-800">
                    <div
                        class="flex flex-wrap justify-end space-x-3 sm:flex-nowrap"
                    >
                        <button
                            type="submit"
                            class="px-3 py-2 text-white transition duration-500 bg-indigo-500 border border-indigo-500 rounded-md select-none ease hover:bg-indigo-600 focus:outline-none focus:shadow-outline"
                        >
                            Cancel
                        </button>
                        @if ($authLink = session('authLink'))
                            <a
                                href="{{ $authLink }}"
                                target="_blank"
                                class="px-3 py-2 text-white transition duration-500 bg-indigo-500 border border-indigo-500 rounded-md select-none ease hover:bg-indigo-600 focus:outline-none focus:shadow-outline"
                            >
                                Authenticate
                            </a>
                        @else
                            <button
                                type="submit"
                                v-if="form.connection_type === 'Imap'"
                                class="px-3 py-2 text-white transition duration-500 bg-indigo-500 border border-indigo-500 rounded-md select-none ease hover:bg-indigo-600 focus:outline-none focus:shadow-outline"
                            >
                                Test Connection
                            </button>
                            <button
                                v-else
                                type="submit"
                                class="px-3 py-2 text-white transition duration-500 bg-indigo-500 border border-indigo-500 rounded-md select-none ease hover:bg-indigo-600 focus:outline-none focus:shadow-outline"
                            >
                                Connect Account
                            </button>
                        @endif
                    </div>
                </div>
            </x-splade-form>
        </div>
    </x-splade-modal>
</x-app-layout>
