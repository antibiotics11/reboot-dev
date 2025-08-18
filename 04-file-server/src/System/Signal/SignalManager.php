<?php

namespace RebootDev\FileServer\System\Signal;
use RuntimeException;
use function pcntl_async_signals, pcntl_signal, pcntl_alarm;
use const SIG_DFL, SIG_IGN;

class SignalManager {
  private static self $manager;
  public static function getManager(): self {
    self::$manager ??= new self();
    return self::$manager;
  }

  private(set) bool $asyncEnabled = false;

  private function __construct() {}

  public function enableAsync(bool $enable = true): void {
    if (!$this->asyncEnabled) {
      pcntl_async_signals($enable);
      $this->asyncEnabled = true;
    }
  }

  public function setHandler(Signal $signal, ?callable $handler = null): void {
    if ($signal === Signal::SIGKILL || $signal === Signal::SIGSTOP) {
      return;
    }
    if (!pcntl_signal($signal->value, $handler ?? SIG_DFL)) {
      throw new RuntimeException("pcntl_signal() failed for " . $signal->name);
    }
  }

  public function ignore(Signal $signal): void {
    if ($signal === Signal::SIGKILL || $signal === Signal::SIGSTOP) {
      return;
    }
    if (!pcntl_signal($signal->value, SIG_IGN)) {
      throw new RuntimeException("pcntl_signal() failed for " . $signal->name);
    }
  }

  public function alarm(int $seconds): int {
    return pcntl_alarm($seconds);
  }

}