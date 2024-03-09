1. Add unit tests to all services
2. Add integration tests
3. Add "black box" test for every service
  * send user input data, check if we created data and produced necessary events
  * send mocked MB data to consumers, check if we created data and produced necessary events
4. Add transactional outbox pattern to save events in DB table before sending to MB - allows us to avoid lost messages if MB is down
5. Add dead letter DB table to save "invalid" events to forward them to the special consumer.