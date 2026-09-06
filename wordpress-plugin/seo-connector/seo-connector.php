<?php
/**
 * Plugin Name: SEO Connector
 * Description: SEO Aracı panelinin bu siteyi okumasına ve onaylanan SEO düzeltmelerini uygulamasına izin verir.
 * Version: 1.0.0
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 */
if (! defined('ABSPATH')) {
    exit;
}

define('SEOCONNECTOR_VERSION', '1.0.0');
define('SEOCONNECTOR_NAMESPACE', 'seoconnector/v1');
define('SEOCONNECTOR_TABLE', 'seoconnector_changes');

/* -------------------------------------------------------------------------
 * Activation: table that keeps the previous value of everything we change.
 * ---------------------------------------------------------------------- */

register_activation_hook(__FILE__, 'seoconnector_install');

function seoconnector_install()
{
    global $wpdb;

    $table = $wpdb->prefix.SEOCONNECTOR_TABLE;
    $charset = $wpdb->get_charset_collate();

    require_once ABSPATH.'wp-admin/includes/upgrade.php';

    dbDelta("CREATE TABLE {$table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        fix_id bigint(20) unsigned NOT NULL,
        object_id bigint(20) unsigned NOT NULL,
        object_type varchar(20) NOT NULL DEFAULT 'post',
        field varchar(40) NOT NULL,
        old_value longtext NULL,
        new_value longtext NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY (id),
        KEY fix_id (fix_id)
    ) {$charset};");
}

/* -------------------------------------------------------------------------
 * Settings screen
 * ---------------------------------------------------------------------- */

add_action('admin_menu', function () {
    add_options_page('SEO Connector', 'SEO Connector', 'manage_options', 'seo-connector', 'seoconnector_settings_page');
});

add_action('admin_init', function () {
    register_setting('seoconnector', 'seoconnector_panel_url');
    register_setting('seoconnector', 'seoconnector_project_id');
    register_setting('seoconnector', 'seoconnector_api_key');
});

