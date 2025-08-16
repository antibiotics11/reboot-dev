<?php

require_once(__DIR__ . "/src/Autoloader.php");
use RebootDev\SocketServer\{Autoloader, TcpServer, TcpSocket};

function put(string $message): void {
  printf("[%s] %s%s", date(DATE_RFC2822, time()), $message, PHP_EOL);
};

function sys(string $message, ?int $timestamp = null): string {
  $timestamp ??= time();
  return sprintf("[%s] [sys] %s\r\n", date(DATE_RFC2822, $timestamp), $message);
}

function chat(string $nickname, string $message, ?int $timestamp = null): string {
  $timestamp ??= time();
  return sprintf("[%s] <%s> %s\r\n", date(DATE_RFC2822, $timestamp), $nickname, trim($message));
}

function id(string $uniqueString): string {
  return hash("crc32", $uniqueString);
}

Autoloader::register();

$socket = TcpSocket::create();
$socket->setOption(SOL_SOCKET, SO_REUSEADDR, 1);
$socket->setOption(SOL_SOCKET, SO_REUSEPORT, 1);
$socket->setOption(SOL_SOCKET, SO_KEEPALIVE, 1);
$socket->setOption(SOL_SOCKET, SO_RCVTIMEO,  [ "sec" => 10, "usec" => 0 ]);
$socket->setOption(SOL_SOCKET, SO_SNDTIMEO,  [ "sec" => 10, "usec" => 0 ]);
$socket->setOption(SOL_TCP,    TCP_NODELAY,  1);
$socket->bindTo("127.0.0.1", 5001);

/** @var stdClass[] $sessions */
$sessions = [];
const COMMAND = [ "NICK" => "/nick", "WHO" => "/who", "QUIT" => "/quit" ];

$server = new TcpServer;
put("Starting chat server...");

pcntl_async_signals(true);
pcntl_signal(SIGINT, function () use (&$server): never {
  put("Closing socket server...");
  $server->close();
  exit(0);
});

$server->open($socket);
$server->handle(
  static function (stdClass $clientName) use (&$sessions): string {
    put(sprintf("New connection accepted from [%s].", $clientName->name));

    $session = new stdClass;
    $sessionId = id($clientName->name);
    $session->nickname = "guest-" . $sessionId;
    $session->peername = $clientName->name;
    $sessions[$sessionId] = $session;

    return sys(sprintf("Welcome user %s.", $session->nickname));
  },
  static function (string $receivedData, stdClass $clientName, array &$options) use (&$sessions): string {
    $receivedDataSize = strlen($receivedData);
    $receivedDataSize = rtrim($receivedDataSize, "\r\n");

    $sessionId = id($clientName->name);
    if (!isset($sessions[$sessionId])) {
      put(sprintf("%d bytes received from unknown host [%s].", $receivedDataSize, $clientName->name));
      $options["broadcast"]  = false;
      $options["keep-alive"] = false;
      return sys("Expired or non-existent session.");
    }

    put(sprintf("%d bytes received from [%s].", $receivedDataSize, $clientName->name));

    $exploded = explode(" ", $receivedData);
    $command  = trim(strtolower($exploded[0] ?? ""));
    array_shift($exploded);
    $value    = implode(" ", $exploded);

    if (in_array($command, COMMAND, true)) {
      $options["keep-alive"] = true;
      $options["broadcast"]  = false;

      switch ($command) {
        case COMMAND["NICK"] :
          $newNickname = trim($value);
          if (strlen($newNickname) === 0) {
            return sys("Usage: /nick <name>");
          }
          $sessions[$sessionId]->nickname = $newNickname;

          put(sprintf("User %s[%s] changed nickname.", $sessionId, $clientName->name));
          return sys("Successfully changed nickname.");

        case COMMAND["WHO"] :
          $onlineUsers = "";
          foreach ($sessions as $session) {
            $onlineUsers .= $session->nickname . " ";
          }
          return sys("Online: " . $onlineUsers);

        case COMMAND["QUIT"] :
          $options["keep-alive"] = false;
          put(sprintf("User %s[%s] terminated connection.", $sessionId, $clientName->name));
          unset($sessions[$sessionId]);
          return sys("Bye.");
      }
    }

    $options["keep-alive"] = true;
    $options["broadcast"]  = true;

    put(sprintf("Bradcasting %d bytes.", $receivedDataSize));
    return chat($sessions[$sessionId]->nickname, $receivedData);
});