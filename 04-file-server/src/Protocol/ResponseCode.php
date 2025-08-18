<?php

namespace RebootDev\FileServer\Protocol;

enum ResponseCode: int {
  case OK          = 200;
  case NO_CONTENT  = 204;
  case BAD_REQUEST = 400;
  case FORBIDDEN   = 403;
  case NOT_FOUND   = 404;
  case INTERNAL    = 500;

  public function reason(): string {
    return match ($this) {
      self::OK          => "OK",
      self::NO_CONTENT  => "No Content",
      self::BAD_REQUEST => "Bad RequestHeader",
      self::FORBIDDEN   => "Forbidden",
      self::NOT_FOUND   => "Not Found",
      self::INTERNAL    => "Internal Server Error"
    };
  }
}