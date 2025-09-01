<?php
namespace Satrack\EmailGatePro\Support;

interface HttpClientInterface
{
  public function get(string $url, array $args = []): array; // returns [code=>int, body=>string, error=>string|null]
}