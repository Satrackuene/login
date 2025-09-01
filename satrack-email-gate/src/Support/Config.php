<?php
namespace Satrack\EmailGatePro\Support;

class Config
{
  private string $optionKey;
  public function __construct(string $optionKey)
  {
    $this->optionKey = $optionKey;
  }

  public function get(string $key, $default = null)
  {
    $o = get_option($this->optionKey, []);
    return $o[$key] ?? $default;
  }
  public function set(string $key, $value): void
  {
    $o = get_option($this->optionKey, []);
    $o[$key] = $value;
    update_option($this->optionKey, $o, false);
  }
  public function key(): string
  {
    return $this->optionKey;
  }
}