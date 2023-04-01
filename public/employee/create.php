<?php
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class EmployeeCreatePage extends CRUDPage
{
    private ?Employee $employee;
    private ?array $errors = [];
    private int $state;
    private ?array $keys = [];
    private $room;

    protected function insertKey($success)
    {
        if ($success) {
            foreach ($this->keys as $key) {

                $stmtInsert = PDOProvider::get()->prepare("INSERT INTO `key` (employee, room) VALUES (:employeeId, :roomId)");
                $success = $stmtInsert->execute(['employeeId' => $this->employee->employee_id, 'roomId' => $key]);

                if (!$success) {
                    break;
                }
            }
        }
    }

    protected function prepare(): void
    {
        parent::prepare();
        $this->findState();
        $this->title = "Přidat nového zaměstnance";
        if ($this->state === self::STATE_FORM_REQUESTED) {

            $this->employee = new Employee();
        } elseif ($this->state === self::STATE_DATA_SENT) {
            $this->employee = Employee::readPost();
            $this->keys = filter_input(INPUT_POST, 'keys', FILTER_DEFAULT, FILTER_FORCE_ARRAY);

            $this->errors = [];
            if (!$this->keys || !in_array($this->employee->room, $this->keys)) {
                $this->errors['keys'] = "Zaměstnanec by měl mít minimálně klíč od své nové místnosti";
            }

            $isOk = $this->employee->validate($this->errors);
            if (!$isOk) {
                $this->state = self::STATE_FORM_REQUESTED;
            } else {

                $success = $this->employee->insert();
                $this->insertKey($success);

                $this->redirect(self::ACTION_INSERT, $success);
            }
        }
    }

    protected function pageBody()
    {
        $stmtKeys = PDOProvider::get()->query("SELECT room_id rID, name, no FROM room");
        $stmtRooms = PDOProvider::get()->query("SELECT r.room_id rID, r.name rName, r.no rNo FROM room r");
        $this->keys = $stmtKeys->fetchAll();
        $this->room = $stmtRooms->fetchAll();

        return MustacheProvider::get()->render(
            'employeeForm',
            [
                'title' => $this->title,
                'employee' => $this->employee,
                'keys' => $this->keys,
                'room' => $this->room,
                'errors' => $this->errors,
            ]
        );
    }

    private function findState(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST')
            $this->state = self::STATE_DATA_SENT;
        else
            $this->state = self::STATE_FORM_REQUESTED;
    }
}

$page = new EmployeeCreatePage();
$page->render();
