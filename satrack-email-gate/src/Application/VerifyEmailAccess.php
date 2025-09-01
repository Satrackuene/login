<?php
namespace Satrack\EmailGatePro\Application;

use Satrack\EmailGatePro\Support\Config;
use Satrack\EmailGatePro\Domain\Security\RateLimiter;
use Satrack\EmailGatePro\Domain\Security\AccessCookieManager;
use Satrack\EmailGatePro\Infrastructure\HubSpot\HubSpotV1Verifier;
use Satrack\EmailGatePro\Infrastructure\HubSpot\HubSpotV3Verifier;
use Satrack\EmailGatePro\Support\LoggerInterface;

class VerifyEmailAccess
{
  private Config $config;
  private RateLimiter $rate;
  private AccessCookieManager $cookie;
  private HubSpotV1Verifier $v1;
  private HubSpotV3Verifier $v3;
  private LoggerInterface $log;

  public function __construct(Config $config, RateLimiter $rate, AccessCookieManager $cookie, HubSpotV1Verifier $v1, HubSpotV3Verifier $v3, LoggerInterface $log)
  {
    $this->config = $config;
    $this->rate = $rate;
    $this->cookie = $cookie;
    $this->v1 = $v1;
    $this->v3 = $v3;
    $this->log = $log;
  }

  public function handle(string $email): array
  {
    $email = sanitize_email($email);
    if (!is_email($email)) {
      return [false, __('Correo inválido', 'satrack-egp')];
    }

    $max = (int) ($this->config->get('rate', 10));
    if (!$this->rate->hit(max(3, $max))) {
      return [false, __('Demasiados intentos, inténtalo más tarde.', 'satrack-egp')];
    }

    $token = (string) $this->config->get('token', '');
    $listId = (string) $this->config->get('list_id', '');
    $mode = (string) $this->config->get('mode', 'v1');
    if (!$token || !$listId) {
      return [false, __('Plugin no configurado.', 'satrack-egp')];
    }

    // Filtro opcional por dominios
    $domains = trim((string) $this->config->get('allowed_domains', ''));
    if ($domains !== '') {
      $okDomain = false;
      $parts = array_map('trim', explode(',', $domains));
      foreach ($parts as $d) {
        if ($d && preg_match('/@' . preg_quote($d, '/') . '$/i', $email)) {
          $okDomain = true;
          break;
        }
      }
      if (!$okDomain) {
        return [false, __('Dominio de correo no permitido.', 'satrack-egp')];
      }
    }

    // Cache 12h por email+modo+lista
    $cacheKey = 'satrack_egp_allow_' . md5(strtolower($email) . '|' . $listId . '|' . $mode);
    $cached = get_transient($cacheKey);
    $allowed = ($cached === '1');

    if (!$allowed) {
      $allowed = ($mode === 'v1') ? $this->v1->isAllowed($email, $listId, $token) : $this->v3->isAllowed($email, $listId, $token);
      if ($allowed)
        set_transient($cacheKey, '1', 12 * HOUR_IN_SECONDS);
    }

    if (!$allowed)
      return [false, __('Tu correo no está autorizado para este contenido.', 'satrack-egp')];

    $ttl = (int) $this->config->get('cookie_ttl', 24);
    $this->cookie->issue($email, max(1, $ttl));

    // Login opcional como visitor
    if ((bool) $this->config->get('login_as_visitor', false)) {
      $this->loginVisitor($email);
    }

    return [true, __('Acceso concedido', 'satrack-egp')];
  }

  private function loginVisitor(string $email): void
  {
    $user = get_user_by('email', $email);
    if (!$user) {
      $base = sanitize_user(preg_replace('/@.*/', '', $email), true) ?: 'visitor';
      $u = $base;
      $i = 1;
      while (username_exists($u)) {
        $u = $base . $i++;
      }
      $id = wp_insert_user([
        'user_login' => $u,
        'user_pass' => wp_generate_password(20, true, true),
        'user_email' => $email,
        'role' => 'visitor'
      ]);
      if (!is_wp_error($id)) {
        $user = get_user_by('id', $id);
      }
    } else {
      if (!in_array('visitor', (array) $user->roles, true) && !user_can($user, 'manage_options')) {
        $user->set_role('visitor');
      }
    }
    if ($user) {
      wp_clear_auth_cookie();
      wp_set_current_user($user->ID);
      wp_set_auth_cookie($user->ID, false, is_ssl());
    }
  }
}
