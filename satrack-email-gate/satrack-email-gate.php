<?php
/**
 * Plugin Name:  Satrack Email Gate Pro
 * Description:  Restringe contenido validando la propiedad "access-ctr-forma-clientes" en HubSpot. Opción de inicio de sesión con rol "visitor" sin acceso a /wp-admin.
 * Version:      2.1.0
 * Author:       Satrack
 * License:      GPLv2 or later
 * Text Domain:  satrack-egp
 */


if (!defined('ABSPATH')) {
  exit;
}

define('SEGP_VERSION', '2.1.0');
define('SEGP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SEGP_PLUGIN_URL', plugin_dir_url(__FILE__));

// --- PSR-4 Autoloader simple ---
spl_autoload_register(function ($class) {
  $prefix = 'Satrack\\EmailGatePro\\';
  $base_dir = __DIR__ . '/src/';
  $len = strlen($prefix);
  if (strncmp($prefix, $class, $len) !== 0)
    return;
  $relative_class = substr($class, $len);
  $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
  if (is_readable($file))
    require $file;
});

use Satrack\EmailGatePro\Support\Config;
use Satrack\EmailGatePro\Support\WordPressHttpClient;
use Satrack\EmailGatePro\Support\WpLogger;
use Satrack\EmailGatePro\Domain\Security\TokenSigner;
use Satrack\EmailGatePro\Domain\Security\RateLimiter;
use Satrack\EmailGatePro\Domain\Security\AccessCookieManager;
use Satrack\EmailGatePro\Infrastructure\HubSpot\HubSpotPropertyVerifier;
use Satrack\EmailGatePro\Support\AccessLogger;
use Satrack\EmailGatePro\Application\VerifyEmailAccess;
use Satrack\EmailGatePro\Infrastructure\WordPress\Admin\SettingsPage;
use Satrack\EmailGatePro\Infrastructure\WordPress\Rest\VerificationController;
use Satrack\EmailGatePro\Infrastructure\WordPress\Shortcodes\FormShortcode;
use Satrack\EmailGatePro\Infrastructure\WordPress\Shortcodes\ProtectShortcode;
use Satrack\EmailGatePro\Infrastructure\WordPress\Users\VisitorLoginManager;

// --- Activación / Desactivación ---
register_activation_hook(__FILE__, function () {
  add_role('visitor', 'Visitor', ['read' => true]);
});
register_deactivation_hook(__FILE__, function () {
  // Si quieres eliminar el rol en desactivación, descomenta:
  // remove_role('visitor');
});

// --- Service Container muy simple ---
class SEG_Container
{
  private array $services = [];
  public function set(string $id, $service)
  {
    $this->services[$id] = $service;
  }
  public function get(string $id)
  {
    return $this->services[$id] ?? null;
  }
}

add_action('plugins_loaded', function () {
  $c = new SEG_Container();

  // Infraestructura base
  $config = new Config('satrack_egp_options');
  $http = new WordPressHttpClient();
  $log = new WpLogger('satrack-egp');

  $signer = new TokenSigner();
  $cookie = new AccessCookieManager($signer, 'satrack_egp_token');
  $rate = new RateLimiter('satrack_egp_rl_', 10 * MINUTE_IN_SECONDS);

  $c->set(Config::class, $config);
  $c->set(WordPressHttpClient::class, $http);
  $c->set(WpLogger::class, $log);
  $c->set(TokenSigner::class, $signer);
  $c->set(AccessCookieManager::class, $cookie);
  $c->set(RateLimiter::class, $rate);

  $accessLog = new AccessLogger();
  $c->set(AccessLogger::class, $accessLog);

  $verifier = new HubSpotPropertyVerifier($http, $log);
  $c->set(HubSpotPropertyVerifier::class, $verifier);
  // Caso de uso
  $usecase = new VerifyEmailAccess($config, $rate, $cookie, $verifier, $accessLog, $log);
  $c->set(VerifyEmailAccess::class, $usecase);

  // WordPress: UI + REST + Shortcodes + Visitante
  (new SettingsPage($config))->register();
  (new VerificationController($config, $usecase))->register();
  (new FormShortcode($config))->register();
  (new ProtectShortcode())->register();

  // Reglas del rol visitor (sin admin bar, sin dashboard)
  (new VisitorLoginManager($config))->registerGuards();

  // Guardar contenedor global si deseas recuperarlo
  $GLOBALS['satrack_egp_container'] = $c;
});