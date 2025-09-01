<?php
namespace Satrack\EmailGatePro\Domain\Security;

class AccessCookieManager
{
  private TokenSigner $signer;
  private string $cookieName;
  public function __construct(TokenSigner $signer, string $cookieName)
  {
    $this->signer = $signer;
    $this->cookieName = $cookieName;
  }

  public function issue(string $email, int $ttlHours): void
  {
    $exp = time() + ($ttlHours * HOUR_IN_SECONDS);
    $token = $this->signer->sign(['e' => strtolower($email), 'exp' => $exp]);
    setcookie($this->cookieName, $token, [
      'expires' => $exp,
      'path' => '/',
      'secure' => is_ssl(),
      'httponly' => true,
      'samesite' => 'Lax'
    ]);
  }
  public function valid(): bool
  {
    if (empty($_COOKIE[$this->cookieName]))
      return false;
    return $this->signer->verify($_COOKIE[$this->cookieName]) !== null;
  }
}