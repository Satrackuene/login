<?php
namespace Satrack\EmailGatePro\Infrastructure\WordPress\Rest;

use Satrack\EmailGatePro\Support\Config;
use Satrack\EmailGatePro\Application\VerifyEmailAccess;
use WP_REST_Request;
use WP_Error;

class VerificationController
{
  private Config $config;
  private VerifyEmailAccess $usecase;
  const NS = 'email-gate-pro/v1';
  public function __construct(Config $config, VerifyEmailAccess $usecase)
  {
    $this->config = $config;
    $this->usecase = $usecase;
  }
  public function register(): void
  {
    add_action('rest_api_init', function () {
      register_rest_route(self::NS, '/verify', [
        'methods' => 'POST',
        'callback' => [$this, 'verify'],
        'permission_callback' => function () {
          //return wp_verify_nonce($_SERVER['HTTP_X_WP_NONCE'] ?? '', 'wp_rest'); }
          return true;
        }
      ]);
    });
  }
  public function verify(WP_REST_Request $req)
  {
    $a = (int) $req->get_param('a');
    $b = (int) $req->get_param('b');
    $op = $req->get_param('op');
    $captcha = (int) $req->get_param('captcha');
    switch ($op) {
      case '-':
        $expected = $a - $b;
        break;
      case 'x':
      case 'X':
        $expected = $a * $b;
        break;
      case '+':
        $expected = $a + $b;
        break;
      default:
        return new WP_Error('segp_captcha', __('Captcha incorrecto', SEGP_DOMAIN), ['status' => 403]);
    }
    if ($expected < 0 || $expected > 40) {
      return new WP_Error('segp_captcha', __('Captcha incorrecto', SEGP_DOMAIN), ['status' => 403]);
    }
    if ($captcha !== $expected) {
      return new WP_Error('segp_captcha', __('Captcha incorrecto', SEGP_DOMAIN), ['status' => 403]);
    }
    $email = $req->get_param('email');

    [$ok, $msg, $data] = $this->usecase->handle((string) $email);
    if ($ok)
      return rest_ensure_response(['ok' => true]);
    return new WP_Error('segp_denied', $msg, ['status' => ($msg === 'Plugin no configurado.' ? 500 : 403), $data]);
  }
}