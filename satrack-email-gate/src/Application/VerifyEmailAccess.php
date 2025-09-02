<?php
namespace Satrack\EmailGatePro\Application;

use Satrack\EmailGatePro\Support\Config;
use Satrack\EmailGatePro\Domain\Security\RateLimiter;
use Satrack\EmailGatePro\Domain\Security\AccessCookieManager;
use Satrack\EmailGatePro\Infrastructure\HubSpot\HubSpotPropertyVerifier;
use Satrack\EmailGatePro\Support\AccessLogger;
use Satrack\EmailGatePro\Support\LoggerInterface;

class VerifyEmailAccess
{
  private Config $config;
  private RateLimiter $rate;
  private AccessCookieManager $cookie;
  private HubSpotPropertyVerifier $verifier;
  private AccessLogger $accessLog;
  private LoggerInterface $log;

  public function __construct(Config $config, RateLimiter $rate, AccessCookieManager $cookie, HubSpotPropertyVerifier $verifier, AccessLogger $accessLog, LoggerInterface $log)
  {
    $this->config = $config;
    $this->rate = $rate;
    $this->cookie = $cookie;
    $this->verifier = $verifier;
    $this->accessLog = $accessLog;
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
    if (!$token) {
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

    // Cache 12h por email
    $cacheKey = 'satrack_egp_allow_' . md5(strtolower($email));
    $cached = get_transient($cacheKey);
    $allowed = ($cached === '1');

    if (!$allowed) {
      $allowed = $this->verifier->hasAccess($email, $token);

      if ($allowed) {
        set_transient($cacheKey, '1', 12 * HOUR_IN_SECONDS);
      }
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!$allowed) {
      $this->log->info('Access denied', ['email' => $email, 'ip' => $ip]);
      return [false, __('Tu correo no está autorizado para este contenido.', 'satrack-egp'), $allowed];
    }

    $ttl = (int) $this->config->get('cookie_ttl', 24);
    $this->cookie->issue($email, max(1, $ttl));

    // Login opcional como visitor
    if ((bool) $this->config->get('login_as_visitor', false)) {
      $this->loginVisitor($email);
    }

    if ((bool) $this->config->get('enable_log', false)) {
      $this->accessLog->log($email, $ip);
    }
    $this->log->info('Access granted', ['email' => $email, 'ip' => $ip, 'session_hours' => max(1, $ttl)]);

    return [true, __('Acceso concedido', 'satrack-egp'), $allowed];
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
