<?php
namespace Satrack\EmailGatePro\Domain\Security;

class TokenSigner
{
  public function sign(array $payload): string
  {
    $data = base64_encode(json_encode($payload));
    $sig = hash_hmac('sha256', $data, wp_salt('auth'));
    return $data . '.' . $sig;
  }
  public function verify(string $token): ?array
  {
    $parts = explode('.', $token);
    if (count($parts) !== 2)
      return null;
    [$data, $sig] = $parts;
    $calc = hash_hmac('sha256', $data, wp_salt('auth'));
    if (!hash_equals($calc, $sig))
      return null;
    $payload = json_decode(base64_decode($data), true);
    if (!is_array($payload))
      return null;
    if (isset($payload['exp']) && time() > $payload['exp'])
      return null;
    return $payload;
  }
}
