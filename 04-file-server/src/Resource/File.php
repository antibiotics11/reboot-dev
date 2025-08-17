<?php

namespace RebootDev\FileServer\Resource;
use InvalidArgumentException, RuntimeException;
use function strlen;
use function realpath;
use function is_file, is_readable;
use function file_get_contents;
use function pathinfo;
use const PATHINFO_BASENAME, PATHINFO_EXTENSION;

class File {
  protected(set) string $name;
  protected(set) int    $size;

  public function __construct(
    public string $path {
      set (string $path) {
        $this->path = $path;
        $this->name = pathinfo($path, PATHINFO_BASENAME);
      }
    },
    public string $content  = "" {
      set (string $content) {
        $this->content = $content;
        $this->size    = strlen($content);
      }
    },
    public MimeType $mimeType = MimeType::TEXT_PLAIN
  ) {}

  public static function fromPath(string $path): self {
    $absolutePath = realpath($path);
    if ($absolutePath === false) {
      throw new InvalidArgumentException("Failed to get absolute path.");
    }
    if (!is_file($absolutePath)) {
      throw new InvalidArgumentException("Input path is not a file.");
    }
    if (!is_readable($absolutePath)) {
      throw new InvalidArgumentException("File is not readable.");
    }

    $content = file_get_contents($absolutePath);
    if ($content === false) {
      throw new RuntimeException("Failed to read file content");
    }

    $extension = MimeType::fromExtension(
      pathinfo($absolutePath, PATHINFO_EXTENSION)
    );

    return new self($absolutePath, $content, $extension);
  }
}