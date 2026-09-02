{{--
  Laravel's bundled paginator views are Tailwind-only, and the storefront
  layout loads no Tailwind: every `sm:hidden` collapsed, so the mobile and
  desktop blocks both rendered, unstyled, next to an English "Showing … results".
  Inline styles, like the rest of the storefront.
--}}
@php
    $base = 'display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:38px;padding:0 12px;border:1px solid #E6E6E6;font-size:11px;letter-spacing:0.12em;text-transform:uppercase';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" style="display:flex;align-items:center;justify-content:center;gap:6px;flex-wrap:wrap">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" style="{{ $base }};color:#CFC7BA">Précédent</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" style="{{ $base }};color:#14120F">Précédent</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span aria-disabled="true" style="{{ $base }};border-color:transparent;color:#9B9B9B">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" style="{{ $base }};background:#14120F;border-color:#14120F;color:#FFFFFF">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" aria-label="Page {{ $page }}" style="{{ $base }};color:#14120F">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" style="{{ $base }};color:#14120F">Suivant</a>
        @else
            <span aria-disabled="true" style="{{ $base }};color:#CFC7BA">Suivant</span>
        @endif
    </nav>
@endif
