<?php

namespace RebootDev\ToDoManager;
use function printf, sprintf;
use function date;

readonly class View {
  private const string TEMPLATE = <<<TEMPLATE
===============To Do Manager v0.9===============
%s
================================================

TEMPLATE;

  private const string ADD_MESSAGE = "New task registered as id %d.";
  public function add(Task $task): void {
    printf(self::TEMPLATE,
      sprintf(self::ADD_MESSAGE, $task->id)
    );
  }

  /**
   * @param Task[] $list
   * @return void
   */
  private const string TASK_VIEW = <<<TASK
------------------------------------------------
  %d  |  %s  |  %s  |  %s

TASK;
  public function list(array $list): void {
    $listView = "  ID  |  Content  |  Created At  |  Status  " . PHP_EOL;

    foreach ($list as $task) {
      $listView .= sprintf(self::TASK_VIEW,
        $task->id,
        $task->task,
        date(DATE_RFC3339, $task->createdAt),
        $task->status === 0 ? "Pending" : "Done"
      );
    }

    printf(self::TEMPLATE, $listView);
  }

  private const string DONE_MESSAGE = "Task id %d has been done.";
  public function done(int $id): void {
    printf(self::TEMPLATE,
      sprintf(self::DONE_MESSAGE, $id)
    );
  }

  private const string REMOVE_MESSAGE = "Successfully removed task id %d.";
  public function remove(int $id): void {
    printf(self::TEMPLATE,
      sprintf(self::REMOVE_MESSAGE, $id)
    );
  }

  private const string CLEAR_MESSAGE = "Successfully cleared %d tasks.";
  public function clear(int $count): void {
    printf(self::TEMPLATE,
      sprintf(self::CLEAR_MESSAGE, $count)
    );
  }

  private const string HELP = "Usage: todo {add|list|done|remove|clear|help}";
  public function help(): void {
    printf(self::TEMPLATE, self::HELP);
  }

  public function error(string $message): void {
    printf(self::TEMPLATE, $message);
  }
}