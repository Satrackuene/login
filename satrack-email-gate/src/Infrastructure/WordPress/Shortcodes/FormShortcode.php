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
    $redirect = '';
    if (!empty($_GET['redirect_to'])) {
      $redirect = esc_url_raw(wp_unslash($_GET['redirect_to']));
    } else {
      $rid = (int) $this->config->get('redirect_page', 0);
      if ($rid) {
        $redirect = get_permalink($rid);
      }
    }
    $ops = ['+', '-', 'x'];
    do {
      $a = random_int(1, 20);
      $b = random_int(1, 20);
      $op = $ops[array_rand($ops)];
      if ($op === '-' && $a < $b) {
        [$a, $b] = [$b, $a];
      }
      switch ($op) {
        case '-':
          $expected = $a - $b;
          break;
        case 'x':
          $expected = $a * $b;
          break;
        default:
          $expected = $a + $b;
      }
    } while ($expected < 0 || $expected > 40);
    $words = [
      1 => __('uno', SEGP_DOMAIN),
      2 => __('dos', SEGP_DOMAIN),
      3 => __('tres', SEGP_DOMAIN),
      4 => __('cuatro', SEGP_DOMAIN),
      5 => __('cinco', SEGP_DOMAIN),
      6 => __('seis', SEGP_DOMAIN),
      7 => __('siete', SEGP_DOMAIN),
      8 => __('ocho', SEGP_DOMAIN),
      9 => __('nueve', SEGP_DOMAIN),
      10 => __('diez', SEGP_DOMAIN),
      11 => __('once', SEGP_DOMAIN),
      12 => __('doce', SEGP_DOMAIN),
      13 => __('trece', SEGP_DOMAIN),
      14 => __('catorce', SEGP_DOMAIN),
      15 => __('quince', SEGP_DOMAIN),
      16 => __('dieciséis', SEGP_DOMAIN),
      17 => __('diecisiete', SEGP_DOMAIN),
      18 => __('dieciocho', SEGP_DOMAIN),
      19 => __('diecinueve', SEGP_DOMAIN),
      20 => __('veinte', SEGP_DOMAIN)
    ];
    $showA = random_int(0, 1) === 1 ? $a : $words[$a];
    $showB = random_int(0, 1) === 1 ? $b : $words[$b];
    $question = sprintf(__('¿Cuánto es %s %s %s?', SEGP_DOMAIN), esc_html($showA), esc_html($op), esc_html($showB));
    ob_start(); ?>
    <form id="segp-form" class="segp-form" data-endpoint="<?php echo esc_attr($endpoint); ?>"
      data-nonce="<?php echo esc_attr($nonce); ?>"
      data-success="<?php echo esc_attr(__('Acceso concedido. Recargando…', SEGP_DOMAIN)); ?>" <?php if ($redirect) { ?>
        data-redirect="<?php echo esc_attr($redirect); ?>" <?php } ?>>
      <label for="segp-email"
        class="segp-label"><?php _e('Ingresa tu correo inscrito para acceder', SEGP_DOMAIN); ?></label>
      <input id="segp-email" type="email" required autocomplete="off" placeholder="tucorreo@dominio.com"
        class="segp-input" />
      <label for="segp-captcha" class="segp-label"><?php echo $question; ?></label>
      <input id="segp-captcha" type="text" autocomplete="off" required class="segp-input" />
      <input type="hidden" id="segp-a" value="<?php echo esc_attr($a); ?>" />
      <input type="hidden" id="segp-b" value="<?php echo esc_attr($b); ?>" />
      <input type="hidden" id="segp-op" value="<?php echo esc_attr($op); ?>" />
      <button id="segp-btn" class="segp-btn"><?php _e('Acceder', SEGP_DOMAIN); ?></button>
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