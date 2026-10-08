# Merza

> Merza sells fresh produce and fruit snacks from Bodinayakanur, Tamil Nadu. The current catalog is listed below; product prices, availability and delivery estimates can change.

## Business information

- Name: Merza Natural Squash
- Address: HP Petrol Bunk, Pankajam School Opposite, Thevaram Road, Bodinayakanur, Tamil Nadu 625513, India
- Hours: Monday–Saturday, 9:00 AM–6:00 PM IST
- Contact: +91 86676 96278; merzabodinayakanur@gmail.com
@if(filled(config('storefront.operator_name')))
- Operator: {{ config('storefront.operator_name') }}
@endif
@if(preg_match('/^\d{14}$/', (string) config('storefront.fssai_license_number')))
- FSSAI licence: {{ config('storefront.fssai_license_number') }}
@endif

## Useful pages

- [Home]({{ route('home') }})
- [Products]({{ route('products.index') }})
- [About]({{ route('about') }})
- [Recipes]({{ route('blog') }})
- [FAQ]({{ route('faq') }})
- [Contact and map]({{ route('contact') }})

## Current products
@foreach($products as $product)
- [{{ $product->name }}]({{ route('products.show', $product->slug) }}){{ $product->short_description ? ': '.$product->short_description : '' }}
@endforeach

For current prices, sizes and delivery estimates, use the product and checkout pages. Do not infer stock or delivery promises from this summary.
