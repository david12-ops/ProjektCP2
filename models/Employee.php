<?php

//namespace models;

class Employee
{
    public const DB_TABLE = "employee";

    public ?string $login;
    public ?string $password;
    public ?int $admin;
    public ?int $employee_id;
    public ?string $name;
    public ?string $surname;
    public ?string $job;
    public ?int $wage;
    public ?int $room;

    /**
     * @param string|null $login
     * @param int|null $admin
     * @param int|null $employee_id
     * @param string|null $name
     * @param string|null $surname
     * @param string|null $phone
     * @param string|null $job;
     * @param int|null $wage;
     * @param int|null $room;
     */
    public function __construct(?string $login = null, ?string $password = null, ?int $admin = null, ?int $employee_id = null, ?string $name = null, ?string $surname = null, ?string $job = null, ?int $wage = null, ?int $room = null)
    {
        $this->login = $login;
        $this->password = $password;
        $this->admin = $admin;
        $this->employee_id = $employee_id;
        $this->name = $name;
        $this->surname = $surname;
        $this->job = $job;
        $this->wage = $wage;
        $this->room = $room;
    }

    public static function findByID(int $id): ?self
    {
        $pdo = PDOProvider::get();
        $stmt = $pdo->prepare("SELECT * FROM `" . self::DB_TABLE . "` WHERE `employee_id`= :employeeId");
        $stmt->execute(['employeeId' => $id]);

        if ($stmt->rowCount() < 1)
            return null;

        $employee = new self();
        $employee->hydrate($stmt->fetch());
        return $employee;
    }

    /**
     * @return Employee[]
     */
    public static function getAll($sorting = []): array
    {
        $sortSQL = "";
        if (count($sorting)) {
            $SQLchunks = [];
            foreach ($sorting as $field => $direction)
                $SQLchunks[] = "`{$field}` {$direction}";

            $sortSQL = " ORDER BY " . implode(', ', $SQLchunks);
        }

        $pdo = PDOProvider::get();
        $stmt = $pdo->prepare("SELECT * FROM `" . self::DB_TABLE . "`" . $sortSQL);
        $stmt->execute([]);

        $employee = [];
        while ($employeeData = $stmt->fetch()) {
            $employees = new Employee();
            $employees->hydrate($employeeData);
            $employee[] = $employees;
        }

        return $employee;
    }

    private function hydrate(array|object $data)
    {
        $fields = ['login', 'password', 'admin', 'employee_id', 'name', 'surname', 'job', 'wage', 'room'];
        if (is_array($data)) {
            foreach ($fields as $field) {
                if (array_key_exists($field, $data))
                    $this->{$field} = $data[$field];
            }
        } else {
            foreach ($fields as $field) {
                if (property_exists($data, $field))
                    $this->{$field} = $data->{$field};
            }
        }
    }

    public function insert(): bool
    {
        //pridat Sloupce
        $query = "INSERT INTO " . self::DB_TABLE . " (`login`,`password`,`admin`,`name`, `surname`, `job`, `wage`, `room`) VALUES (:login,:password, :admin, :name, :surname, :job, :wage, :room)";
        $stmt = PDOProvider::get()->prepare($query);
        $result = $stmt->execute(['login' => $this->login, 'password' => $this->password, 'admin' => $this->admin, 'name' => $this->name, 'surname' => $this->surname, 'job' => $this->job, 'wage' => $this->wage, 'room' => $this->room]);
        if (!$result)
            return false;

        $this->employee_id = PDOProvider::get()->lastInsertId();
        return true;
    }

    public function update($password): bool
    {
        if (!isset($this->employee_id) || !$this->employee_id)
            throw new Exception("Cannot update model without ID");
        if (trim($password)) {
            $query = "UPDATE " . self::DB_TABLE . " SET `login` = :login,`password` = :password,`admin` = :admin,`name` = :name, `surname` = :surname, `job` = :job, `wage` = :wage, `room` = :room WHERE `employee_id` = :employeeId";
            $stmt = PDOProvider::get()->prepare($query);
            return $stmt->execute(['employeeId' => $this->employee_id, 'login' => $this->login, 'password' => $this->password, 'admin' => $this->admin, 'name' => $this->name, 'surname' => $this->surname, 'job' => $this->job, 'wage' => $this->wage, 'room' => $this->room]);
        } else {
            $query = "UPDATE " . self::DB_TABLE . " SET `login` = :login,`admin` = :admin,`name` = :name, `surname` = :surname, `job` = :job, `wage` = :wage, `room` = :room WHERE `employee_id` = :employeeId";
            $stmt = PDOProvider::get()->prepare($query);
            return $stmt->execute(['employeeId' => $this->employee_id, 'login' => $this->login, 'admin' => $this->admin, 'name' => $this->name, 'surname' => $this->surname, 'job' => $this->job, 'wage' => $this->wage, 'room' => $this->room]);
        }
    }

