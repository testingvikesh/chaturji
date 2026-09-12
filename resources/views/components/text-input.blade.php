@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'border-gray-300 focus:border-brand-green focus:ring-brand-green rounded-md shadow-sm']) !!}>
