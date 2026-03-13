<article class="card {{ $isNew ? 'is-new' : '' }} {{ ($property->removed_at) ? 'is-removed' : '' }}">
  @if($property->image_url)
    <img class="card-image" src="{{ $property->image_url }}" alt="{{ $property->title }}" loading="lazy">
  @else
    <div class="card-image-placeholder">🏠</div>
  @endif

  <div class="card-body">
    <div class="card-meta">
      <span class="badge badge-source">{{ $property->source }}</span>
      @if($isNew)
        <span class="badge badge-new">Nueva</span>
      @endif
      @if($property->removed_at)
        <span class="badge badge-removed">Eliminada</span>
      @endif
    </div>

    <h3 class="card-title">{{ $property->title }}</h3>

    <div class="card-price">
      @if($property->price_usd)
        USD {{ number_format($property->price_usd, 0, ',', '.') }}
      @elseif($property->price_raw)
        {{ $property->price_raw }}
      @else
        <span style="color: var(--text-muted); font-weight: 400; font-size: 0.85rem;">Precio a consultar</span>
      @endif
    </div>

    <div class="card-tags">
      @if($property->bedrooms)
        <span class="tag">🛏 {{ $property->bedrooms }} dorm.</span>
      @endif
      @if($property->bathrooms)
        <span class="tag">🚿 {{ $property->bathrooms }} baños</span>
      @endif
      @if($property->area_m2)
        <span class="tag">📐 {{ number_format($property->area_m2, 0) }} m²</span>
      @endif
      @if($property->neighborhood)
        <span class="tag">📍 {{ Str::limit($property->neighborhood, 30) }}</span>
      @endif
    </div>

    @if($property->description)
      <p class="card-description">{{ $property->description }}</p>
    @endif

    <div class="card-footer">
      <span class="card-date">
        @if($property->removed_at)
          Eliminada {{ $property->removed_at->diffForHumans() }}
        @elseif($property->first_seen_at)
          Visto {{ $property->first_seen_at->diffForHumans() }}
        @else
          Guardado {{ $property->created_at->diffForHumans() }}
        @endif
      </span>
      <a href="{{ $property->url }}" target="_blank" rel="noopener" class="card-link">Ver →</a>
    </div>
  </div>
</article>
