## Business events.
#### Services list:
  * Auth
  * Task tracker
  * Accounting
  * Analytics

#### Business events:
  * **Account created**
    * Producer: Auth
    * Consumers: Task tracker, Accounting, Analytics
  * **Account role changed**
    * Producer: Auth
    * Consumers: Task tracker, Accounting, Analytics
  * **Task assigned**
    * Producer: Task tracker
    * Consumers: Accounting, Analytics
  * **Task completed**
    * Producer: Task tracker
    * Consumers: Accounting, Analytics
  * **Accounting balance withdraw calculation completed**
    * Producer: Accounting
    * Consumers: Analytics

#### CUD:
  * **Create/update/delete account**
    * Producer: Auth
    * Consumers: Task tracker, Accounting, Analytics
  * **Create/update/delete account roles**
    * Producer: Auth
    * Consumers: Task tracker, Accounting, Analytics
  * **Create/update/delete task**
    * Producer: Task tracker
    * Consumers: Accounting, Analytics
  * **Create accounting transaction**
    * Producer: Accounting
    * Consumers: Analytics