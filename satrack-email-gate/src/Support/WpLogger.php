<?php
namespace Satrack\EmailGatePro\Support;

class WpLogger implements LoggerInterface
{
  private string $channel;
  public function __construct(string $channel)
  {
    $this->channel = $channel;
  }
  public function info(string $msg, array $ctx = []): void
  {
    error_log("[{$this->channel}] INFO: $msg " . json_encode($ctx));
  }
  public function error(string $msg, array $ctx = []): void
  {
    error_log("[{$this->channel}] ERROR: $msg " . json_encode($ctx));
  }
}