@props([
    'state' => 'active',
    'label' => null,
    'size' => 'md',
])

@php
    $config = match ($state) {
        'lifetime' => [
            'label' => $label ?? 'Lifetime Access',
            'classes' => 'bg-indigo-50 text-indigo-700 border-indigo-200/80',
            'dot' => 'bg-indigo-500',
        ],
        'expiring' => [
            'label' => $label ?? 'Expires Soon',
            'classes' => 'bg-amber-50 text-amber-700 border-amber-200/80',
            'dot' => 'bg-amber-500',
        ],
        'expired' => [
            'label' => $label ?? 'Access Expired',
            'classes' => 'bg-rose-50 text-rose-700 border-rose-200/80',
            'dot' => 'bg-rose-500',
        ],
        default => [
            'label' => $label ?? 'Access Active',
            'classes' => 'bg-emerald-50 text-emerald-700 border-emerald-200/80',
            'dot' => 'bg-emerald-500',
        ],
    };

    $sizeClasses = $size === 'sm'
        ? 'px-2 py-0.5 text-[10px]'
        : 'px-2.5 py-1 text-xs';
@endphp

<span class="inline-flex items-center gap-1.5 rounded-full font-bold uppercase tracking-wider border shadow-2xs {{ $config['classes'] }} {{ $sizeClasses }}">
    <span class="h-1.5 w-1.5 rounded-full {{ $config['dot'] }}"></span>
    <span>{{ $config['label'] }}</span>
</span>
