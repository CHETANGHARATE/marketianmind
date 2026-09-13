<title>{{ $meta['title'] }}</title>
<meta name="description" content="{{ $meta['description'] }}">
<meta name="robots" content="{{ $meta['robots'] }}">
<link rel="canonical" href="{{ $meta['canonical'] }}">

<!-- Open Graph / Facebook -->
<meta property="og:title" content="{{ $meta['og_title'] }}">
<meta property="og:description" content="{{ $meta['og_description'] }}">
<meta property="og:url" content="{{ $meta['og_url'] }}">
<meta property="og:type" content="{{ $meta['og_type'] }}">
<meta property="og:site_name" content="{{ $meta['og_site_name'] }}">
@if(!empty($meta['og_image']))
<meta property="og:image" content="{{ $meta['og_image'] }}">
@endif

<!-- Twitter / X -->
<meta name="twitter:card" content="{{ $meta['twitter_card'] }}">
<meta name="twitter:title" content="{{ $meta['twitter_title'] }}">
<meta name="twitter:description" content="{{ $meta['twitter_description'] }}">
@if(!empty($meta['twitter_image']))
<meta name="twitter:image" content="{{ $meta['twitter_image'] }}">
@endif

<!-- JSON-LD Structured Data Schemas -->
@if(!empty($meta['schemas']))
@foreach($meta['schemas'] as $schema)
<script type="application/ld+json">
{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
@endforeach
@endif
