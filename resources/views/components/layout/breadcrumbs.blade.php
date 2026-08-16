@props(['trail' => []])

@if (count($trail) > 1)
    <nav class="breadcrumbs" aria-label="مسار التصفح">
        <ol>
            @foreach ($trail as $crumb)
                <li>
                    @if (! empty($crumb['url']) && ! $loop->last)
                        <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                    @else
                        <span aria-current="page">{{ $crumb['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
