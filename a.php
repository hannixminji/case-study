<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=payroll', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $employee_id = 1005;  // Example employee_id (can be null)
    $job_title_id = 102;  // Example job_title_id (can be null)
    $department_id = 1;   // Example department_id (can be null)

    $query = "
    SELECT id
    FROM test
    WHERE
        (employee_id = :employee_id AND job_title_id = :job_title_id AND department_id = :department_id)
    OR
        (employee_id IS NULL AND job_title_id = :job_title_id AND department_id = :department_id)
    OR
        (employee_id IS NULL AND job_title_id IS NULL AND department_id = :department_id)
    OR
        (employee_id IS NULL AND job_title_id IS NULL AND department_id IS NULL)
    ORDER BY
        CASE
            WHEN employee_id = :employee_id AND job_title_id = :job_title_id AND department_id = :department_id THEN 1
            WHEN employee_id IS NULL AND job_title_id = :job_title_id AND department_id = :department_id THEN 2
            WHEN employee_id IS NULL AND job_title_id IS NULL AND department_id = :department_id THEN 3
            WHEN employee_id IS NULL AND job_title_id IS NULL AND department_id IS NULL THEN 4
            ELSE 5
        END
    LIMIT 1
    ";

        $statement = $pdo->prepare($query);

        // Binding values using Helper::getPdoParameterType() for correct parameter types
        $statement->bindValue(":employee_id"   , $employee_id   , PDO::PARAM_INT);
        $statement->bindValue(":job_title_id"  , $job_title_id  , PDO::PARAM_INT);
        $statement->bindValue(":department_id" , $department_id , PDO::PARAM_INT);

        $statement->execute();

        $id = $statement->fetchColumn();

echo $id;
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
