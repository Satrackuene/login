<?php
namespace Satrack\EmailGatePro\Infrastructure\WordPress\Users;

use Satrack\EmailGatePro\Support\Config;

class VisitorLoginManager
{
  private Config $config;
  public function __construct(Config $config)
  {
    $this->config = $config;
  }
  public function registerGuards(): void
  {
    // Ocultar admin bar a visitantes
    add_action('init', function () {
      if (is_user_logged_in() && current_user_can('read') && !current_user_can('manage_options')) {
        show_admin_bar(false);
      }
    });
    // Bloquear dashboard
    add_action('admin_init', function () {
      if (is_user_logged_in() && current_user_can('read') && !current_user_can('manage_options')) {
        if (!wp_doing_ajax() && !defined('REST_REQUEST')) {
          wp_redirect(home_url('/'));
          exit;
        }
      }
    });
  }
}
