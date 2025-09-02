<?php
namespace Satrack\EmailGatePro\Support;

class AccessLogger
{
  private string $file;

  public function __construct()
  {
    $upload = wp_upload_dir();
    $dir = trailingslashit($upload['basedir']) . 'satrack-egp';
    if (!is_dir($dir)) {
      wp_mkdir_p($dir);
    }
    $this->file = $dir . '/access.log';
  }

  public function log(string $email, string $ip, int $hours): void
  {
    $line = sprintf("%s\t%s\t%s\t%d\n", current_time('mysql'), $ip, $email, $hours);
    file_put_contents($this->file, $line, FILE_APPEND);
  }
}
