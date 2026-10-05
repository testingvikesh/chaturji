@props([
    'anchor',
    'gated' => false,
    'label' => 'Section',
    'tone' => 'content',
])

@if ($gated)
    <details id="gate-{{ $anchor }}" data-section="{{ $anchor }}" class="material-section-details {{ $tone === 'question' ? 'is-question' : '' }}">
        <summary>
            <span class="material-section-details-label">{{ $label }}</span>
            <span class="material-section-details-action">
                <span class="show-label">Show</span>
                <span class="hide-label">Hide</span>
            </span>
        </summary>
        <div class="material-section-details-body">
            {{ $slot }}
        </div>
    </details>
@else
    <div>
        {{ $slot }}
    </div>
@endif
