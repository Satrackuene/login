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
        __('Requerir login', SEGP_DOMAIN),
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
    echo '<label><input type="checkbox" name="segp_require_login" ' . checked($val, true, false) . ' /> ' . esc_html__('Requerir login', SEGP_DOMAIN) . '</label>';
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
    return is_user_logged_in();
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
    $gate = !empty($opt['gate_page']) ? get_permalink((int) $opt['gate_page']) : '';
    if ($gate) {
      $url = add_query_arg('redirect_to', rawurlencode(get_permalink($post_id)), $gate);
    } else {
      $url = wp_login_url(get_permalink($post_id));
    }
    wp_redirect($url);
    exit;
  }
}
