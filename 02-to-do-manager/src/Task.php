<?php

namespace RebootDev\ToDoManager;
use JsonSerializable;
use Override;

class Task implements JsonSerializable {
  public function __construct(
    public int    $id,
    public int    $createdAt, // unix timestamp
    public string $task      = "",
    public int    $status    = self::PENDING
  ) {}

  public static function getByArray(array $task): ?self {
    if (isset($task["id"], $task["created at"], $task["task"], $task["status"])) {
      return new Task($task["id"], $task["created at"], $task["task"], $task["status"]);
    }
    return null;
  }

  #[Override]
  public function jsonSerialize(): array {
    return [
      "id"         => $this->id,
      "created at" => $this->createdAt,
      "task"       => $this->task,
      "status"     => $this->status
    ];
  }

  public function markAsDone(): void {
    $this->status = self::DONE;
  }

  public const int PENDING = 0;
  public const int DONE    = 1;
}