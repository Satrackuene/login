<?php
namespace Satrack\EmailGatePro\Infrastructure\WordPress\Admin;

class PostAccessMetaBox
{
  public function register(): void
  {
    add_action('add_meta_boxes', [$this, 'addBoxes']);
    add_action('save_post', [$this, 'save']);
    add_action('template_redirect', [$this, 'maybeRedirect']);
  }

  public function addBoxes(): void
  {
    foreach (get_post_types(['public' => true]) as $type) {
      add_meta_box(
        'segp_require_login',
        __('Requerir login', 'satrack-egp'),
        [$this, 'render'],
        $type,
        'side'
      );
    }
  }

  public function render($post): void
  {
    $val = (bool) get_post_meta($post->ID, '_segp_require_login', true);
    wp_nonce_field('segp_require_login', 'segp_require_login_nonce');
    echo '<label><input type="checkbox" name="segp_require_login" ' . checked($val, true, false) . ' /> ' . esc_html__('Requerir login', 'satrack-egp') . '</label>';
  }

  public function save(int $post_id): void
  {
    if (!isset($_POST['segp_require_login_nonce']) || !wp_verify_nonce($_POST['segp_require_login_nonce'], 'segp_require_login')) {
      return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return;
    }
    if (isset($_POST['segp_require_login'])) {
      update_post_meta($post_id, '_segp_require_login', 1);
    } else {
      delete_post_meta($post_id, '_segp_require_login');
    }
  }

  private function canView(): bool
  {
    if (current_user_can('manage_options')) {
      return true;
    }
    $cookie = $_COOKIE['satrack_egp_token'] ?? '';
    if (!$cookie) {
      return is_user_logged_in();
    }
    $parts = explode('.', $cookie);
    if (count($parts) !== 2) {
      return false;
    }
    $sig = hash_hmac('sha256', $parts[0], wp_salt('auth'));
    if (!hash_equals($sig, $parts[1])) {
      return false;
    }
    $payload = json_decode(base64_decode($parts[0]), true);
    if (!$payload || empty($payload['exp']) || time() > $payload['exp']) {
      return false;
    }
    return true;
  }

  public function maybeRedirect(): void
  {
    if (!is_singular()) {
      return;
    }
    $post_id = get_queried_object_id();
    if (!$post_id) {
      return;
    }
    if (!get_post_meta($post_id, '_segp_require_login', true)) {
      return;
    }
    if ($this->canView()) {
      return;
    }
    $opt = get_option('satrack_egp_options', []);
    $url = !empty($opt['gate_page']) ? get_permalink((int) $opt['gate_page']) : home_url('/');
    wp_redirect($url);
    exit;
  }
}