    public function delete(): bool
    {
        return self::deleteByID($this->employee_id);
    }

    public static function deleteByID(int $employeeId): bool
    {
        $query = "DELETE FROM `" . self::DB_TABLE . "` WHERE `employee_id` = :employeeId";
        $stmt = PDOProvider::get()->prepare($query);
        return $stmt->execute(['employeeId' => $employeeId]);
    }


    public function validate(&$errors = []): bool
    {
        //upravit regex pro login a name room, pozice
        if (!isset($this->login) || (!$this->login)) {
            $errors['login'] = 'Uživatelské jméno nesmí být prázdné';
        } elseif (!preg_match('/^[A-Za-z]([A-Za-z_\-0-9])+$/u', $this->login)) {
            $errors['login'] = 'login musí obsahovat jen písmena a čísla 0-9 nebo "-", "_". Písmena musí být na začátku.';
        }
        if (!isset($this->name) || (!$this->name)) {
            $errors['name'] = 'Jméno nesmí být prázdné';
        } elseif (!preg_match('/^[\p{L}]+$/u', $this->name)) {
            $errors['name'] = 'Jméno musí obsahovat jen písmena s diakritikou';
        }
        if (!isset($this->surname) || (!$this->surname)) {
            $errors['surname'] = 'Příjmení musí být vyplněno';
        } elseif (!preg_match('/^[\p{L}]+$/u', $this->surname)) {
            $errors['surname'] = 'Příjmení musí obsahovat jen písmena s diakritikou';
        }
        ///^[\p{L}_\-]+$/u
        ///^[\p{L}]+$/u
        ///^[\p{L}_\-0-9]+$/u

        ///^([\p{L}|0-9][\p{L}\-0-9_-]*)$/u
        if (!isset($this->job) || (!$this->job)) {
            $errors['job'] = 'Pozice musí být vyplněná';
        } elseif (!preg_match('/^([\p{L}|0-9][\p{L}\-0-9_-]*)$/u', $this->job)) {
            $errors['job'] = 'Pozice musí obsahovat jen písmena s diakritikou a čísla 0-9 nebo "-", "_". Písmena nebo čísla musí být na začátku.';
        }
        if (!isset($this->wage)) {
            $errors['wage'] = 'Plat musí být vyplněn';
        } elseif ($this->wage < 0) {
            $errors['wage'] = 'Plat nemůže být záporný';
        } elseif (!intval($this->wage)) {
            $errors['wage'] = 'Plat musí být číslo';
        }
        if (!isset($this->room))
            $errors['room'] = 'Místnost musí být vyplněna';

        return count($errors) === 0;
    }

    //?Heshovat
    public static function readPost(): self
    {
        $employee = new Employee();

        $employee->login = filter_input(INPUT_POST, 'login');
        if ($employee->login)
            $employee->login = trim($employee->login);

        $employee->password = filter_input(INPUT_POST, 'password');
        if ($employee->password)
            $employee->password = trim($employee->password);

        $employee->admin = filter_input(INPUT_POST, 'admin', FILTER_VALIDATE_INT);
        if (!$employee->admin)
            $employee->admin = 0;

        $employee->employee_id = filter_input(INPUT_POST, 'employee_id', FILTER_VALIDATE_INT);

        $employee->name = filter_input(INPUT_POST, 'name');
        if ($employee->name)
            $employee->name = trim($employee->name);

        $employee->surname = filter_input(INPUT_POST, 'surname');
        if ($employee->surname)
            $employee->surname = trim($employee->surname);

        $employee->job = filter_input(INPUT_POST, 'job');
        if ($employee->job)
            $employee->job = trim($employee->job);

        $employee->wage = filter_input(INPUT_POST, 'wage', FILTER_VALIDATE_INT);

        $employee->room = filter_input(INPUT_POST, 'room', FILTER_VALIDATE_INT);

        return $employee;
    }
}
