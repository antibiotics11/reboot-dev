<?php

namespace RebootDev\ToDoManager;

enum Command: string {
  case ADD  = "add";
  case LIST = "list";
  case DONE = "done";
  case RMOV = "remove";
  case CLER = "clear";
  case HELP = "help";

  public function mustHaveValue(): bool {
    return match($this) {
      self::ADD,  self::RMOV, self::DONE => true,
      self::LIST, self::CLER, self::HELP => false
    };
  }
}