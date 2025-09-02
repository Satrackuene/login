<?php
namespace Satrack\EmailGatePro\Infrastructure\WordPress\Admin;

use Satrack\EmailGatePro\Support\Config;

class SettingsPage
{
  private Config $config;
  public function __construct(Config $config)
  {
    $this->config = $config;
  }

  public function register(): void
  {
    add_action('admin_menu', [$this, 'menu']);
    add_action('admin_init', [$this, 'settings']);
  }

  public function menu(): void
  {
    add_options_page('Satrack Email Gate Pro', 'Satrack Email Gate Pro', 'manage_options', 'satrack-egp', [$this, 'render']);
  }

  public function settings(): void
  {
    register_setting($this->config->key(), $this->config->key(), [$this, 'sanitize']);
    add_settings_section('segp_main', 'Configuración', '__return_false', $this->config->key());

    $this->addField('token', 'HubSpot Private App Token', function ($o) {
      printf(
        "<input type='password' name='%s[token]' value='%s' style='width:420px' autocomplete='new-password' />",
        esc_attr($this->config->key()),
        esc_attr($o['token'] ?? '')
      );
    });

    $this->addField('cookie_ttl', 'TTL acceso (horas)', function ($o) {
      printf(
        "<input type='number' min='1' name='%s[cookie_ttl]' value='%d' style='width:90px' />",
        esc_attr($this->config->key()),
        (int) ($o['cookie_ttl'] ?? 24)
      );
    });

    $this->addField('gate_page', 'Página del formulario', function ($o) {
      wp_dropdown_pages([
        'name' => $this->config->key() . '[gate_page]',
        'show_option_none' => '-- Seleccionar --',
        'option_none_value' => 0,
        'selected' => (int) ($o['gate_page'] ?? 0)
      ]);
      echo '<p class="description">Crea una página con <code>[email_gate_form]</code> y selecciónala aquí para redirigir.</p>';
    });

    $this->addField('rate', 'Rate limit (intentos/10 min)', function ($o) {
      printf(
        "<input type='number' min='3' name='%s[rate]' value='%d' style='width:90px' />",
        esc_attr($this->config->key()),
        (int) ($o['rate'] ?? 10)
      );
    });

    $this->addField('login_as_visitor', 'Crear sesión WP como visitor', function ($o) {
      $v = !empty($o['login_as_visitor']);
      printf(
        "<label><input type='checkbox' name='%s[login_as_visitor]' %s /> Habilitar</label>",
        esc_attr($this->config->key()),
        checked($v, true, false)
      );
    });

    $this->addField('allowed_domains', 'Dominios permitidos (coma)', function ($o) {
      printf(
        "<input type='text' name='%s[allowed_domains]' value='%s' style='width:420px' placeholder='empresa.com, aliado.com' />",
        esc_attr($this->config->key()),
        esc_attr($o['allowed_domains'] ?? '')
      );
    });
  }

  private function addField(string $id, string $label, callable $cb): void
  {
    add_settings_field($id, $label, function () use ($cb) {
      $cb(get_option($this->config->key(), []));
    }, $this->config->key(), 'segp_main');
  }

  public function sanitize(array $raw): array
  {
    $clean = [];
    $clean['token'] = trim($raw['token'] ?? '');
    $clean['cookie_ttl'] = max(1, (int) ($raw['cookie_ttl'] ?? 24));
    $clean['gate_page'] = (int) ($raw['gate_page'] ?? 0);
    $clean['rate'] = max(3, (int) ($raw['rate'] ?? 10));
    $clean['login_as_visitor'] = !empty($raw['login_as_visitor']) ? 1 : 0;
    $clean['allowed_domains'] = trim((string) ($raw['allowed_domains'] ?? ''));
    return $clean;
  }

  public function render(): void
  {
    echo '<div class="wrap"><h1>Satrack Email Gate Pro</h1><form method="post" action="options.php">';
    settings_fields($this->config->key());
    do_settings_sections($this->config->key());
    submit_button();
    echo '<hr><p><strong>Shortcodes:</strong> <code>[email_gate_form]</code> y <code>[email_gate_protect]Contenido[/email_gate_protect]</code></p>';
    echo '</form></div>';
  }
}