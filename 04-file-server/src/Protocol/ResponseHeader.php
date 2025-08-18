<?php

namespace RebootDev\FileServer\Protocol;
use RebootDev\FileServer\Resource\File;
use Stringable;
use Override;
use function sprintf;

readonly class ResponseHeader extends Header implements Stringable {
  public function __construct(
    public ResponseCode $code,
    public File         $file
  ) {}

  #[Override]
  public function __toString(): string {
    if ($this->code === ResponseCode::OK) {
      return sprintf("OK %d\r\n", $this->file->size);
    }
    return sprintf("ERR %d %s\r\n", $this->code->value, $this->code->reason());
  }
}