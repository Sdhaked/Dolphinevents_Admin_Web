@php
    $breadcrumb_title = $breadcrumb_title ?? 'Dashboard';
    $breadcrumb_items = $breadcrumb_items ?? [];
@endphp

<section class="breadcrumb-sec">
    <h5 class="pTitle">{{ $breadcrumb_title }}</h5>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item" onclick="history.back()">
                Previous Pg
            </li>
            @if(!empty($breadcrumb_items))
                @foreach($breadcrumb_items as $item)
                    @if($loop->last)
                        <li class="breadcrumb-item active" aria-current="page">
                            {{ $item['title'] }}
                        </li>
                    @else
                        <li class="breadcrumb-item">
                            @if(isset($item['url']))
                                <a href="{{ $item['url'] }}">{{ $item['title'] }}</a>
                            @else
                                {{ $item['title'] }}
                            @endif
                        </li>
                    @endif
                @endforeach
            @else
                <li class="breadcrumb-item active" aria-current="page">
                    {{ $breadcrumb_title }}
                </li>
            @endif
        </ol>
    </nav>
</section>