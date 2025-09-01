<?php
namespace Satrack\EmailGatePro\Support;

class WordPressHttpClient implements HttpClientInterface
{
  public function get(string $url, array $args = []): array
  {
    $res = wp_remote_get($url, $args);
    if (is_wp_error($res)) {
      return ['code' => 0, 'body' => '', 'error' => $res->get_error_message()];
    }
    return [
      'code' => (int) wp_remote_retrieve_response_code($res),
      'body' => (string) wp_remote_retrieve_body($res),
      'error' => null
    ];
  }
}