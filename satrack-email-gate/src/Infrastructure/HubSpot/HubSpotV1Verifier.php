<?php
namespace Satrack\EmailGatePro\Infrastructure\HubSpot;

use Satrack\EmailGatePro\Domain\Contracts\HubSpotVerifierInterface;
use Satrack\EmailGatePro\Support\HttpClientInterface;
use Satrack\EmailGatePro\Support\LoggerInterface;

class HubSpotV1Verifier implements HubSpotVerifierInterface
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
    $url = 'https://api.hubapi.com/contacts/v1/contact/email/' . rawurlencode($email) . '/profile?showListMemberships=true';
    $res = $this->http->get($url, [
      'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json'
      ],
      'timeout' => 8
    ]);
    if ($res['error'] || $res['code'] === 0) {
      $this->log->error('HubSpot v1 error', ['err' => $res['error']]);
      return false;
    }
    if ($res['code'] !== 200) {
      return false;
    }
    $data = json_decode($res['body'], true);
    foreach (($data['list-memberships'] ?? []) as $m) {
      $id = $m['static-list-id'] ?? ($m['listId'] ?? null);
      if ($id !== null && strval($id) === strval($listId))
        return true;
    }
    return false;
  }
}