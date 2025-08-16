<?php

namespace RebootDev\SocketServer;
use RuntimeException;
use function strlen, substr, strtr, strncmp, sprintf;
use function is_file, is_readable;
use function spl_autoload_register;
use const DIRECTORY_SEPARATOR;

final class Autoloader {
  private const string BASE_DIRECTORY = __DIR__;
  private const string ROOT_NAMESPACE = __NAMESPACE__;
  private const string DIR_SEPARATOR  = DIRECTORY_SEPARATOR;
  private const string CLASS_FILE_EXT = ".php";

  public static function register(): void {
    if (!spl_autoload_register([ self::class, "loadClass" ])) {
      throw new RuntimeException("Failed to register autoloader.");
    }
  }

  private static function loadClass(string $class): void {
    $rootNamespaceLength = strlen(self::ROOT_NAMESPACE);
    if (strncmp(self::ROOT_NAMESPACE, $class, $rootNamespaceLength) !== 0) {
      return;
    }

    $classNamespacePath  = substr($class, $rootNamespaceLength);
    $classFilePath       = sprintf("%s%s%s",
      self::BASE_DIRECTORY,
      strtr($classNamespacePath, [ "\\" => self::DIR_SEPARATOR ]),
      self::CLASS_FILE_EXT
    );

    if (is_file($classFilePath) && is_readable($classFilePath)) {
      require_once($classFilePath);
    }
  }

  private function __construct() {}
}