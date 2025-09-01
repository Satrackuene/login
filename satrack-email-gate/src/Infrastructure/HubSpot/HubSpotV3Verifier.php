<?php
namespace Satrack\EmailGatePro\Infrastructure\HubSpot;

use Satrack\EmailGatePro\Domain\Contracts\HubSpotVerifierInterface;
use Satrack\EmailGatePro\Support\HttpClientInterface;
use Satrack\EmailGatePro\Support\LoggerInterface;

class HubSpotV3Verifier implements HubSpotVerifierInterface
{
  private HttpClientInterface $http;
  private LoggerInterface $log;
  public function __construct(HttpClientInterface $http, LoggerInterface $log)
  {
    $this->http = $http;
    $this->log = $log;
  }

  public function isAllowed(string $email, string $listId, string $token): bool
  {
    // 1) Obtener contacto por email
    $urlC = 'https://api.hubapi.com/crm/v3/objects/contacts/' . rawurlencode($email) . '?idProperty=email&properties=email';
    $resC = $this->http->get($urlC, [
      'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json'
      ],
      'timeout' => 8
    ]);
    if ($resC['code'] !== 200)
      return false;
    $contact = json_decode($resC['body'], true);
    $cid = $contact['id'] ?? null;
    if (!$cid)
      return false;

    // 2) List memberships (solo listas MANUAL/SNAPSHOT)
    $after = null;
    $guard = 0;
    do {
      $urlL = 'https://api.hubapi.com/crm/v3/lists/' . rawurlencode($listId) . '/memberships';
      $qs = ['limit' => 200];
      if ($after)
        $qs['after'] = $after;
      $url = add_query_arg($qs, $urlL);
      $res = $this->http->get($url, [
        'headers' => [
          'Authorization' => 'Bearer ' . $token,
          'Content-Type' => 'application/json',
          'Accept' => 'application/json'
        ],
        'timeout' => 10
      ]);
      if ($res['code'] !== 200)
        return false;
      $data = json_decode($res['body'], true);
      foreach (($data['results'] ?? []) as $row) {
        if (strval($row['recordId'] ?? '') === strval($cid))
          return true;
      }
      $after = $data['paging']['next']['after'] ?? null;
    } while ($after && ++$guard < 20);
    return false;
  }
}
