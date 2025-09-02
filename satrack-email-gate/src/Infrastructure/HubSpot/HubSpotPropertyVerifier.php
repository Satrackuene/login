<?php
namespace Satrack\EmailGatePro\Infrastructure\HubSpot;

use Satrack\EmailGatePro\Support\HttpClientInterface;
use Satrack\EmailGatePro\Support\LoggerInterface;

class HubSpotPropertyVerifier
{
  private HttpClientInterface $http;
  private LoggerInterface $log;
  public function __construct(HttpClientInterface $http, LoggerInterface $log)
  {
    $this->http = $http;
    $this->log = $log;
  }

  /**
   * Returns true if the HubSpot contact has the access property enabled.
   */
  public function hasAccess(string $email, string $token): bool
  {
    $url = 'https://api.hubapi.com/crm/v3/objects/contacts/' . rawurlencode($email) . '?idProperty=email&properties=access_log_cf,email';

    $res = $this->http->get($url, [
      'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json'
      ],
      'timeout' => 8
    ]);

    if ($res['code'] !== 200 && $res['code'] !== 404) {
      $this->log->error('HubSpot contact request failed', ['email' => $email, 'code' => $res['code']]);
      return false;
    } elseif ($res['code'] === 404) {
      // Contacto no existe
      return false;
    }
    $data = json_decode($res['body'], true);
    $prop = $data['properties']['access_log_cf'] ?? null;
    return filter_var($prop, FILTER_VALIDATE_BOOLEAN);
  }
}