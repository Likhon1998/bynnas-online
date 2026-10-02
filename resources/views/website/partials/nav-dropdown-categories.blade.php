{{--
  Desktop nav "Categories" dropdown items.
  @var string $allUrl
--}}
<a href="{{ $allUrl }}" class="gaget-nav-dropdown-item gaget-nav-dropdown-item--all">
    <span>All categories</span>
    <span class="gaget-nav-dropdown-cta">View all @include('website.partials.nav-dropdown-arrow', ['class' => ''])</span>
</a>
@forelse($allCategories ?? [] as $cat)
    @php $catMeta = $cat->iconMeta(); @endphp
    <a href="{{ route('website.category', $cat->slug) }}" class="gaget-nav-dropdown-item" style="--i: {{ min($loop->index + 1, 14) }}">
        <span class="gaget-nav-dropdown-icon" style="--dd-bg: {{ $catMeta['bg'] }}; --dd-fg: {{ $catMeta['color'] }};">
            @include('website.partials.category-icon-svg', ['icon' => $catMeta['key'], 'class' => ''])
        </span>
        <span class="gaget-nav-dropdown-label">{{ $cat->name }}</span>
        @include('website.partials.nav-dropdown-arrow')
    </a>
@empty
    <span class="gaget-nav-dropdown-empty">No categories yet</span>
@endforelse
