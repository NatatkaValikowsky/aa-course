## Event schema migration process.

We need to add a new field to the task entity. As a result we should change out producer schema event.

1. We have the first version schema of the **Task.Created** event: [1.json](json-schema/task/created/1.json).
2. Create a new version of event schema [2.json](json-schema/task/created/2.json). With a new field.
3. Create a new consumer for the new event version (in my case I didn't have the first consumer version, so, I created it for the second version)
4. Change the event to the 2nd version in producer [TaskController::createTask()](task-tracker/src/Controller/TaskController.php)
5. Check if we receive 2nd version in the created consumer.
6. Now we can remove the previous consumer and previous producer.
7. Profit