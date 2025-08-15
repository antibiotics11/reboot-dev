<?php

namespace RebootDev\ToDoManager;
use RuntimeException;
use Throwable;
use function is_resource;
use function fopen, fwrite, feof, fread, fclose;
use function flock, fflush, fseek, ftruncate, rewind;
use function unlink;
use function strlen, substr;
use function error_get_last;
use const SEEK_END;
use const LOCK_EX, LOCK_SH, LOCK_UN;

class FileManager {
  private const string DEFAULT_MODE       = "c+b";
  private const int    DEFAULT_CHUNK_SIZE = 4096;

  /** @var resource|null */
  private $fileDescriptor = null;

  public function __construct(
    private readonly string $filePath
  ) {}

  public function __destruct() {
    if ($this->isFileOpen()) {
      $this->close();
    }
  }

  public function isFileOpen(): bool {
    return is_resource($this->fileDescriptor);
  }

  public function open(string $mode = self::DEFAULT_MODE): void {
    if ($this->isFileOpen()) {
      throw new RuntimeException("File \"" . $this->filePath . "\" is already open.");
    }

    $fileDescriptor = fopen($this->filePath, $mode);
    if ($fileDescriptor === false) {
      $lastError = error_get_last();
      $errorMessage = $lastError["message"] ?? "Unknown error.";
      throw new RuntimeException("Failed to open file: " . $errorMessage);
    }
    $this->fileDescriptor = $fileDescriptor;
  }

  public function write(string $content, bool $append = false): int {
    if (!$this->isFileOpen()) {
      throw new RuntimeException("File \"" . $this->filePath . "\" is not yet opened.");
    }

    if (!flock($this->fileDescriptor, LOCK_EX)) {
      throw new RuntimeException("Failed to lock file.");
    }

    if ($append) {
      if (fseek($this->fileDescriptor, 0, SEEK_END) !== 0) {
        throw new RuntimeException("Failed to move file pointer.");
      }
    } else {
      if (!ftruncate($this->fileDescriptor, 0) || !rewind($this->fileDescriptor)) {
        throw new RuntimeException("Failed to initialize file write.");
      }
    }

    $contentSize  = strlen($content);
    $totalWritten = 0;
    try {
      while ($totalWritten < $contentSize) {
        $chunk = substr($content, $totalWritten);
        $bytesWritten = fwrite($this->fileDescriptor, $chunk);

        if ($bytesWritten === false) {
          throw new RuntimeException("fwrite() returned false.");
        }
        if ($bytesWritten === 0) {
          throw new RuntimeException("fwrite() wrote 0 bytes; aborting.");
        }

        $totalWritten += $bytesWritten;
      }

      fflush($this->fileDescriptor);
    } catch (Throwable $e) {
      throw new RuntimeException("Failed to write to file: " . $e->getMessage(), 0, $e);
    } finally {
      flock($this->fileDescriptor, LOCK_UN);
    }

    /*
    $append ?
      fseek($this->fileDescriptor, 0, SEEK_END) :
      ftruncate($this->fileDescriptor, 0) && rewind($this->fileDescriptor);
    $bytesWritten = fwrite($this->fileDescriptor, $content);
    flock($this->fileDescriptor, LOCK_UN);
    */
    return $totalWritten;
  }

  public function read(int $chunkSize = self::DEFAULT_CHUNK_SIZE): string {
    if (!$this->isFileOpen()) {
      throw new RuntimeException("File \"" . $this->filePath . "\" is not yet opened.");
    }

    if (!flock($this->fileDescriptor, LOCK_SH)) {
      throw new RuntimeException("Failed to lock file.");
    }

    $content = "";
    try {
      rewind($this->fileDescriptor);
      while (!feof($this->fileDescriptor)) {
        if (false !== $chunk = fread($this->fileDescriptor, $chunkSize)) {
          $content .= $chunk;
          continue;
        }
        break;
      }
    } catch (Throwable $e) {
      throw new RuntimeException("Failed to read file: " . $e->getMessage(), 0, $e);
    } finally {
      flock($this->fileDescriptor, LOCK_UN);
    }

    return $content;
  }

  public function delete(): void {
    if ($this->isFileOpen()) {
      $this->close();
    }
    if (!unlink($this->filePath)) {
      throw new RuntimeException("Failed to delete file.");
    }
  }

  public function close(): void {
    if ($this->isFileOpen()) {
      @fflush($this->fileDescriptor);
      @fclose($this->fileDescriptor);
    }
    $this->fileDescriptor = null;
  }

}