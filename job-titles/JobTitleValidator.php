<?php

require_once __DIR__ . '/../includes/BaseValidator.php';

class JobTitleValidator extends BaseValidator
{
    public function __construct()
    {
    }

    public function validate(array $fieldsToValidate): void
    {
    }

    public function isValidTitle(mixed $title): bool
    {
        if ($title === null) {
            $this->errors['title'] = 'The title cannot be null.';

            return false;
        }

        if ( ! is_string($title)) {
            $this->errors['title'] = 'The title must be a string.';

            return false;
        }

        if (trim($title) === '') {
            $this->errors['title'] = 'The title cannot be empty.';

            return false;
        }

        if (mb_strlen($title) < 3 || mb_strlen($title) > 100) {
            $this->errors['title'] = 'The title must be between 3 and 100 characters long.';

            return false;
        }

        if ( ! preg_match('/^[A-Za-z0-9._\- ]+$/', $title)) {
            $this->errors['title'] = 'The title contains invalid characters. Only letters, numbers, spaces, and the following characters are allowed: - . _';

            return false;
        }

        if ($title !== htmlspecialchars(strip_tags($title), ENT_QUOTES, 'UTF-8')) {
            $this->errors['title'] = 'The title contains HTML tags or special characters that are not allowed.';

            return false;
        }

        $isUnique = $this->isUnique('title', $title);

        if ($isUnique === null) {
            $this->errors['title'] = 'Unable to verify the uniqueness of the title. Please try again later.';

            return false;
        }

        if ($isUnique === false) {
            $this->errors['title'] = 'This title already exists. Please provide a different one.';

            return false;
        }

        return true;
    }
}