function seoconnector_settings_page()
{
    if (isset($_GET['settings-updated'])) {
        $result = seoconnector_handshake();
        echo '<div class="notice notice-'.(is_wp_error($result) ? 'error' : 'success').'"><p>'
            .esc_html(is_wp_error($result) ? $result->get_error_message() : 'Panele bağlanıldı.')
            .'</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>SEO Connector</h1>
        <form method="post" action="options.php">
            <?php settings_fields('seoconnector'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="panel_url">Panel adresi</label></th>
                    <td><input id="panel_url" name="seoconnector_panel_url" type="url" class="regular-text"
                               value="<?php echo esc_attr(get_option('seoconnector_panel_url')); ?>"
                               placeholder="https://seo.ornek.com"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="project_id">Proje no</label></th>
                    <td><input id="project_id" name="seoconnector_project_id" type="number" class="small-text"
                               value="<?php echo esc_attr(get_option('seoconnector_project_id')); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="api_key">API anahtarı</label></th>
                    <td><input id="api_key" name="seoconnector_api_key" type="password" class="regular-text"
                               value="<?php echo esc_attr(get_option('seoconnector_api_key')); ?>"></td>
                </tr>
            </table>
            <?php submit_button('Kaydet ve bağlan'); ?>
        </form>
    </div>
    <?php
}

/* -------------------------------------------------------------------------
 * Handshake back to the panel
 * ---------------------------------------------------------------------- */

function seoconnector_handshake()
{
    $panel = untrailingslashit((string) get_option('seoconnector_panel_url'));
    $key = (string) get_option('seoconnector_api_key');
    $projectId = (int) get_option('seoconnector_project_id');

    if (! $panel || ! $key || ! $projectId) {
        return new WP_Error('seoconnector_config', 'Panel adresi, proje no ve API anahtarı zorunlu.');
    }

    $body = wp_json_encode([
        'project_id' => $projectId,
        'site_url' => untrailingslashit(home_url()),
        'plugin_version' => SEOCONNECTOR_VERSION,
        'wp_version' => get_bloginfo('version'),
        'seo_plugin' => seoconnector_detect_seo_plugin(),
        'capabilities' => ['meta', 'image-alt', 'rollback'],
    ]);

    $timestamp = time();

    $response = wp_remote_post($panel.'/api/wp/handshake', [
        'timeout' => 20,
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Seo-Timestamp' => (string) $timestamp,
            'X-Seo-Signature' => seoconnector_sign($key, 'POST', '/api/wp/handshake', $timestamp, $body),
        ],
        'body' => $body,
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    if (wp_remote_retrieve_response_code($response) !== 200) {
        return new WP_Error('seoconnector_http', 'Panel yanıtı: HTTP '.wp_remote_retrieve_response_code($response));
    }

    return true;
}

function seoconnector_detect_seo_plugin()
{
    if (defined('WPSEO_VERSION')) {
        return 'yoast';
    }

    if (class_exists('RankMath')) {
        return 'rankmath';
    }

    if (defined('SEOPRESS_VERSION')) {
        return 'seopress';
    }

    return 'none';
}

/* -------------------------------------------------------------------------
 * Signed REST API
 * ---------------------------------------------------------------------- */

function seoconnector_sign($secret, $method, $path, $timestamp, $body)
{
    $payload = implode("\n", [strtoupper($method), $path, (string) $timestamp, hash('sha256', (string) $body)]);

    return hash_hmac('sha256', $payload, $secret);
}

function seoconnector_verify(WP_REST_Request $request)
{
    $secret = (string) get_option('seoconnector_api_key');

    if (! $secret) {
        return new WP_Error('seoconnector_unconfigured', 'Eklenti yapılandırılmamış.', ['status' => 401]);
    }

    $timestamp = (int) $request->get_header('x_seo_timestamp');
    $signature = (string) $request->get_header('x_seo_signature');

    if (abs(time() - $timestamp) > 300) {
        return new WP_Error('seoconnector_stale', 'İstek zaman damgası geçersiz.', ['status' => 401]);
    }

    // The panel signs the full REST path, e.g. /wp-json/seoconnector/v1/meta.
    $path = '/wp-json'.$request->get_route();
    $expected = seoconnector_sign($secret, $request->get_method(), $path, $timestamp, $request->get_body());

    if (! hash_equals($expected, $signature)) {
        return new WP_Error('seoconnector_signature', 'İmza doğrulanamadı.', ['status' => 401]);
    }

    return true;
}

add_action('rest_api_init', function () {
    $auth = ['permission_callback' => 'seoconnector_verify'];

    register_rest_route(SEOCONNECTOR_NAMESPACE, '/ping', array_merge($auth, [
        'methods' => 'GET',
        'callback' => 'seoconnector_ping',
    ]));

    register_rest_route(SEOCONNECTOR_NAMESPACE, '/posts', array_merge($auth, [
        'methods' => 'GET',
        'callback' => 'seoconnector_posts',
    ]));

    register_rest_route(SEOCONNECTOR_NAMESPACE, '/meta', array_merge($auth, [
        'methods' => 'POST',
        'callback' => 'seoconnector_update_meta',
    ]));

    register_rest_route(SEOCONNECTOR_NAMESPACE, '/image-alt', array_merge($auth, [
        'methods' => 'POST',
        'callback' => 'seoconnector_update_alt',
    ]));

    register_rest_route(SEOCONNECTOR_NAMESPACE, '/rollback', array_merge($auth, [
        'methods' => 'POST',
        'callback' => 'seoconnector_rollback',
    ]));
});

function seoconnector_ping()
{
    return [
        'plugin_version' => SEOCONNECTOR_VERSION,
        'wp_version' => get_bloginfo('version'),
        'seo_plugin' => seoconnector_detect_seo_plugin(),
        'site_url' => untrailingslashit(home_url()),
        'capabilities' => ['meta', 'image-alt', 'rollback'],
    ];
}

function seoconnector_posts(WP_REST_Request $request)
{
    $types = array_filter(explode(',', (string) $request->get_param('type'))) ?: ['post', 'page'];

    $query = new WP_Query([
        'post_type' => $types,
        'post_status' => 'publish',
        'posts_per_page' => min(200, (int) ($request->get_param('per_page') ?: 100)),
        'paged' => max(1, (int) $request->get_param('page')),
    ]);

    $items = [];

    foreach ($query->posts as $post) {
        $items[] = [
            'id' => $post->ID,
            'type' => $post->post_type,
            'url' => get_permalink($post),
            'title' => get_the_title($post),
            'seo_title' => seoconnector_get_meta_value($post->ID, 'title'),
            'meta_description' => seoconnector_get_meta_value($post->ID, 'meta_description'),
        ];
    }

    return ['items' => $items, 'total' => (int) $query->found_posts];
}

/** Meta keys differ per SEO plugin; fall back to our own when none is active. */
function seoconnector_meta_key($field)
{
    $plugin = seoconnector_detect_seo_plugin();

    if ($plugin === 'yoast') {
        return $field === 'title' ? '_yoast_wpseo_title' : '_yoast_wpseo_metadesc';
    }

    if ($plugin === 'rankmath') {
        return $field === 'title' ? 'rank_math_title' : 'rank_math_description';
    }

    return $field === 'title' ? '_seoconnector_title' : '_seoconnector_description';
}

function seoconnector_get_meta_value($postId, $field)
{
    return (string) get_post_meta($postId, seoconnector_meta_key($field), true);
}

function seoconnector_post_id_from_url($url)
{
    $postId = url_to_postid($url);

    if ($postId) {
        return $postId;
    }

    // Front page and shop archives do not resolve through url_to_postid.
    $path = trim((string) wp_parse_url($url, PHP_URL_PATH), '/');

    if ($path === '') {
        return (int) get_option('page_on_front');
    }

    $page = get_page_by_path($path, OBJECT, ['post', 'page', 'product']);

    return $page ? $page->ID : 0;
}

function seoconnector_record_change($fixId, $objectId, $type, $field, $old, $new)
{
    global $wpdb;

    $wpdb->insert($wpdb->prefix.SEOCONNECTOR_TABLE, [
        'fix_id' => (int) $fixId,
        'object_id' => (int) $objectId,
        'object_type' => $type,
        'field' => $field,
        'old_value' => $old,
        'new_value' => $new,
        'created_at' => current_time('mysql'),
    ]);
}

function seoconnector_update_meta(WP_REST_Request $request)
{
    $body = $request->get_json_params();
    $postId = isset($body['object_id']) ? (int) $body['object_id'] : seoconnector_post_id_from_url($body['url'] ?? '');

    if (! $postId) {
        return new WP_Error('seoconnector_not_found', 'Sayfa bulunamadı: '.($body['url'] ?? ''), ['status' => 404]);
    }

    $field = ($body['field'] ?? '') === 'title' ? 'title' : 'meta_description';
    $key = seoconnector_meta_key($field);
    $old = (string) get_post_meta($postId, $key, true);
    $new = (string) ($body['value'] ?? '');

    update_post_meta($postId, $key, $new);
    seoconnector_record_change($body['fix_id'] ?? 0, $postId, 'post', $field, $old, $new);

    return ['ok' => true, 'object_id' => $postId, 'field' => $field];
}

function seoconnector_update_alt(WP_REST_Request $request)
{
    $body = $request->get_json_params();
    $attachmentId = isset($body['attachment_id'])
        ? (int) $body['attachment_id']
        : attachment_url_to_postid((string) ($body['url'] ?? ''));

    if (! $attachmentId) {
        return new WP_Error('seoconnector_not_found', 'Görsel bulunamadı.', ['status' => 404]);
    }

    $old = (string) get_post_meta($attachmentId, '_wp_attachment_image_alt', true);
    $new = (string) ($body['alt'] ?? '');

    update_post_meta($attachmentId, '_wp_attachment_image_alt', $new);
    seoconnector_record_change($body['fix_id'] ?? 0, $attachmentId, 'attachment', 'alt', $old, $new);

    return ['ok' => true, 'attachment_id' => $attachmentId];
}

function seoconnector_rollback(WP_REST_Request $request)
{
    global $wpdb;

    $body = $request->get_json_params();
    $fixId = (int) ($body['fix_id'] ?? 0);
    $table = $wpdb->prefix.SEOCONNECTOR_TABLE;

    $change = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE fix_id = %d ORDER BY id DESC LIMIT 1", $fixId));

    if (! $change) {
        return new WP_Error('seoconnector_not_found', 'Geri alınacak değişiklik yok.', ['status' => 404]);
    }

    $key = $change->field === 'alt'
        ? '_wp_attachment_image_alt'
        : seoconnector_meta_key($change->field);

    update_post_meta((int) $change->object_id, $key, $change->old_value);

    return ['ok' => true, 'restored' => $change->field];
}

/* -------------------------------------------------------------------------
 * Output our own meta when no SEO plugin is installed
 * ---------------------------------------------------------------------- */

add_action('wp_head', function () {
    if (seoconnector_detect_seo_plugin() !== 'none' || ! is_singular()) {
        return;
    }

    $description = seoconnector_get_meta_value(get_the_ID(), 'meta_description');

    if ($description) {
        echo '<meta name="description" content="'.esc_attr($description).'">'."\n";
    }
}, 1);

add_filter('pre_get_document_title', function ($title) {
    if (seoconnector_detect_seo_plugin() !== 'none' || ! is_singular()) {
        return $title;
    }

    $custom = seoconnector_get_meta_value(get_the_ID(), 'title');

    return $custom ?: $title;
});
