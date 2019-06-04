<?php

namespace Electra\Web\Application;

use Electra\Utility\Objects;
use Electra\Web\Http\Request;
use Electra\Web\Http\Response;
use Electra\Web\Task\AbstractWebTask;

class Api
{
  /** @var AbstractWebTask[] */
  protected $tasks = [];

  /**
   * @param AbstractWebTask $task
   * @return $this
   */
  public function addTask(AbstractWebTask $task)
  {
    $this->tasks[] = $task;

    return $this;
  }

  /**
   * @throws \Exception
   */
  public function run()
  {
    foreach ($this->tasks as $task)
    {
      Router::match($task->getHttpMethods(), $task->getUri(), function() use ($task)
      {
        // Hydrate payload from request params
        $payloadClass = $task->getPayloadClass();
        $payload = new $payloadClass();
        $request = Request::capture();
        $requestParams = $request->all();
        $payload = Objects::hydrate($payload, (object)$requestParams);
        // Execute task
        $taskResponse = $task->execute($payload);
        // Serialize and send response
        return Response::create(json_encode($taskResponse))->send();
      });
    }
    
    Router::init();
  }
}