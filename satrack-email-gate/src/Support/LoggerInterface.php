<?php
namespace Satrack\EmailGatePro\Support;

interface LoggerInterface
{
  public function info(string $msg, array $ctx = []): void;
  public function error(string $msg, array $ctx = []): void;
}