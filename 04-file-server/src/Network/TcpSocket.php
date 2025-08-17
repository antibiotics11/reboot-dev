<?php

namespace RebootDev\FileServer\Network;
use RebootDev\FileServer\Network\Exception\SocketError;
use Socket;
use stdClass;
use RuntimeException, Throwable;
use function socket_last_error;

class TcpSocket {
  public const int   DEFAULT_BACKLOG_SIZE = 128;
  public const int   DEFAULT_READ_LENGTH  = 65535;
  public const float DEFAULT_TIMEOUT      = 1.0;

  public static function create(int $addressFamily = AF_INET): self {
    try {
      $socket = socket_create($addressFamily, SOCK_STREAM, SOL_TCP);
      if ($socket === false) {
        throw SocketError::fromCode(socket_last_error());
      }
    } catch (Throwable $e) {
      throw new RuntimeException("Failed to create socket.", 0, $e);
    }
    return new self($socket);
  }

  public function setOption(int $level, int $option, mixed $value): void {
    if ($this->isClosed()) {
      throw new RuntimeException("Socket is closed.");
    }

    try {
      $result = socket_set_option($this->socket, $level, $option, $value);
      if (!$result) {
        throw SocketError::fromRawSocket($this->socket);
      }
    } catch (Throwable $e) {
      throw new RuntimeException("Failed to set socket option", 0, $e);
    }
  }

  public function getOption(int $level, int $option): int|array {
    if ($this->isClosed()) {
      throw new RuntimeException("Socket is closed.");
    }

    try {
      $result = socket_get_option($this->socket, $level, $option);
      if ($result === false) {
        throw SocketError::fromRawSocket($this->socket);
      }
      return $result;
    } catch (Throwable $e) {
      throw new RuntimeException("Failed to get socket option.", 0, $e);
    }
  }

  public function getSockName(): stdClass {
    if ($this->isClosed()) {
      throw new RuntimeException("Socket is closed.");
    }

    try {
      $result = socket_getsockname($this->getRawSocket(), $localAddress, $localPort);
      if ($result === false) {
        throw SocketError::fromRawSocket($this->getRawSocket());
      }

      $name = new stdClass;
      $name->address = $localAddress;
      $name->port    = $localPort;
      $name->name    = $localAddress . ":" . $localPort;
      return $name;
    } catch (Throwable $e) {
      throw new RuntimeException("Failed to get socket name.", 0, $e);
    }
  }

  public function getPeerName(): stdClass {
    if ($this->isClosed()) {
      throw new RuntimeException("Socket is closed.");
    }

    try {
      $result = socket_getpeername($this->getRawSocket(), $peerAddress, $peerPort);
      if ($result === false) {
        throw SocketError::fromRawSocket($this->getRawSocket());
      }

      $name = new stdClass;
      $name->address = $peerAddress;
      $name->port    = $peerPort;
      $name->name    = $peerAddress . ":" . $peerPort;
      return $name;
    } catch (Throwable $e) {
      throw new RuntimeException("Failed to get peer name.", 0, $e);
    }
  }

  public function bindTo(string $address, int $port = 0): void {
    if ($this->isClosed()) {
      throw new RuntimeException("Socket is closed.");
    }

    try {
      $result = socket_bind($this->socket, $address, $port);
      if ($result === false) {
        throw SocketError::fromRawSocket($this->socket);
      }
    } catch (Throwable $e) {
      throw new RuntimeException("Failed to bind socket.", 0, $e);
    }
  }

  public function listen(int $backlogQueueSize = self::DEFAULT_BACKLOG_SIZE): void {
    if ($this->isClosed()) {
      throw new RuntimeException("Socket is closed.");
    }

    try {
      $result = socket_listen($this->socket, $backlogQueueSize);
      if ($result === false) {
        throw SocketError::fromRawSocket($this->socket);
      }
    } catch (Throwable $e) {
      throw new RuntimeException("Failed to listen on socket.", 0, $e);
    }
  }

