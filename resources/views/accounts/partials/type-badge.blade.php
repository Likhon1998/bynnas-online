@php
    use App\Support\AccountUi;
    $badge = AccountUi::typeBadge($type);
@endphp
<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold ring-1 ring-inset {{ $badge['bg'] }} {{ $badge['text'] }} {{ $badge['ring'] }}">
    {{ AccountUi::typeLabel($type) }}
</span>
