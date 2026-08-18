@if ($paginator->hasPages())
<style>
    .pagination-papa{
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: var(--gap-card);
        width:100%;
    }
@media (max-width: 450px){
.pagination-papa{
        flex-direction: column;
        justify-content: center;
    }
}
</style>
    
        <div class="pagination-papa">
            {{-- Page count metar --}}
            <div>
                <p class="small m-0">
                    {!! __('Showing') !!}
                    <span class="fw-semibold">{{ $paginator->firstItem() }}</span>
                    {!! __('to') !!}
                    <span class="fw-semibold">{{ $paginator->lastItem() }}</span>
                    {!! __('of') !!}
                    <span class="fw-semibold">{{ $paginator->total() }}</span>
                    {!! __('results') !!}
                </p>
            </div>

            {{-- Pagination --}}
            <div>
                <ul>
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        {{-- <li class="numb disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                            <span aria-hidden="true">&lsaquo;</span>
                        </li> --}}
                    @else
                        <li class="btn prev">
                            <a class="page-link-ajax" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">
                                <i class="fas fa-angle-left"></i> Prev
                            </a>
                        </li>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <li class="numb disabled" aria-disabled="true"><span>{{ $element }}</span></li>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <li class="numb active" aria-current="page"><span>{{ $page }}</span></li>
                                @else
                                    <li class="numb"><a href="{{ $url }}" class="align-content-center h-100 w-100 page-link-ajax">{{ $page }}</a></li>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    
                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <li class="btn next">
                            <a class="page-link-ajax" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">
                                Next <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                    @else
                        {{-- <li class="btn next disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                            Next <i class="fas fa-angle-right"></i>
                        </li> --}}
                    @endif
                </ul>
            </div>
        </div>
    {{-- </nav> --}}
@endif
