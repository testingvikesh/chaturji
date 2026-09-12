@php
    use App\Support\MaterialWorkedExamples;

    $points = MaterialWorkedExamples::toPoints($text ?? '');
    $asFormula = (bool) ($asFormula ?? false);
@endphp

@if ($points !== [])
    <ul class="ex-points">
        @foreach ($points as $point)
            <li>
                <span class="ex-points-no">{{ $loop->iteration }}</span>
                <span class="{{ $asFormula ? 'ex-formula' : 'ex-points-text' }}">
                    @if ($asFormula && MaterialWorkedExamples::looksLikeLatex($point))
                        \( {{ MaterialWorkedExamples::latex($point) }} \)
                    @else
                        {{ $point }}
                    @endif
                </span>
            </li>
        @endforeach
    </ul>
@endif
