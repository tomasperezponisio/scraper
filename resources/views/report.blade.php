<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Casas en venta — Bahía Blanca</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg: #f7f6f3;
    --surface: #ffffff;
    --border: #e5e2db;
    --text: #1a1a1a;
    --text-muted: #6b6b6b;
    --accent: #2563eb;
    --accent-light: #eff6ff;
    --new-badge: #16a34a;
    --tag-bg: #f1f0ed;
    --removed-bg: #fef2f2;
    --removed-border: #fecaca;
    --removed-text: #991b1b;
  }

  body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: var(--bg);
    color: var(--text);
    line-height: 1.5;
    min-height: 100vh;
  }

  header {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    padding: 1.5rem 2rem;
    position: sticky;
    top: 0;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
  }

  header h1 {
    font-size: 1.25rem;
    font-weight: 600;
    letter-spacing: -0.02em;
  }

  header p {
    font-size: 0.8rem;
    color: var(--text-muted);
  }

  .stats {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
  }

  .stat {
    text-align: center;
  }

  .stat-value {
    font-size: 1.4rem;
    font-weight: 700;
    letter-spacing: -0.03em;
    color: var(--accent);
  }

  .stat-label {
    font-size: 0.72rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
  }

  .section-title {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--text-muted);
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--border);
  }

  .section-title span {
    background: var(--new-badge);
    color: white;
    padding: 0.1rem 0.45rem;
    border-radius: 999px;
    font-size: 0.7rem;
    margin-left: 0.5rem;
    vertical-align: middle;
  }

  .grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.25rem;
    margin-bottom: 3rem;
  }

  .card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    transition: box-shadow 0.15s ease, transform 0.15s ease;
  }

  .card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    transform: translateY(-1px);
  }

  .card.is-new {
    border-color: #86efac;
    box-shadow: 0 0 0 1px #86efac;
  }

  .card.is-removed {
    border-color: var(--removed-border);
    background: var(--removed-bg);
    opacity: 0.7;
  }

  .card.is-removed .card-title,
  .card.is-removed .card-price {
    text-decoration: line-through;
    color: var(--text-muted);
  }

  .badge-removed {
    background: #fee2e2;
    color: var(--removed-text);
  }

  .card-image {
    width: 100%;
    height: 180px;
    object-fit: cover;
    display: block;
    background: var(--tag-bg);
  }

  .card-image-placeholder {
    width: 100%;
    height: 180px;
    background: var(--tag-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    font-size: 2rem;
  }

  .card-body {
    padding: 1rem;
  }

  .card-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    flex-wrap: wrap;
  }

  .badge {
    font-size: 0.68rem;
    font-weight: 600;
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }

  .badge-source {
    background: var(--tag-bg);
    color: var(--text-muted);
  }

  .badge-new {
    background: #dcfce7;
    color: #15803d;
  }

  .card-title {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 0.4rem;
    color: var(--text);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .card-price {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--accent);
    margin-bottom: 0.6rem;
    letter-spacing: -0.02em;
  }

  .card-price small {
    font-size: 0.75rem;
    font-weight: 400;
    color: var(--text-muted);
    margin-left: 0.25rem;
  }

  .card-tags {
    display: flex;
    gap: 0.4rem;
    flex-wrap: wrap;
    margin-bottom: 0.6rem;
  }

  .tag {
    background: var(--tag-bg);
    border-radius: 6px;
    padding: 0.2rem 0.5rem;
    font-size: 0.75rem;
    color: var(--text-muted);
  }

  .card-description {
    font-size: 0.78rem;
    color: var(--text-muted);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-bottom: 0.75rem;
  }

  .card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--border);
  }

  .card-date {
    font-size: 0.72rem;
    color: var(--text-muted);
  }

  .card-link {
    font-size: 0.78rem;
    font-weight: 500;
    color: var(--accent);
    text-decoration: none;
    padding: 0.3rem 0.7rem;
    background: var(--accent-light);
    border-radius: 6px;
    transition: background 0.1s;
  }

  .card-link:hover {
    background: #dbeafe;
  }

  .empty {
    text-align: center;
    color: var(--text-muted);
    padding: 3rem;
    font-size: 0.9rem;
  }

  .filters {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin-top: 0.5rem;
  }

  .filter-tag {
    background: var(--accent-light);
    color: var(--accent);
    border: 1px solid #bfdbfe;
    border-radius: 999px;
    padding: 0.15rem 0.6rem;
    font-size: 0.72rem;
    font-weight: 500;
  }

  @media (max-width: 600px) {
    header { padding: 1rem; }
    .container { padding: 1rem; }
    .grid { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>

<header>
  <div>
    <h1>Casas en venta · Bahía Blanca</h1>
    <p>Actualizado el {{ $generatedAt }}</p>
    <div class="filters">
      <span class="filter-tag">📍 Macrocentro</span>
      <span class="filter-tag">💰 USD 120.000 – 250.000</span>
      <span class="filter-tag">🛏 Mín. 3 dormitorios</span>
      <span class="filter-tag">🏠 Casa en venta</span>
    </div>
  </div>
  <div class="stats">
    <div class="stat">
      <div class="stat-value">{{ $newToday->count() }}</div>
      <div class="stat-label">Nuevas hoy</div>
    </div>
    <div class="stat">
      <div class="stat-value">{{ $properties->count() }}</div>
      <div class="stat-label">Total</div>
    </div>
    <div class="stat">
      <div class="stat-value">{{ $properties->whereNotNull('price_usd')->count() }}</div>
      <div class="stat-label">Con precio</div>
    </div>
  </div>
</header>

<div class="container">

  @if($newToday->count() > 0)
  <h2 class="section-title">
    Nuevas hoy
    <span>{{ $newToday->count() }}</span>
  </h2>
  <div class="grid">
    @foreach($newToday as $property)
      @include('partials.property-card', ['property' => $property, 'isNew' => true])
    @endforeach
  </div>
  @endif

  <h2 class="section-title">Todas las propiedades</h2>

  @if($properties->count() === 0)
    <div class="empty">No hay propiedades guardadas aún. Ejecutá <code>php artisan scrape:run</code> para comenzar.</div>
  @else
  <div class="grid">
    @foreach($properties as $property)
      @include('partials.property-card', ['property' => $property, 'isNew' => false])
    @endforeach
  </div>
  @endif

  @if($removed->count() > 0)
  <h2 class="section-title" style="margin-top: 2rem;">
    Eliminadas del sitio
    <span style="background: var(--removed-text);">{{ $removed->count() }}</span>
  </h2>
  <div class="grid">
    @foreach($removed as $property)
      @include('partials.property-card', ['property' => $property, 'isNew' => false])
    @endforeach
  </div>
  @endif

</div>

</body>
</html>
