<section class="rounded-2xl border border-rose-200 bg-white shadow-sm overflow-hidden">
    <div class="border-b border-rose-100 bg-rose-50/60 px-5 py-4">
        <h2 class="text-[15px] font-bold text-rose-800">Danger zone</h2>
        <p class="mt-0.5 text-[12px] text-rose-700/80">Deleting your account is permanent. This cannot be undone.</p>
    </div>

    <div class="p-5 space-y-4">
        <p class="text-[13px] leading-relaxed text-slate-600">
            Once deleted, your admin access and profile data are removed. Make sure another store owner can still manage the shop before you continue.
        </p>

        <x-danger-button
            class="!rounded-xl !px-5 !py-2.5 !text-[13px] !font-bold"
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        >{{ __('Delete account') }}</x-danger-button>
    </div>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            </div>

            <h2 class="text-center text-lg font-bold text-slate-900">
                Delete your account?
            </h2>

            <p class="mt-2 text-center text-[13px] text-slate-500">
                Enter your password to confirm. This action is permanent.
            </p>

            <div class="mt-5">
                <label for="password" class="mb-1.5 block text-[12px] font-semibold text-slate-700">Password</label>
                <input id="password" name="password" type="password" placeholder="Your current password"
                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-[13px] focus:border-rose-400 focus:ring-rose-100" />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-1.5" />
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <x-secondary-button class="!rounded-xl" x-on:click="$dispatch('close')">
                    Cancel
                </x-secondary-button>
                <x-danger-button class="!rounded-xl sm:ms-2">
                    Yes, delete account
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
