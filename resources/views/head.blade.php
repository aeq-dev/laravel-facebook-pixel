@if ($enabled)
    <!-- Meta Pixel Code -->
    <script>
        ! function(f, b, e, v, n, t, s) {
            if (f.fbq) return;
            n = f.fbq = function() {
                n.callMethod ?
                    n.callMethod.apply(n, arguments) : n.queue.push(arguments)
            };
            if (!f._fbq) f._fbq = n;
            n.push = n;
            n.loaded = !0;
            n.version = '2.0';
            n.queue = [];
            t = b.createElement(e);
            t.async = !0;
            t.src = v;
            s = b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t, s)
        }(window, document, 'script',
            'https://connect.facebook.net/en_US/fbevents.js');
        @if ($userData)
            @if (count($pixelIds))
                @foreach ($pixelIds as $id)
                    fbq('init', '{{ $id }}', {
                        em: '{{ $userData['em'] }}',
                        external_id: {{ $userData['external_id'] }}
                    });
                @endforeach
            @else
                fbq('init', '{{ $pixelId }}', {
                    em: '{{ $userData['em'] }}',
                    external_id: {{ $userData['external_id'] }}
                });
            @endif
        @else
            @if (count($pixelIds))
                @foreach ($pixelIds as $id)
                    fbq('init', '{{ $id }}');
                @endforeach
            @else
                fbq('init', '{{ $pixelId }}');
            @endif
        @endif
        fbq('track', 'PageView');
    </script>

    @if (count($pixelIds))
        @foreach ($pixelIds as $id)
            <noscript>
                <img height="1" width="1" style="display:none"
                    src="https://www.facebook.com/tr?id={{ $id }}&ev=PageView&noscript=1" />
            </noscript>
        @endforeach
    @else
        <noscript>
            <img height="1" width="1" style="display:none"
                src="https://www.facebook.com/tr?id={{ $pixelId }}&ev=PageView&noscript=1" />
        </noscript>
    @endif

    <!-- End Meta Pixel Code -->
@endif
