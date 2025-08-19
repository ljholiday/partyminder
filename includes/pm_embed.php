<?php
// /wp-content/plugins/partyminder/includes/pm_embed.php
if (!defined('ABSPATH')) { exit; }

function pm_first_url_in_text(string $text): ?string {
    // Simple and tolerant URL matcher
    if (preg_match('~\bhttps?://[^\s<>"\']+~i', $text, $m)) {
        return $m[0];
    }
    return null;
}

function pm_is_local_url(string $url): bool {
    $home = parse_url(home_url(), PHP_URL_HOST);
    $target = parse_url($url, PHP_URL_HOST);
    return $home && $target && strtolower($home) === strtolower($target);
}

function pm_build_embed_from_local_product(int $post_id): ?array {
    if (get_post_type($post_id) !== 'product') {
        return null;
    }
    $title = get_the_title($post_id);
    $desc  = wp_strip_all_tags(get_post_field('post_excerpt', $post_id) ?: get_post_field('post_content', $post_id));
    $desc  = wp_trim_words($desc, 40, '...');
    $image = get_the_post_thumbnail_url($post_id, 'large');
    $url   = get_permalink($post_id);

    if (!$image) {
        // Try WooCommerce product image via attachment
        $thumb_id = get_post_thumbnail_id($post_id);
        if ($thumb_id) {
            $image = wp_get_attachment_image_url($thumb_id, 'large');
        }
    }

    if (!$image) { return null; }

    return [
        'title' => $title ?: '',
        'description' => $desc ?: '',
        'image' => $image,
        'url' => $url,
        'source' => 'local-product',
        'fetched_at' => time(),
    ];
}

function pm_fetch_og_embed(string $url): ?array {
    // Cache first
    $key = 'pm_og_' . md5($url);
    $cached = get_transient($key);
    if (is_array($cached)) { return $cached; }

    // Quick, polite fetch
    $resp = wp_remote_get($url, [
        'timeout' => 4,
        'redirection' => 3,
        'user-agent' => 'PartyminderBot/1.0 (+'.home_url().')',
    ]);

    if (is_wp_error($resp)) { return null; }
    $code = wp_remote_retrieve_response_code($resp);
    if ($code < 200 || $code >= 400) { return null; }

    $html = wp_remote_retrieve_body($resp);
    if (!$html) { return null; }

    // Parse OG tags
    $title = null;
    $desc  = null;
    $image = null;

    // Cheap meta parse to avoid full DOM cost
    if (preg_match('/<meta\s+property=["\']og:title["\']\s+content=["\']([^"\']+)["\']/i', $html, $m)) { $title = $m[1]; }
    if (preg_match('/<meta\s+property=["\']og:description["\']\s+content=["\']([^"\']+)["\']/i', $html, $m)) { $desc = $m[1]; }
    if (preg_match('/<meta\s+property=["\']og:image(?::secure_url)?["\']\s+content=["\']([^"\']+)["\']/i', $html, $m)) { $image = $m[1]; }

    // Fallbacks
    if (!$title && preg_match('/<title>\s*(.*?)\s*<\/title>/si', $html, $m)) { $title = wp_strip_all_tags($m[1]); }
    if (!$desc && preg_match('/<meta\s+name=["\']description["\']\s+content=["\']([^"\']+)["\']/i', $html, $m)) { $desc = $m[1]; }

    // Must have image per your requirement
    if (!$image) { return null; }

    // Normalize to absolute URL if needed
    $image = pm_make_absolute_url($image, $url);

    $data = [
        'title' => $title ? wp_strip_all_tags($title) : '',
        'description' => $desc ? wp_strip_all_tags($desc) : '',
        'image' => esc_url_raw($image),
        'url' => esc_url_raw($url),
        'source' => 'og',
        'fetched_at' => time(),
    ];

    // Cache 7 days
    set_transient($key, $data, DAY_IN_SECONDS * 7);

    return $data;
}

function pm_make_absolute_url(string $maybe_relative, string $base): string {
    // Absolute already
    if (preg_match('~^https?://~i', $maybe_relative)) {
        return $maybe_relative;
    }
    $b = wp_parse_url($base);
    if (!$b || empty($b['scheme']) || empty($b['host'])) { return $maybe_relative; }
    $scheme = $b['scheme'];
    $host   = $b['host'];
    $port   = isset($b['port']) ? ':' . $b['port'] : '';
    $path   = isset($b['path']) ? rtrim(dirname($b['path']), '/') : '';
    if (strpos($maybe_relative, '/') === 0) {
        return "{$scheme}://{$host}{$port}{$maybe_relative}";
    }
    return "{$scheme}://{$host}{$port}{$path}/{$maybe_relative}";
}

function pm_build_embed_from_url(string $url): ?array {
    // Prefer local Woo product path (no network)
    if (pm_is_local_url($url)) {
        $post_id = url_to_postid($url);
        if ($post_id) {
            $local = pm_build_embed_from_local_product($post_id);
            if ($local) { return $local; }
        }
    }
    // Else OG fetch
    return pm_fetch_og_embed($url);
}

function pm_render_embed_card(array $embed): string {
    $title = esc_html($embed['title'] ?? '');
    $desc  = esc_html($embed['description'] ?? '');
    $img   = esc_url($embed['image'] ?? '');
    $url   = esc_url($embed['url'] ?? '');

    // Require image as per request
    if (!$img || !$url) { return ''; }

    ob_start(); ?>
    <div class="pm-embed" data-pm-source="<?php echo esc_attr($embed['source'] ?? ''); ?>">
      <a class="pm-embed__imagewrap" href="<?php echo $url; ?>" target="_blank" rel="nofollow noopener">
        <img class="pm-embed__image" src="<?php echo $img; ?>" alt="" loading="lazy" decoding="async" />
      </a>
      <div class="pm-embed__body">
        <?php if ($title) : ?>
          <a class="pm-embed__title" href="<?php echo $url; ?>" target="_blank" rel="nofollow noopener"><?php echo $title; ?></a>
        <?php endif; ?>
        <?php if ($desc) : ?>
          <div class="pm-embed__desc"><?php echo $desc; ?></div>
        <?php endif; ?>
        <div class="pm-embed__meta"><a href="<?php echo $url; ?>" target="_blank" rel="nofollow noopener" class="pm-embed__link"><?php echo parse_url($url, PHP_URL_HOST); ?></a></div>
      </div>
    </div>
    <?php
    return trim(ob_get_clean());
}