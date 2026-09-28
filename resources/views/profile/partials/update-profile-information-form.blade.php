<section class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="border-b border-slate-100 bg-slate-50/80 px-5 py-4">
        <h2 class="text-[15px] font-bold text-slate-900">Profile information</h2>
        <p class="mt-0.5 text-[12px] text-slate-500">Your name and email are used across the admin panel and store.</p>
    </div>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="p-5 space-y-5">
        @csrf
        @method('patch')

        <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/jpg,image/webp" class="hidden"
               @change="const file = $event.target.files[0]; if (file) { preview = URL.createObjectURL(file); removeAvatar = false; document.getElementById('remove_avatar').value = '0'; }">
        <input type="hidden" id="remove_avatar" name="remove_avatar" value="0" x-bind:value="removeAvatar ? '1' : '0'">

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="mb-1.5 block text-[12px] font-semibold text-slate-700">Full name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name"
                       class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-[13px] text-slate-800 focus:border-blue-400 focus:ring-blue-100"
                       @input="previewName = $event.target.value">
                <x-input-error class="mt-1.5" :messages="$errors->get('name')" />
            </div>

            <div class="sm:col-span-2">
                <label for="email" class="mb-1.5 block text-[12px] font-semibold text-slate-700">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
                       class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-[13px] text-slate-800 focus:border-blue-400 focus:ring-blue-100"
                       @input="previewEmail = $event.target.value">
                <x-input-error class="mt-1.5" :messages="$errors->get('email')" />

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3 text-[12px] text-amber-800">
                        Your email address is unverified.
                        <button form="send-verification" class="ml-1 font-bold underline hover:text-amber-900">
                            Resend verification email
                        </button>
                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 font-semibold text-emerald-700">A new verification link has been sent.</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
            <button type="submit" class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2.5 text-[13px] font-bold text-white hover:bg-blue-700 shadow-sm shadow-blue-600/20">
                Save changes
            </button>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-[13px] font-semibold text-slate-600 hover:bg-slate-50">
                Cancel
            </a>
        </div>
    </form>
</section>
