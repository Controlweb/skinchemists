<?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc>{{ route('home') }}</loc><changefreq>weekly</changefreq><priority>1.0</priority></url>
  <url><loc>{{ route('shop') }}</loc><changefreq>daily</changefreq><priority>0.9</priority></url>
  <url><loc>{{ route('bundles') }}</loc><changefreq>weekly</changefreq><priority>0.8</priority></url>
  <url><loc>{{ route('lab') }}</loc><changefreq>weekly</changefreq><priority>0.7</priority></url>
  <url><loc>{{ route('contact') }}</loc><changefreq>yearly</changefreq><priority>0.5</priority></url>
  @foreach ($products as $product)
  <url>
    <loc>{{ route('product', $product->slug) }}</loc>
    <lastmod>{{ $product->updated_at->toAtomString() }}</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
  @endforeach
  @foreach ($ingredients as $ingredient)
  <url>
    <loc>{{ route('ingredient', $ingredient->slug) }}</loc>
    <lastmod>{{ $ingredient->updated_at->toAtomString() }}</lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.6</priority>
  </url>
  @endforeach
  @foreach ($articles as $article)
  <url>
    <loc>{{ route('article', $article->slug) }}</loc>
    <lastmod>{{ $article->updated_at->toAtomString() }}</lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.6</priority>
  </url>
  @endforeach
</urlset>
