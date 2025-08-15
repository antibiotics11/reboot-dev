<?php

namespace RebootDev\ToDoManager;
use function set_exception_handler;
use function getenv;
use const DIRECTORY_SEPARATOR;

final class App {
  public function __construct(
    private readonly int   $argc       = 0,
    private readonly array $argv       = [],
    private ?string        $dataPath   = null,
    private ?ArgvParser    $argvParser = null,
    private ?Controller    $controller = null
  ) {
    $this->argvParser ??= new ArgvParser();
    if ($this->controller === null) {
      $this->dataPath ??= getenv("HOME") . DIRECTORY_SEPARATOR . ".todo.json";
      $this->controller = new Controller(
        new Model(new FileManager($this->dataPath)),
        new View()
      );
    }

    set_exception_handler([ $this->controller, "error" ]);
  }

  public function run(): void {
    if ($this->argc <= 1) {
      $this->route(Command::HELP);
      return;
    }

    $parsedArgv = $this->argvParser->parse($this->argc, $this->argv);
    foreach ($parsedArgv as $pair) {
      $this->route($pair["command"], $pair["value"]);
    }
  }

  private function route(Command $command, ?string $value = null): void {
    match ($command) {
      Command::ADD  => $this->controller->add($value),
      Command::LIST => $this->controller->list(),
      Command::DONE => $this->controller->done((int)$value),
      Command::RMOV => $this->controller->remove((int)$value),
      Command::CLER => $this->controller->clear(),
      Command::HELP => $this->controller->help()
    };
  }
}