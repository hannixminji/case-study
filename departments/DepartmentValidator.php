<?php

require_once __DIR__ . '/../includes/BaseValidator.php';

class DepartmentValidator extends BaseValidator
{
    private readonly DepartmentRepository $departmentRepository;

    public function __construct(DepartmentRepository $departmentRepository)
    {
        $this->departmentRepository = $departmentRepository;
    }

    public function validate(array $fieldsToValidate): void
    {
        $this->errors = [];

        foreach ($fieldsToValidate as $field) {
            if ( ! array_key_exists($field, $this->data)) {
                $this->errors[$field] = 'The ' . $field . ' field is missing.';
            } else {
                switch ($field) {
                    case 'id'                : $this->isValidId              ($this->data['id'                ]                           ); break;
                    case 'name'              : $this->isValidName            ($this->data['name'              ], $this->data['id'] ?? null); break;
                    case 'department_head_id': $this->isValidDepartmentHeadId($this->data['department_head_id']                           ); break;
                    case 'description'       : $this->isValidDescription     ($this->data['description'       ]                           ); break;
                    case 'status'            : $this->isValidStatus          ($this->data['status'            ]                           ); break;
                }
            }
        }
    }

    public function isValidName(mixed $name, mixed $id): bool
    {
        if ( ! is_string($name)) {
            $this->errors['name'] = 'The name must be a string.';

            return false;
        }

        $name = trim($name);

        if ($name === '') {
            $this->errors['name'] = 'The name cannot be empty.';

            return false;
        }

        if (strlen($name) < 3 || strlen($name) > 50) {
            $this->errors['name'] = 'The name must be between 3 and 50 characters long.';

            return false;
        }

        if ( ! preg_match('/^[A-Za-z0-9._\- ]+$/', $name)) {
            $this->errors['name'] = 'The name can only contain letters, numbers, periods, hyphens, underscores, and spaces.';

            return false;
        }

        if ($name !== htmlspecialchars(strip_tags($name), ENT_QUOTES, 'UTF-8')) {
            $this->errors['name'] = 'The name contains invalid characters.';

            return false;
        }

        $isUnique = $this->isUnique('name', $name, $id);

        if ($isUnique === null) {
            $this->errors['name'] = 'An unexpected error occurred while checking for uniqueness.';

            return false;
        }

        if ($isUnique === false) {
            $this->errors['name'] = 'The name must be unique, another entry already exists with this name.';

            return false;
        }

        return true;
    }

    public function isValidDepartmentHeadId(mixed $departmentHeadId): bool
    {
        if (is_int($departmentHeadId) || (is_string($departmentHeadId) && preg_match('/^[1-9]\d*$/', $departmentHeadId))) {
            if ($departmentHeadId < 1) {
                $this->errors['department_head_id'] = 'The department head ID must be greater than 0.';

                return false;
            }

            if ($departmentHeadId > PHP_INT_MAX) {
                $this->errors['department_head_id'] = 'The department head ID exceeds the maximum allowable integer size.';

                return false;
            }

            $departmentHeadId = (int) $departmentHeadId;
        }

        if (is_string($departmentHeadId) && ! $this->isValidHash($departmentHeadId)) {
            $this->errors['department_head_id'] = 'The department head ID is an invalid type.';

            return false;
        }

        if ($departmentHeadId !== null && ! is_int($departmentHeadId) && ! is_string($departmentHeadId)) {
            $this->errors['department_head_id'] = 'The department head ID is an invalid type.';

            return false;
        }

        return true;
    }

    private function isUnique(string $field, mixed $value, mixed $id): ?bool
    {
        if ( ! isset($this->errors['id'])) {
            $columns = [
                'id'
            ];

            $filterCriteria = [
                [
                    'column'   => 'department.status',
                    'operator' => '='                ,
                    'value'    => 'Active'
                ],
                [
                    'column'   => 'department.' . $field,
                    'operator' => '='                   ,
                    'value'    => $value
                ]
            ];

            if (is_int($id) || (is_string($id) && preg_match('/^[1-9]\d*$/', $id))) {
                $filterCriteria[] = [
                    'column'   => 'department.id',
                    'operator' => '!='           ,
                    'value'    => $id
                ];

            } elseif (is_string($id) && ! $this->isValidHash($id)) {
                $filterCriteria[] = [
                    'column'   => 'SHA2(department.id, 256)',
                    'operator' => '!='                      ,
                    'value'    => $id
                ];
            }

            $isUnique = $this->departmentRepository->fetchAllDepartments(
                columns             : $columns       ,
                filterCriteria      : $filterCriteria,
                limit               : 1              ,
                includeTotalRowCount: false
            );

            if ($isUnique === ActionResult::FAILURE) {
                return null;
            }

            return empty($isUnique['result_set']);
        }

        return null;
    }
}
