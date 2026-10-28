@props(['messages'])

@if (!empty($messages))
    <ul {{ $attributes->merge(['class' => 'text-xs text-red-500 space-y-1 mt-1']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
