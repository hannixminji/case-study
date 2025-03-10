<?php

require_once __DIR__ . '/../database/database.php';

$query = '
    UPDATE
        leave_entitlements AS leave_entitlement
    JOIN
        employees AS employee
    ON
        leave_entitlement.employee_id = employee.id
    SET
        leave_entitlement.number_of_days_taken = 0,
        leave_entitlement.remaining_days       = 0
    WHERE
        leave_entitlement.deleted_at IS NULL
    AND
        employee.deleted_at IS NULL
';

try {
    $statement = $pdo->prepare($query);

    $statement->execute();

} catch (PDOException $exception) {
    error_log('Database Error: An error occurred while resetting all employee leave balances. ' .
              'Exception Message: ' . $exception->getMessage());
}
