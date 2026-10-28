@if ($paginator->hasPages())
<nav role="navigation" aria-label="Pagination" class="stemi-page-links" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    @if ($paginator->onFirstPage())<span aria-disabled="true">@include('partials.eye-icon') Previous</span>
    @else<a href="{{ $paginator->previousPageUrl() }}" rel="prev">@include('partials.eye-icon') Previous</a>@endif
    @foreach ($elements as $element)
        @if (is_string($element))<span>{{ $element }}</span>@endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())<strong aria-current="page" style="padding:6px 10px;background:#e5f4ff;border-radius:6px">{{ $page }}</strong>
                @else<a href="{{ $url }}" aria-label="Go to page {{ $page }}" style="padding:6px 10px">{{ $page }}</a>@endif
            @endforeach
        @endif
    @endforeach
    @if ($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next">Next @include('partials.eye-icon')</a>
    @else<span aria-disabled="true">Next @include('partials.eye-icon')</span>@endif
</nav>
@endif
