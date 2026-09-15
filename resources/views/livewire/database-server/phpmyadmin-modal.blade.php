<div>
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" @click.self="$wire.closeModal()">
            <div class="w-[95vw] max-w-md bg-base-100 rounded-lg shadow-xl flex flex-col overflow-hidden">
                <div class="flex items-center justify-between px-4 py-2 border-b border-base-300 shrink-0">
                    <div class="flex items-center gap-2">
                        <x-icon :name="$databaseIcon" class="w-5 h-5" />
                        <span class="text-sm text-base-content/70">{{ $databaseType }}</span>
                        <h3 class="text-sm font-bold">{{ $serverName }}</h3>
                    </div>
                    <button class="btn btn-sm btn-ghost btn-circle" @click="$wire.closeModal()">
                        <x-icon name="o-x-mark" class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-4 flex flex-col gap-4">
                    <p class="text-sm text-base-content/70">
                        {{ __('phpMyAdmin opened in a new tab with host, port and username pre-filled. Paste the password below to log in — phpMyAdmin does not support passing it via URL.') }}
                    </p>

                    <x-copy-input :value="$username" :label="__('Username')" />
                    <x-copy-input :value="$password" :label="__('Password')" />

                    <x-button
                        :label="__('Open phpMyAdmin')"
                        icon="o-arrow-top-right-on-square"
                        :link="$phpmyadminUrl"
                        external
                        class="btn-primary"
                    />
                </div>
            </div>
        </div>
    @endif
</div>
