@props([
    'src',
    'alt' => '',
    'class' => '',
    'width' => null,
    'height' => null,
    'placeholder' => null,
])

@php
    // Generate a low-quality placeholder if not provided
    $placeholderSrc = $placeholder ?? 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . ($width ?? 400) . ' ' . ($height ?? 300) . '"%3E%3Crect width="100%25" height="100%25" fill="%23f3f4f6"/%3E%3C/svg%3E';
@endphp

<img 
    src="{{ $placeholderSrc }}"
    data-src="{{ $src }}"
    alt="{{ $alt }}"
    class="lazy {{ $class }}"
    @if($width) width="{{ $width }}" @endif
    @if($height) height="{{ $height }}" @endif
    loading="lazy"
    {{ $attributes }}
>

@once
    @push('scripts')
    <script>
        // Lazy loading implementation
        document.addEventListener('DOMContentLoaded', function() {
            const lazyImages = document.querySelectorAll('img.lazy');
            
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            img.classList.add('loaded');
                            imageObserver.unobserve(img);
                        }
                    });
                }, {
                    rootMargin: '50px 0px',
                    threshold: 0.01
                });

                lazyImages.forEach(img => imageObserver.observe(img));
            } else {
                // Fallback for browsers that don't support IntersectionObserver
                lazyImages.forEach(img => {
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                });
            }
        });
    </script>
    @endpush

    @push('styles')
    <style>
        img.lazy {
            opacity: 0;
            transition: opacity 0.3s;
        }
        img.lazy.loaded {
            opacity: 1;
        }
    </style>
    @endpush
@endonce
