<?php

namespace RebootDev\FileServer\Network;
use RebootDev\FileServer\Network\Exception\SocketError;
use InvalidArgumentException, RuntimeException, Throwable;

class BlockingTcpServer {
  protected ?TcpSocket $serverSocket = null;
  protected bool       $closed       = true;
  protected array      $handleErrors = [];

  public function open(TcpSocket $serverSocket): void {
    try {
      $this->serverSocket = $serverSocket;
      $this->handleErrors = [];
      $this->serverSocket->listen();
      $this->closed = false;
    } catch (Throwable $e) {
      $this->closed = true;
      throw new InvalidArgumentException("Unavailable socket.", 0, $e);
    }
  }

  public function handle(?callable $onNewConnection = null): void {
    if ($this->isClosed()) {
      throw new RuntimeException("Server is not yet opened.");
    }

    while (!$this->isClosed()) {
      try {
        $clientSocket = $this->serverSocket->accept();
      } catch (Throwable $e) {
        if ($e instanceof SocketError && $e->isRetryable()) {
          continue;
        }
        $this->handleErrors[] = $e;
        continue;
      }

      try {
        $onNewConnection($clientSocket, $this);
      } catch (Throwable $e) {
        $this->handleErrors[] = $e;
        try {
          $clientSocket->close();
        } catch (Throwable) {}
      }
    }
  }

  public function isClosed(): bool {
    return $this->closed;
  }

  public function close(): void {
    $this->serverSocket?->close();
    $this->serverSocket = null;
    $this->handleErrors = [];
    $this->closed = true;
  }

  public function getLastHandleError(): ?Throwable {
    return array_pop($this->handleErrors);
  }

  public function __destruct() {
    $this->isClosed() or $this->close();
  }
}