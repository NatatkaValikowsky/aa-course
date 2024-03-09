We need to add a new field to the task entity. As a result we should change out producer schema event.

1. We have the first version schema of the **Task.Created** event: [1.json](json-schema/task/created/1.json).
2. Create a new version of event schema [2.json](json-schema/task/created/2.json). With a new field.
3. Create DB migration to the new version (add a new optional field) in the task service
4. Create DB migration to the new version (add a new optional field) in the accounting/analytics service
5. Create a new consumer for the new event version (in my case I didn't have the first consumer version, so, I created it for the second version)
6. Change the event to the 2nd version in producer [TaskController::createTask()](task-tracker/src/Controller/TaskController.php)
7. Check if we receive 2nd version in the created consumer.
8. Now we can remove the previous consumer and previous producer.
9. Profit