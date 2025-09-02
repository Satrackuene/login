<?php
namespace Satrack\EmailGatePro\Infrastructure\WordPress\Shortcodes;

use Satrack\EmailGatePro\Support\Config;
use Satrack\EmailGatePro\Infrastructure\WordPress\Rest\VerificationController;

class FormShortcode
{
  private Config $config;
  public function __construct(Config $config)
  {
    $this->config = $config;
  }
  public function register(): void
  {
    add_shortcode('email_gate_form', [$this, 'render']);
    add_action('wp_enqueue_scripts', [$this, 'assets']);
  }
  public function assets(): void
  {
    wp_register_script('segp-form', SEGP_PLUGIN_URL . 'assets/js/form.js', [], null, true);
  }
  public function render(): string
  {
    wp_enqueue_script('segp-form');
    $nonce = wp_create_nonce('wp_rest');
    $endpoint = esc_url_raw(rest_url(VerificationController::NS . '/verify'));
    ob_start(); ?>
    <form id="segp-form" class="segp-form" data-endpoint="<?php echo esc_attr($endpoint); ?>"
      data-nonce="<?php echo esc_attr($nonce); ?>"
      data-success="<?php echo esc_attr(__('Acceso concedido. Recargando…', 'satrack-egp')); ?>">
      <label for="segp-email"
        class="segp-label"><?php _e('Ingresa tu correo corporativo para acceder', 'satrack-egp'); ?></label>
      <input id="segp-email" type="email" required placeholder="tucorreo@empresa.com" class="segp-input" />
      <button id="segp-btn" class="segp-btn"><?php _e('Acceder', 'satrack-egp'); ?></button>
      <p id="segp-msg" class="segp-msg" role="alert" aria-live="polite"></p>
    </form>
    <style>
      .segp-form {
        max-width: 440px;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 10px
      }

      .segp-label {
        display: block;
        margin-bottom: 6px
      }

      .segp-input {
        width: 100%;
        padding: 10px;
        margin-bottom: 10px
      }

      .segp-btn {
        padding: 10px 14px;
        cursor: pointer
      }

      .segp-msg {
        margin-top: 8px
      }
    </style>
    <?php
    return (string) ob_get_clean();
  }
}