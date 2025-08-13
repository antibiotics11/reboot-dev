#!/usr/bin/env php
<?php

declare(ticks = 1, strict_types = 1);
cli_set_process_title("cli-notes");

const COMMAND_ADD    = "add";
const COMMAND_LIST   = "list";
const COMMAND_CLEAR  = "clear";
const COMMAND_SEARCH = "search";
const COMMAND_HELP   = "help";
const OUTPUT_PATH    = "/dev/tty";
const NOTE_FILE_PATH = "/etc/notes.json";
const NOTE_FILE_PERM = 0600;
const HELP           = <<<HELP
usage: note {add|list|clear}\r\n
HELP;

/** struct */
final readonly class app_config {
    public function __construct(
        public string $output_path,
        public string $note_file_path
    ) {}
}

function main(int $argc, array $argv): int {
    $parsed_argv = parse_argv($argc, $argv);

    $config = new app_config(
        $parsed_argv["output"] ?? OUTPUT_PATH,
        $parsed_argv["file"] ?? NOTE_FILE_PATH
    );
    unset($parsed_argv["output"], $parsed_argv["file"]);

    foreach ($parsed_argv as $key => $value) {
        if (!router($key, $value, $config)) {
            return 1;
        }
    }
    return 0;
}

function parse_argv(int $argc, array $argv): array {
    $parsed_argv = [];
    for ($i = 1; $i < $argc; $i++) {
        $key   = $argv[$i];
        $value = null;
        if (isset($argv[$i + 1])) {
            $value = $argv[++$i];
        }
        $parsed_argv[$key] = $value;
    }
    return $parsed_argv;
}

function router(string $key, ?string $value, app_config $config): bool {
    return match ($key) {
        COMMAND_ADD    => note_add($value, $config),
        COMMAND_LIST   => note_list($config),
        COMMAND_CLEAR  => note_clear($config),
        COMMAND_SEARCH => note_search($config),
        COMMAND_HELP   => note_help($config),
        default        => note_error($config)
    };
}

function current_time(string $format = DATE_RFC2822): string {
    return date($format, time());
}

const ACCESS_MODE_READONLY  = "r";
const ACCESS_MODE_READWRITE = "r+";

function file_open(string $path, bool $readonly = true) {
    $mode = $readonly ? ACCESS_MODE_READONLY : ACCESS_MODE_READWRITE;
    if (false !== $fd = fopen($path, $mode, false, stream_context_create([
        "file" => [
            "output_buffering" => false
        ]
    ]))) {
        return $fd;
    }
    return false;
}

function file_write($fd, string $content, bool $append = true): int|false {
    if (!is_resource($fd)) {
        return false;
    }

    if (!flock($fd, LOCK_EX)) {
        return false;
    }

    if ($append) {
        $seek_result = fseek($fd, 0, SEEK_END);
        if ($seek_result !== 0) {
            return false;
        }
    }

    $bytes_written = fwrite($fd, $content);
    flock($fd, LOCK_UN);

    return $bytes_written;
 }

function file_read($fd): string|false {
    if (!is_resource($fd)) {
        return false;
    }

    $content = "";
    while (!feof($fd)) {
        if (false !== $chunk = fread($fd, 4096)) {
            $content = sprintf("%s%s", $content, $chunk);
            continue;
        }
        break;
    }

    return $content;
}

function file_close(&$fd): void {
    if (is_resource($fd)) {
        fflush($fd);
        fclose($fd);
    }
    $fd = null;
}

function _note_is_valid(string $note_file_path): bool {
    return is_readable($note_file_path) && is_writable($note_file_path);
}

function _note_create(string $note_file_path): bool {
    return touch($note_file_path) && chmod($note_file_path, NOTE_FILE_PERM);
}

function _note_remove(string $note_file_path): bool {
    return unlink($note_file_path);
}

function _note_read(string $note_file_path): ?array {
    if (!_note_is_valid($note_file_path)) {
        return null;
    }

    $note_file_fd = file_open($note_file_path);
    if ($note_file_fd === false) {
        return null;
    }

    $note_file_raw_content = file_read($note_file_fd);
    if ($note_file_raw_content === false) {
        return null;
    }

    file_close($note_file_fd);

    $note_file_content = _note_decode($note_file_raw_content);
    return $note_file_content ?? null;
}

function _note_write(string $note_file_path, array $content): ?int {
    if (!_note_is_valid($note_file_path)) {
        return null;
    }

    $note_file_fd = file_open($note_file_path, false);
    if ($note_file_fd === false) {
        return null;
    }

    $note_file_content = _note_encode($content);
    if ($note_file_content === null) {
        return null;
    }

    $bytes_written = file_write($note_file_fd, $note_file_content, false);
    if ($bytes_written === false) {
        return null;
    }

    file_close($note_file_fd);
    return $bytes_written;
}

function _note_encode(array $content): ?string {
    if (false !== $result = json_encode($content)) {
        return $result;
    }
    return null;
}

function _note_decode(string $content): ?array {
    if (strlen($content) === 0) {
        return [];
    }

    $result = json_decode($content, true);
    if ($result !== false && $result !== null) {
        return $result;
    }
    return null;
}

function _note_output(string $output_path, string $content, bool $close = false): void {
  static $output_fd;
  static $output_is_file = null;

  $output_fd      ??= file_open($output_path, false);
  $output_is_file ??= is_file($output_path);

  file_write($output_fd, $content, $output_is_file);

  if ($close) {
      file_close($output_fd);
  }
}

function note_add(string $content, app_config $config): bool {
    $note_content = _note_read($config->note_file_path);
    if ($note_content === null) {
        return false;
    }

    $note_content[current_time()] = $content;
    if (false === _note_write($config->note_file_path, $note_content)) {
        return false;
    }
    return true;
}

function note_list(app_config $config): bool {
    $note_content = _note_read($config->note_file_path);
    if ($note_content === null) {
        return false;
    }

    $note_output = "";
    foreach ($note_content as $timestamp => $line) {
        $note_output = sprintf("%s\r\n%s => %s",
            $note_output,
            $timestamp,
            $line
        );
    }
    $note_output = sprintf("%s\r\n", $note_output);

    _note_output($config->output_path, $note_output, true);
    return true;
}

function note_clear(app_config $config): bool {
    return _note_remove($config->note_file_path) && _note_create($config->note_file_path);
}

function note_search(app_config $config): bool {


    return true;
}

function note_help(app_config $config): bool {
    _note_output($config->output_path, HELP, true);
    return true;
}

function note_error(app_config $config): bool {
    _note_output($config->output_path, "Undefined command.\r\n", true);
    return true;
}

exit(main($_SERVER["argc"], $_SERVER["argv"]));