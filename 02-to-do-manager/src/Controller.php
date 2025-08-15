<?php

namespace RebootDev\ToDoManager;
use Throwable;

readonly class Controller {
  public function __construct(
    private Model $model,
    private View  $view
  ) {}

  public function add(string $content): void {
    try {
      $task = $this->model->add($content);
      $this->view->add($task);
    } catch (Throwable $e) {
      $this->view->error($e);
    }
  }

  public function list(): void {
    try {
      $list = $this->model->list();
      $this->view->list($list);
    } catch (Throwable $e) {
      $this->view->error($e);
    }
  }

  public function done(int $id): void {
    try {
      $this->model->done($id);
      $this->view->done($id);
    } catch (Throwable $e) {
      $this->view->error($e);
    }
  }

  public function remove(int $id): void {
    try {
      $this->model->remove($id);
      $this->view->remove($id);
    } catch (Throwable $e) {
      $this->view->error($e);
    }
  }

  public function clear(): void {
    try {
      $count = $this->model->clear();
      $this->view->clear($count);
    } catch (Throwable $e) {
      $this->view->error($e);
    }
  }

  public function help(): void {
    $this->view->help();
  }

  public function error(Throwable $exception): void {
    $this->view->error($exception->getMessage());
  }
}