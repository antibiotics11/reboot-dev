<?php

namespace RebootDev\FileServer\Protocol;
use InvalidArgumentException;
use function strlen, strtoupper, trim, ord;

final readonly class RequestHeader extends Header {
  public function __construct(
    public RequestMethod $method,
    public string        $path
  ) {}

  public static function fromRequest(string $request): self {
    $rawMethod  = "";

    $separatorFound = false;
    $buffer = "";
    for ($i = 0; $i < strlen($request); $i++) {
      if (!$separatorFound && ord($request[$i]) == 32) {
        $separatorFound = true;
        $rawMethod = $buffer;
        $buffer = "";
        continue;
      }
      $buffer .= $request[$i];
    }

    $rawPath = trim($buffer);
    if (strlen($rawPath) === 0) {
      throw new InvalidArgumentException("Bad request header.");
    }

    $method = RequestMethod::tryFrom(strtoupper(trim($rawMethod)));
    if ($method === null) {
      throw new InvalidArgumentException("Invalid request method.");
    }

    return new self($method, $rawPath);
  }
}