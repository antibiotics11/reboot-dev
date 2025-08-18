<?php

namespace RebootDev\FileServer;
use InvalidArgumentException, RuntimeException;
use function date, time;
use function sprintf;
use const DATE_RFC2822;
use const STDOUT;

final class Logger {
  /** @var string[] */
  private array $buffer = [];

  public function __construct(
    private $outputStream = STDOUT
  ) {}

  public function __destruct() {
    $this->close();
  }

  public function format(string $message, bool $ln = true, string $dateFormat = DATE_RFC2822, ?int $timestamp = null): string {
    return sprintf("[%s] %s%s", date($dateFormat, $timestamp ?? time()), $message, $ln ? PHP_EOL : "");
  }

  public function log(string $message, bool $ln = true, string $dateFormat = DATE_RFC2822, ?int $timestamp = null): void {
    if (!is_resource($this->outputStream)) {
      throw new InvalidArgumentException("Invalid stream.");
    }

    $this->buffer[] = $this->format($message, $ln, $dateFormat, $timestamp);
    foreach ($this->buffer as $key => $log) {
      $bytesWritten = fwrite($this->outputStream, $log);
      if ($bytesWritten === false) {
        throw new RuntimeException("");
      }
      if ($bytesWritten > 0) {
        fflush($this->outputStream);
        unset($this->buffer[$key]);
      }
    }

    $this->buffer = array_values($this->buffer);
  }

  public function close(): void {
    if (is_resource($this->outputStream) && $this->outputStream !== STDOUT) {
      @fflush($this->outputStream);
      fclose($this->outputStream);
    }
  }
}