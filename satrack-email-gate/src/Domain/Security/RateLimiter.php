<?php
namespace Satrack\EmailGatePro\Domain\Security;

class RateLimiter
{
  private string $prefix;
  private int $ttl;
  public function __construct(string $prefix, int $ttl)
  {
    $this->prefix = $prefix;
    $this->ttl = $ttl;
  }
  public function hit(int $max): bool
  {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $key = $this->prefix . md5($ip);
    $c = (int) get_transient($key);
    if ($c >= $max)
      return false;
    set_transient($key, $c + 1, $this->ttl);
    return true;
  }
}