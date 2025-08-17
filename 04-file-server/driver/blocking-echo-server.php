<?php

require_once(__DIR__ . "/../src/Autoloader.php");
use RebootDev\FileServer\Autoloader;
use RebootDev\FileServer\Network\{BlockingTcpServer, TcpSocket};

function put(string $message): void {
  printf("[%s] %s%s", date(DATE_RFC2822, time()), $message, PHP_EOL);
}

Autoloader::register();

$socket = TcpSocket::create();
$socket->setOption(SOL_SOCKET, SO_REUSEADDR, 1);
$socket->setOption(SOL_SOCKET, SO_REUSEPORT, 1);
$socket->setOption(SOL_SOCKET, SO_RCVTIMEO,  [ "sec" => 10, "usec" => 0 ]);
$socket->setOption(SOL_SOCKET, SO_SNDTIMEO,  [ "sec" => 10, "usec" => 0 ]);
$socket->bindTo("127.0.0.1", 5001);

$server = new BlockingTcpServer;
put("Starting server...");

pcntl_async_signals(true);
pcntl_signal(SIGINT, function () use (&$server): never {
  put("Closing server...");
  $server->close();
  exit(0);
});
pcntl_signal(SIGCHLD, SIG_IGN);

$server->open($socket);
$server->handle(static function (TcpSocket $clientSocket, BlockingTcpServer $server): void {
  $pid = pcntl_fork();
  if ($pid == 0) {
    $server->close();

    $receivedData = $clientSocket->read();
    $receivedSize = strlen($receivedData);

    put($receivedSize . " bytes received from " . $clientSocket->getPeername()->name);
    put("Received data: " . $receivedData);

    $clientSocket->write($receivedSize . " bytes received.\r\n");
    $clientSocket->close();
    exit(0);
  } else {
    put("Handling request on PID " . $pid);
    $clientSocket->close();
  }
});