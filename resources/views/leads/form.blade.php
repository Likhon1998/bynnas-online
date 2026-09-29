<x-app-layout>
@php
    $editing = $lead->exists;
    $input = 'mt-1 block w-full rounded-lg border-slate-200 text-[13px] focus:border-indigo-400 focus:ring-indigo-200';
    $label = 'block text-[11px] font-bold uppercase tracking-wide text-slate-600';
@endphp

<div class="mx-auto max-w-3xl space-y-4 text-[12px] text-slate-700">
    <div>
        <a href="{{ $editing ? route('leads.show', $lead) : route('leads.index') }}" class="text-[12px] font-semibold text-indigo-600 hover:text-indigo-700">← Back</a>
        <h1 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900">{{ $editing ? 'Edit lead' : 'Add lead' }}</h1>
        <p class="text-[12px] text-slate-500">Record an inquiry received on a social platform, by phone, or on the website.</p>
    </div>

    <form method="POST" action="{{ $editing ? route('leads.update', $lead) : route('leads.store') }}"
          class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        @csrf
        @if($editing) @method('PUT') @endif

        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-[12px] text-rose-700">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="{{ $label }}">Name <span class="text-rose-500">*</span></label>
                <input name="name" value="{{ old('name', $lead->name) }}" required maxlength="120" class="{{ $input }}">
            </div>
            <div>
                <label class="{{ $label }}">Phone</label>
                <input name="phone" type="tel" value="{{ old('phone', $lead->phone) }}" maxlength="30" placeholder="01XXXXXXXXX" class="{{ $input }}">
                <p class="mt-1 text-[10px] text-slate-400">Needed to create a customer or order. Matches existing customers by phone.</p>
            </div>
            <div>
                <label class="{{ $label }}">Email</label>
                <input name="email" type="email" value="{{ old('email', $lead->email) }}" maxlength="160" class="{{ $input }}">
            </div>
            <div>
                <label class="{{ $label }}">Source <span class="text-rose-500">*</span></label>
                <select name="source" required class="{{ $input }}">
                    @foreach(\App\Models\Lead::SOURCES as $key => $name)
                        <option value="{{ $key }}" @selected(old('source', $lead->source) === $key)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="{{ $label }}">Conversation reference</label>
                <input name="conversation_ref" value="{{ old('conversation_ref', $lead->conversation_ref) }}" maxlength="255"
                       placeholder="Profile link, page inbox thread, comment link or WhatsApp number" class="{{ $input }}">
                <p class="mt-1 text-[10px] text-slate-400">Where the conversation is, so anyone can pick it up on the platform.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 border-t border-slate-100 pt-4 md:grid-cols-3">
            <div>
                <label class="{{ $label }}">Product of interest</label>
                <select name="product_id" class="{{ $input }}">
                    <option value="">—</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected((string) old('product_id', $lead->product_id) === (string) $product->id)>{{ $product->storefrontDisplayName() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $label }}">Campaign</label>
                <select name="campaign_id" class="{{ $input }}">
                    <option value="">—</option>
                    @foreach($campaigns as $campaign)
                        <option value="{{ $campaign->id }}" @selected((string) old('campaign_id', $lead->campaign_id) === (string) $campaign->id)>{{ $campaign->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $label }}">Landing page</label>
                <select name="landing_page_id" class="{{ $input }}">
                    <option value="">—</option>
                    @foreach($landingPages as $page)
                        <option value="{{ $page->id }}" @selected((string) old('landing_page_id', $lead->landing_page_id) === (string) $page->id)>{{ $page->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 border-t border-slate-100 pt-4 md:grid-cols-3">
            @unless($editing)
                <div>
                    <label class="{{ $label }}">Status</label>
                    <select name="status" class="{{ $input }}">
                        @foreach(['new', 'contacted', 'interested', 'follow_up'] as $key)
                            <option value="{{ $key }}" @selected(old('status', $lead->status) === $key)>{{ \App\Models\Lead::STATUSES[$key] }}</option>
                        @endforeach
                    </select>
                </div>
            @endunless
            <div>
                <label class="{{ $label }}">Assigned to</label>
                <select name="assigned_to" class="{{ $input }}">
                    <option value="">Unassigned</option>
                    @foreach($staff as $member)
                        <option value="{{ $member->id }}" @selected((string) old('assigned_to', $lead->assigned_to ?? ($editing ? null : auth()->id())) === (string) $member->id)>{{ $member->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $label }}">Follow-up date</label>
                <input type="datetime-local" name="follow_up_at" value="{{ old('follow_up_at', $lead->follow_up_at?->format('Y-m-d\TH:i')) }}" class="{{ $input }}">
            </div>
        </div>

        <div>
            <label class="{{ $label }}">Notes</label>
            <textarea name="notes" rows="4" maxlength="5000" class="{{ $input }}" placeholder="What they asked for, budget, size/colour, delivery area…">{{ old('notes', $lead->notes) }}</textarea>
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
            <a href="{{ $editing ? route('leads.show', $lead) : route('leads.index') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-[12px] font-semibold text-slate-600 hover:bg-slate-50">Cancel</a>
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-[12px] font-bold text-white hover:bg-indigo-700">{{ $editing ? 'Save changes' : 'Add lead' }}</button>
        </div>
    </form>
</div>
</x-app-layout>
