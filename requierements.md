## Task tracker

1. Таск-трекер должен быть отдельным дашбордом и доступен всем сотрудникам компании UberPopug Inc.
* Actor: Account
* Data: Account id
* Query: Get tasks

2. Авторизация в таск-трекере должна выполняться через общий сервис авторизации
* Actor: Account
* Command: Login to task tracker
* Data: Account credentials
* Event: Account authorized

3. В таск-трекере должны быть только задачи.

4. Новые таски может создавать кто угодно
* Actor: Account
* Command: Create a new task
* Data: Task
  * description
  * status
  * assignee -> account (no admin/manager role)
* Event: Task created
* Event: Task assigned

5. Менеджеры или администраторы должны иметь кнопку «заассайнить задачи», которая возьмёт все открытые задачи и рандомно заассайнит каждую на любого из сотрудников
* Actor: Admin/manager role account
* Command: Assign tasks
* Data: -
* Event: Task assigned (single event for every task)

6. Каждый сотрудник должен иметь возможность видеть в отдельном месте список заассайненных на него задач + отметить задачу выполненной.
* Read model: 
  * Actor: Account
  * Data: Account id
  * Query: Get tasks
* Write model:
  * Actor: Account
  * Command: Complete task
  * Data: Task id
  * Event: Task completed

## Accounting
1. Аккаунтинг должен быть в отдельном дашборде
* Worker account read model queries:
  * get account balance
  * get account logs
* Admin/accountant account read model queries:
  * get all accounts balances
  * get all account logs
  * get daily earning statistics

2. Авторизация в дешборде аккаунтинга должна выполняться через общий сервис аутентификации
* Actor: Account
* Command: Login to accounting
* Data: Account credentials
* Event: Account authorized

3. У каждого из сотрудников должен быть свой счёт
* Actor: Event - Account created
* Command: Create account balance
* Data: Account id
* Event: Account balance created

4. Расценки
   * деньги списываются сразу после ассайна на сотрудника
     * Actor: Event - Task assigned
     * Command: Create transaction

     * Data:
       * Account id
       * Assigned task fee amount (negative value)
     * Event: Transaction created
   * деньги начисляются после выполнения задачи
     * Actor: Event - Task completed
     * Command: Create transaction
     * Data:
       * Account id
       * Completed task amount
     * Event: Transaction created
   * отрицательный баланс переносится на следующий день
     * Actor: Event - Transaction created
     * Command: Update account balance
     * Data:
       * Account id
       * Transaction amount
     * Event: Account balance updated

5. Дешборд должен выводить количество заработанных топ-менеджментом за сегодня денег.
* Read model: get dashboard daily data. We can make dashboard report instead of aggregate all transactions on fly. For this case:
  * Actor: Account
  * Data: Account id
  * Query: Get audit report
* Write model:
  * Actor: Event - Transaction created
  * Command: Update accounting daily report
  * Data:
    * Transaction amount
  * Event: Accounting daily report updated

6. В конце дня необходимо: считать сколько денег сотрудник получил за рабочий день, отправлять на почту сумму выплаты.
* Actor: Crontab or any scheduler 
* Command: Calculate account daily earnings
* Data:
  * Account id
* Event: Account daily earnings calculated 

* Actor: Event - Account daily earnings calculated
* Command: Send account earning notification
* Data:
    * Account id
    * Daily earning data
* Event: Account earning notification sent

7. После выплаты баланса (в конце дня) он должен обнуляться, и в аудитлоге всех операций аккаунтинга должно быть отображено, что была выплачена сумма.
* Actor: Event: Notification sent
* Command: Create account top-up transaction
* Data:
    * Account id
    * Daily earning data
* Event: Account top-up transaction created
* Actor: Event - Account top-up transaction created
* Command: Reset account balance
* Data:
   * Account id
* Event: Account balance reset

8. Дашборд должен выводить информацию по дням
* Actor: Account
* Data: Account id
* Query: Get audit report


## Analytics

Analytics service collects events and generates reports

1. Аналитика — это отдельный дашборд, доступный только админам.
* Actor: Account
* Data: Account (admin role)
* Query: Get analytics report data

2. Нужно указывать, сколько заработал топ-менеджмент за сегодня и сколько попугов ушло в минус.
* Actor: Account
* Data: Account (admin role)
* Query: Get earning daily report data

3. Нужно показывать самую дорогую задачу за день, неделю или месяц.
* Actor: Account
* Data: Account (admin role)
* Query: Get daily highest price task report data

## Auth
* Actor: User
* Command: Create account
* Data: Account data
  * email 
* Event: Account created

* Actor: User
* Command: Authorize user
* Data: Account credentials
  * email
* Event: Account authorized

