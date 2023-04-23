<?php
session_start();

require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class EmployeeDetailPage extends BasePage
{
    private $keys;
    private $rooms;
    private $employee;

    protected function prepare(): void
    {
        parent::prepare();
        //získat data z GET
        $employeeId = filter_input(INPUT_GET, 'employeeId', FILTER_VALIDATE_INT);
        if (!$employeeId)
            throw new BadRequestException();

        //najít zaměstnance v databázi
        $this->employee = Employee::findByID($employeeId);
        if (!$this->employee)
            throw new NotFoundException();

        $stmt = PDOProvider::get()->prepare("SELECT employee.login, employee.admin,room.room_id,employee.name, room.name, employee.surname, employee.job, employee.wage FROM employee INNER JOIN room ON employee.room = room.room_id WHERE employee.employee_id = :employeeId");
        $stmtKey = PDOProvider::get()->prepare("Select r.room_id ,r.name FROM room r JOIN `key` k ON r.room_id = k.room Where k.employee=:employeeId");
        $stmt->execute(['employeeId' => $employeeId]);
        $stmtKey->execute(['employeeId' => $employeeId]);

        $this->rooms = $stmt->fetchAll();
        $this->keys = $stmtKey->fetchAll();

        $subSurname = substr($this->employee->surname, 0, 1);
        $this->title = "Detail zaměstnance {$this->employee->name} {$subSurname}.";
    }

    protected function pageBody()
    {
        //prezentovat data
        return MustacheProvider::get()->render(
            'employeeDetail',
            ['employee' => $this->employee, 'rooms' => $this->rooms, 'keys' => $this->keys]
        );
    }
}

if (empty($_SESSION['id'])) {
    header("Location: /index.php");
} else {
    $page = new EmployeeDetailPage();
    $page->render();
}
