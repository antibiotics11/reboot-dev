<?php

namespace RebootDev\FileServer\Protocol;
use RebootDev\FileServer\Resource\File;
use InvalidArgumentException;
use function ord;
use function strlen, sprintf;

readonly class Message {
  public function __construct(
    public bool           $isRequest = false,
    public ?RequestMethod $method    = null,
    public ?ResponseCode  $code      = null,
    public ?File          $payload   = null
  ) {}

  public static function fromRequest(string $request): self {
    $rawMethod  = "";
    $rawPath    = "";

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
    $rawPath = $buffer;

    $method = RequestMethod::tryFrom($rawMethod);
    if ($method === null) {
      throw new InvalidArgumentException("Invalid request method.");
    }

    return new self(true, $method, null, new File($rawPath));
  }

  public function toResponse(): string {
    if ($this->isRequest) {
      throw new InvalidArgumentException("Message is a request.");
    }
    if ($this->code === null) {
      throw new InvalidArgumentException("Response code is not defined.");
    }

    $rawPayload  = "";
    $payloadSize = 0;
    if ($this->payload !== null) {
      $rawPayload  = $this->payload->content;
      $payloadSize = $this->payload->size;
    }

    $rawHeader = sprintf("ERR %d %s", $this->code->value, $this->code->toString());
    if ($this->code === ResponseCode::OK) {
      $rawHeader = sprintf("OK %d\r\n%s", $payloadSize, $rawPayload);
    }

    return sprintf("%s\r\n", $rawHeader);
  }
}