<?php

namespace RebootDev\FileServer\Network\Exception;
use Socket;
use RuntimeException, Throwable;
use function socket_last_error, socket_strerror;
use function sprintf;
use function in_array;
use const SOCKET_EAGAIN, SOCKET_EWOULDBLOCK, SOCKET_EINTR;

final class SocketError extends RuntimeException {
  public function toLogString(): string {
    return sprintf("[%d] %s", $this->getCode(), $this->getMessage());
  }

  public function isRetryable(): bool {
    return in_array($this->getCode(), [ SOCKET_EAGAIN, SOCKET_EWOULDBLOCK, SOCKET_EINTR]);
  }

  public function isNotError(): bool {
    return $this->getCode() === 0;
  }

  public static function fromCode(int $code, ?Throwable $previous = null): self {
    return new self(socket_strerror($code), $code, $previous);
  }

  public static function fromRawSocket(Socket $socket, ?Throwable $previous = null): self {
    return self::fromCode(socket_last_error($socket), $previous);
  }
}