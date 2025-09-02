<?php
namespace Satrack\EmailGatePro\Infrastructure\WordPress\Shortcodes;

class LogoutUrlShortcode
{
  public function register(): void
  {
    add_shortcode('segp_logout_url', [$this, 'render']);
  }

  public function render(): string
  {
    $opt = get_option('satrack_egp_options', []);
    $url = !empty($opt['gate_page']) ? get_permalink((int) $opt['gate_page']) : '';
    return esc_url(wp_logout_url($url));
  }
}