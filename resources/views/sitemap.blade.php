<?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc>{{ route('home') }}</loc><changefreq>weekly</changefreq><priority>1.0</priority></url>
  <url><loc>{{ route('shop') }}</loc><changefreq>daily</changefreq><priority>0.9</priority></url>
  @foreach ($products as $product)
  <url>
    <loc>{{ route('product', $product->slug) }}</loc>
    <lastmod>{{ $product->updated_at->toAtomString() }}</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
  @endforeach
</urlset>
