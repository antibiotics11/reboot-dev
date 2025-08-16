<?php

namespace RebootDev\SocketServer;
use Throwable;

class TcpServer {
  private ?TcpSocket $serverSocket;

  /** @var TcpSocket[] */
  private array $clientSockets;
  private array $handleErrors;
  private bool  $closed;

  public function __construct() {
    $this->reset();
  }

  private function reset(): void {
    $this->serverSocket  = null;
    $this->clientSockets = [];
    $this->handleErrors  = [];
    $this->closed      = true;
  }

  private function sendDataToClient(
    int|string|array|null|false $sendData,
    TcpSocket                   $clientSocket
  ): void {
    if ($sendData === null || $sendData === false) {
      return;
    }

    if (!is_array($sendData)) {
      $sendData = [ (string)$sendData ];
    }
    foreach ($sendData as $chunk) {
      $clientSocket->write($chunk, strlen($chunk));
    }
  }

  public function open(TcpSocket $socket): void {
    $this->serverSocket = $socket;
    $this->closed     = false;
    $this->serverSocket->listen();
  }

  public function handle(?callable $onNewConnection = null, ?callable $onDataReceived = null): void {
    $this->clientSockets = [];

    while (!$this->closed) {
      $readSockets   = array_merge([ $this->serverSocket ], $this->clientSockets);
      $writeSockets  = null;
      $exceptSockets = null;

      try {
        $count = TcpSocket::select($readSockets, $writeSockets, $exceptSockets);
        if ($count === 0) {
          continue;
        }

        if (in_array($this->serverSocket, $readSockets, true)) {
          $newClient     = $this->serverSocket->accept();
          $newClientName = $newClient->getPeerName();
          $this->clientSockets[] = $newClient;

          if ($onNewConnection !== null) {
            $this->sendDataToClient($onNewConnection($newClientName, $this), $newClient);
          }
        }

        foreach ($this->clientSockets as $key => $client) {
          if (in_array($client, $readSockets, true)) {
            $receivedData = $client->read();
            $clientName   = $client->getPeerName();

            if (strlen($receivedData) === 0) {
              $client->close();
              unset($this->clientSockets[$key]);
              Continue;
            }

            $options = [
              "keep-alive" => true,
              "broadcast"  => false
            ];
            if ($onDataReceived !== null) {
              $result = $onDataReceived($receivedData, $clientName, $options, $this);
              $this->sendDataToClient($result, $client);

              if ($options["broadcast"]) {
                foreach ($this->clientSockets as $peer) {
                  if ($peer !== $client && !$peer->isClosed()) {
                    $this->sendDataToClient($result, $peer);
                  }
                }
              }

              if (!$options["keep-alive"]) {
                $client->close();
                unset($this->clientSockets[$key]);
              }
            }
          }
        }
      } catch (Throwable $e) {
        $this->handleErrors[] = $e;
      }
    }
  }

  public function close(): void {
    foreach ($this->clientSockets as $clientSocket) {
      $clientSocket->close();
    }
    $this->serverSocket?->close();
    $this->reset();
  }

  public function isClosed(): bool {
    return $this->closed;
  }

  public function getLastHandleError(): ?Throwable {
    return array_pop($this->handleErrors);
  }

  public function __destruct() {
    $this->close();
  }
}