  public function accept(): self {
    if ($this->isClosed()) {
      throw new RuntimeException("Socket is closed.");
    }

    try {
      $result = socket_accept($this->socket);
      if ($result === false) {
        throw SocketError::fromRawSocket($this->socket);
      }
      return new self($result);
    } catch (Throwable $e) {
      throw new RuntimeException("Failed to accept socket.", 0, $e);
    }
  }

  /**
   * @param TcpSocket[]|null $readSockets
   * @param TcpSocket[]|null $writeSockets
   * @param TcpSocket[]|null $exceptSockets
   * @param float            $timeout
   * @return int
   */
  public static function select(
    ?array &$readSockets   = null,
    ?array &$writeSockets  = null,
    ?array &$exceptSockets = null,
    float  $timeout        = self::DEFAULT_TIMEOUT
  ): int {

    $build = static function (array $sockets, array &$rawSockets, array &$socketMap): void {
      foreach ($sockets as $socket) {
        $rawSocket = $socket->getRawSocket();
        $id = spl_object_id($rawSocket);
        $socketMap[$id]  = $socket;
        $rawSockets[$id] = $rawSocket;
      }
    };

    $remap = static function (?array &$sockets, array $selectedSockets, array $socketMap): void {
      if ($sockets === null) {
        return;
      }

      $sockets = [];
      foreach (array_keys($selectedSockets) as $id) {
        if (isset($socketMap[$id])) {
          $sockets[] = $socketMap[$id];
        }
      }
    };

    $readRaw = $writeRaw = $exceptRaw = [];
    $readMap = $writeMap = $exceptMap = [];
    $build($readSockets ?? [],   $readRaw,   $readMap);
    $build($writeSockets ?? [],  $writeRaw,  $writeMap);
    $build($exceptSockets ?? [], $exceptRaw, $exceptMap);


    $timeoutSec = (int)$timeout;
    $timeoutUs  = (int)(($timeout - $timeoutSec) * 1_000_000);

    try {
      $result = socket_select($readRaw, $writeRaw, $exceptRaw, $timeoutSec, $timeoutUs);
      if ($result === false) {
        throw SocketError::fromCode(socket_last_error());
      }

      $remap($readSockets,   $readRaw,   $readMap);
      $remap($writeSockets,  $writeRaw,  $writeMap);
      $remap($exceptSockets, $exceptRaw, $exceptMap);

      return $result;
    } catch (Throwable $e) {
      throw new RuntimeException("Failed to select socket.", 0, $e);
    }
  }

  public function read(int $length = self::DEFAULT_READ_LENGTH, int $mode = PHP_BINARY_READ): string {
    if ($this->isClosed()) {
      throw new RuntimeException("Socket is closed.");
    }

    try {
      $result = socket_read($this->socket, $length, $mode);
      if ($result === false) {
        throw SocketError::fromRawSocket($this->socket);
      }
      return $result;
    } catch (Throwable $e) {
      throw new RuntimeException("Failed to read from socket.", 0, $e);
    }
  }

  public function write(string $data, ?int $length = null): int {
    if ($this->isClosed()) {
      throw new RuntimeException("Socket is closed.");
    }

    $totalLength = $length ?? strlen($data);
    $totalSent = 0;
    while ($totalSent < $totalLength) {
      try {
        $result = socket_write($this->socket, substr($data, $totalSent), $totalLength - $totalSent);
        if ($result === false) {
          throw SocketError::fromRawSocket($this->socket);
        }
        if ($result === 0) {
          throw new RuntimeException("socket_write returned 0.");
        }
        $totalSent += $result;
      } catch (Throwable $e) {
        throw new RuntimeException("Failed to write to socket.", 0, $e);
      }
    }

    return $totalSent;
  }

  public function close(): void {
    if ($this->isClosed()) {
      return;
    }

    try {
      socket_close($this->socket);
    } catch (Throwable) {}
    $this->socket = null;
  }

  public function isClosed(): bool {
    return $this->socket === null;
  }

  public function getRawSocket(): Socket {
    if ($this->isClosed()) {
      throw new RuntimeException("Socket is closed.");
    }

    return $this->socket;
  }

  private function __construct(
    private ?Socket $socket = null
  ) {}

  public function __destruct() {
    if (!$this->isClosed()) {
      try {
        $this->close();
      } catch (Throwable) {}
    }
  }
}