<section class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="border-b border-slate-100 bg-slate-50/80 px-5 py-4">
        <h2 class="text-[15px] font-bold text-slate-900">Password & security</h2>
        <p class="mt-0.5 text-[12px] text-slate-500">Use a strong password you don’t reuse on other sites.</p>
    </div>

    <form method="post" action="{{ route('password.update') }}" class="p-5 space-y-4">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="mb-1.5 block text-[12px] font-semibold text-slate-700">Current password</label>
            <div class="relative">
                <input id="update_password_current_password" name="current_password" :type="showCurrent ? 'text' : 'password'" autocomplete="current-password"
                       class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 pr-10 text-[13px] text-slate-800 focus:border-blue-400 focus:ring-blue-100">
                <button type="button" @click="showCurrent = !showCurrent" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1.5" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="update_password_password" class="mb-1.5 block text-[12px] font-semibold text-slate-700">New password</label>
                <div class="relative">
                    <input id="update_password_password" name="password" :type="showNew ? 'text' : 'password'" autocomplete="new-password"
                           class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 pr-10 text-[13px] text-slate-800 focus:border-blue-400 focus:ring-blue-100">
                    <button type="button" @click="showNew = !showNew" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1.5" />
            </div>
            <div>
                <label for="update_password_password_confirmation" class="mb-1.5 block text-[12px] font-semibold text-slate-700">Confirm password</label>
                <div class="relative">
                    <input id="update_password_password_confirmation" name="password_confirmation" :type="showConfirm ? 'text' : 'password'" autocomplete="new-password"
                           class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 pr-10 text-[13px] text-slate-800 focus:border-blue-400 focus:ring-blue-100">
                    <button type="button" @click="showConfirm = !showConfirm" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1.5" />
            </div>
        </div>

        <p class="flex items-center gap-1.5 text-[11px] text-slate-500">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Password must be at least 8 characters.
        </p>

        <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
            <button type="submit" class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2.5 text-[13px] font-bold text-white hover:bg-blue-700 shadow-sm shadow-blue-600/20">
                Update password
            </button>
        </div>
    </form>
</section>
