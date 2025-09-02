<?php
namespace Satrack\EmailGatePro\Infrastructure\WordPress\Shortcodes;

use Satrack\EmailGatePro\Domain\Security\TokenSigner; // (no se usa directamente)

class ProtectShortcode
{
  public function register(): void
  {
    add_shortcode('email_gate_protect', [$this, 'render']);
  }
  public function render($atts, $content = '')
  {
    if ($this->canView()) {
      return do_shortcode($content);
    }
    $opt = get_option('satrack_egp_options', []);
    $url = !empty($opt['gate_page']) ? get_permalink((int) $opt['gate_page']) : '';
    $cta = $url ? ' <a href="' . esc_url($url) . '">' . esc_html__('Ir al formulario de acceso', SEGP_DOMAIN) . '</a>' : '';
    return '<div class="segp-locked">' . esc_html__('Contenido restringido.', SEGP_DOMAIN) . $cta . '</div>';
  }
  private function canView(): bool
  {
    if (current_user_can('manage_options'))
      return true;
    $cookie = $_COOKIE['satrack_egp_token'] ?? '';
    if (!$cookie)
      return is_user_logged_in(); // si se habilitó login visitor
    $parts = explode('.', $cookie);
    if (count($parts) !== 2)
      return false;
    $sig = hash_hmac('sha256', $parts[0], wp_salt('auth'));
    if (!hash_equals($sig, $parts[1]))
      return false;
    $payload = json_decode(base64_decode($parts[0]), true);
    if (!$payload || empty($payload['exp']) || time() > $payload['exp'])
      return false;
    return true;
  }
}