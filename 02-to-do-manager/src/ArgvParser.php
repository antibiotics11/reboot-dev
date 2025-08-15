<?php

namespace RebootDev\ToDoManager;
use InvalidArgumentException;

readonly class ArgvParser {

  /**
   * @param int      $argc
   * @param string[] $argv
   * @return array
   * @throws InvalidArgumentException
   */
  public function parse(int $argc, array $argv): array {
    $parsedArgv = [];

    for ($i = 1; $i < $argc; $i++) {
      $command = Command::tryFrom($argv[$i]);
      if (!($command instanceof Command)) {
        throw new InvalidArgumentException("Undefined command: " . $argv[$i]);
      }

      $parsedArgv[] = [
        "command" => $command,
        "value"   => $command->mustHaveValue() ? $argv[++$i] : null
      ];
    }

    return $parsedArgv;
  }
}