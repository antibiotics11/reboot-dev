<?php

namespace RebootDev\ToDoManager;
use RuntimeException, InvalidArgumentException;
use function json_encode, json_decode;
use function strlen;
use function count, end;
use function time;

readonly class Model {

  /**
   * @return Task[]
   */
  private function readAllTasks(): array {
    $this->fileManager->open();
    $fileContent = $this->fileManager->read();
    $this->fileManager->close();

    $jsonDecoded = [];
    if (strlen($fileContent) > 0) {
      $jsonDecoded = json_decode($fileContent, true);
    }
    if ($jsonDecoded === null) {
      throw new RuntimeException("Failed to decode json.");
    }

    $taskList = [];
    foreach ($jsonDecoded as $rawData) {
      if (($task = Task::getByArray($rawData)) instanceof Task) {
        $taskList[] = $task;
        continue;
      }
      throw new InvalidArgumentException();
    }

    return $taskList;
  }

  /**
   * @param Task[] $taskList
   * @return void
   */
  private function writeTasks(array $taskList): void {
    $encodedList = count($taskList) > 0 ?
      json_encode($taskList, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)
      : "" ;
    if ($encodedList === false) {
      throw new RuntimeException("Failed to encode json.");
    }

    $this->fileManager->open();
    $this->fileManager->write($encodedList);
    $this->fileManager->close();
  }

  private function getTaskById(int $id): ?Task {
    $taskList = $this->readAllTasks();
    foreach ($taskList as $task) {
      if ($task->id === $id) {
        return $task;
      }
    }
    return null;
  }

  public function __construct(
    private FileManager $fileManager
  ) {}

  public function add(string $content): ?Task {
    $taskList = $this->readAllTasks();
    $lastTask = end($taskList);

    $newId   = $lastTask === false ? 1 : $lastTask->id + 1;
    $newTask = new Task($newId, time(), $content);
    $taskList[] = $newTask;

    $this->writeTasks($taskList);
    return $newTask;
  }

  public function list(): array {
    return $this->readAllTasks();
  }

  public function done(int $id): void {
    if ($this->getTaskById($id) === null) {
      throw new InvalidArgumentException("Task ID \"" . $id . "\" does not exist.");
    }

    $taskList = $this->readAllTasks();
    foreach ($taskList as $task) {
      if ($task->id === $id) {
        $task->markAsDone();
        break;
      }
    }

    $this->writeTasks($taskList);
  }

  public function remove(int $id): void {
    if ($this->getTaskById($id) === null) {
      throw new InvalidArgumentException("Task ID \"" . $id . "\" does not exist.");
    }

    $taskList = $this->readAllTasks();
    foreach ($taskList as $arrayKey => $task) {
      if ($task->id === $id) {
        unset($taskList[$arrayKey]);
        break;
      }
    }

    $this->writeTasks($taskList);
  }

  public function clear(): int {
    $prevTaskCount = count($this->readAllTasks());

    $this->writeTasks([]);
    $currentCount = count($this->readAllTasks());

    return $prevTaskCount - $currentCount;
  }
}