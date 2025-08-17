<?php

namespace RebootDev\FileServer\Resource;

enum MimeType: string {
  case TEXT_PLAIN               = "text/plain";
  case TEXT_HTML                = "text/html";
  case TEXT_CSS                 = "text/css";
  case TEXT_JAVASCRIPT          = "text/javascript";
  case TEXT_MARKDOWN            = "text/markdown";
  case IMAGE_JPEG               = "image/jpeg";
  case IMAGE_PNG                = "image/png";
  case IMAGE_GIF                = "image/gif";
  case IMAGE_WEBP               = "image/webp";
  case IMAGE_SVG_XML            = "image/svg+xml";
  case AUDIO_MPEG               = "audio/mpeg";
  case AUDIO_WAV                = "audio/wav";
  case AUDIO_WEBM               = "audio/webm";
  case VIDEO_MP4                = "video/mp4";
  case VIDEO_WEBM               = "video/webm";
  case APPLICATION_JSON         = "application/json";
  case APPLICATION_XML          = "application/xml";
  case APPLICATION_PDF          = "application/pdf";
  case APPLICATION_ZIP          = "application/zip";
  case APPLICATION_JAVASCRIPT   = "application/javascript";
  case APPLICATION_OCTET_STREAM = "application/octet-stream";

  public const array EXTENSION = [
    "TEXT_PLAIN"               => "txt",
    "TEXT_HTML"                => "html",
    "TEXT_CSS"                 => "css",
    "TEXT_JAVASCRIPT"          => "js",
    "TEXT_MARKDOWN"            => "md",
    "IMAGE_JPEG"               => "jpg",
    "IMAGE_PNG"                => "png",
    "IMAGE_GIF"                => "gif",
    "IMAGE_WEBP"               => "webp",
    "IMAGE_SVG_XML"            => "svg",
    "AUDIO_MPEG"               => "mp3",
    "AUDIO_WAV"                => "wav",
    "AUDIO_WEBM"               => "weba",
    "VIDEO_MP4"                => "mp4",
    "VIDEO_WEBM"               => "webm",
    "APPLICATION_JSON"         => "json",
    "APPLICATION_XML"          => "xml",
    "APPLICATION_PDF"          => "pdf",
    "APPLICATION_ZIP"          => "zip",
    "APPLICATION_JAVASCRIPT"   => "js",
    "APPLICATION_OCTET_STREAM" => ""
  ];

  public static function fromExtension(string $extension): self {
    return match ($extension) {
      self::EXTENSION["TEXT_HTML"]              => self::TEXT_HTML,
      self::EXTENSION["TEXT_CSS"]               => self::TEXT_CSS,
      self::EXTENSION["TEXT_MARKDOWN"]          => self::TEXT_MARKDOWN,
      self::EXTENSION["IMAGE_JPEG"]             => self::IMAGE_JPEG,
      self::EXTENSION["IMAGE_PNG"]              => self::IMAGE_PNG,
      self::EXTENSION["IMAGE_GIF"]              => self::IMAGE_GIF,
      self::EXTENSION["IMAGE_WEBP"]             => self::IMAGE_WEBP,
      self::EXTENSION["IMAGE_SVG_XML"]          => self::IMAGE_SVG_XML,
      self::EXTENSION["AUDIO_MPEG"]             => self::AUDIO_MPEG,
      self::EXTENSION["AUDIO_WAV"]              => self::AUDIO_WAV,
      self::EXTENSION["AUDIO_WEBM"]             => self::AUDIO_WEBM,
      self::EXTENSION["VIDEO_MP4"]              => self::VIDEO_MP4,
      self::EXTENSION["VIDEO_WEBM"]             => self::VIDEO_WEBM,
      self::EXTENSION["APPLICATION_JSON"]       => self::APPLICATION_JSON,
      self::EXTENSION["APPLICATION_XML"]        => self::APPLICATION_XML,
      self::EXTENSION["APPLICATION_PDF"]        => self::APPLICATION_PDF,
      self::EXTENSION["APPLICATION_ZIP"]        => self::APPLICATION_ZIP,
      self::EXTENSION["TEXT_JAVASCRIPT"],
      self::EXTENSION["APPLICATION_JAVASCRIPT"] => self::APPLICATION_JAVASCRIPT,
      default                                   => self::TEXT_PLAIN
    };
  }
}