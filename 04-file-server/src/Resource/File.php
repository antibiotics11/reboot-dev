<?php

namespace RebootDev\FileServer\Resource;
use Generator;
use InvalidArgumentException, RuntimeException, Throwable;
use function realpath;
use function is_file, is_readable;
use function pathinfo;
use const PATHINFO_BASENAME, PATHINFO_EXTENSION;

class File {
  public function __construct(
    protected(set) string   $realpath,
    protected(set) string   $basename,
    protected(set) int      $size,
    protected(set) MimeType $mimeType = MimeType::TEXT_PLAIN
  ) {}

  public static function fromPath(string $path): self {
    $realpath = realpath($path);
    if ($realpath === false || !is_file($realpath) || !is_readable($realpath)) {
      throw new InvalidArgumentException("Unreadable file.");
    }

    $basename  = pathinfo($realpath, PATHINFO_BASENAME);
    $extension = pathinfo($realpath, PATHINFO_EXTENSION);
    $mimeType  = MimeType::fromExtension($extension);
    $size      = filesize($realpath);

    return new self($realpath, $basename, $size, $mimeType);
  }

  /**
   * @param int $chunkSize
   * @return Generator<string>
   */
  public function getContent(int $chunkSize = 8192): Generator {
    if ($chunkSize < 1) {
      throw new InvalidArgumentException("Chunk size must be larger than 1.");
    }

    $fd = fopen($this->realpath, "rb");
    if ($fd === false) {
      throw new RuntimeException("Failed to open file.");
    }
    if (!flock($fd, LOCK_SH)) {
      fclose($fd);
      throw new RuntimeException("Failed to lock file.");
    }

    try {
      rewind($fd);
      while (!feof($fd)) {
        $chunk = fread($fd, $chunkSize);
        if ($chunk === false) {
          throw new RuntimeException("Failed to read file.");
        }
        if ($chunk !== "") {
          yield $chunk;
        }
      }
    } finally {
      if (is_resource($fd)) {
        @flock($fd, LOCK_UN);
        fclose($fd);
      }
    }
  }
}