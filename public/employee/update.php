<?php
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class EmployeeUpdatePage extends CRUDPage
{
    // + možnost odebírat a přidávat klíče
    private ?employee $employee;
    private ?array $errors = [];
    private int $state;
    private ?array $keys;
    private $room;

    protected function updateKey($success)
    {
        $stmtDelete = PDOProvider::get()->prepare("DELETE FROM `key` WHERE `key`.employee =:employeeId");
        $stmtDelete->execute(['employeeId' => $this->employee->employee_id]);
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
        $this->title = "Upravit zaměstnance";

        //když chce formulář
        if ($this->state === self::STATE_FORM_REQUESTED) {
            $employeeId = filter_input(INPUT_GET, 'employeeId', FILTER_VALIDATE_INT);
            if (!$employeeId)
                throw new BadRequestException();
            //jdi dál
            $this->employee = Employee::findByID($employeeId);
            if (!$this->employee)
                throw new NotFoundException();
        }

        //když poslal data
        elseif ($this->state === self::STATE_DATA_SENT) {
            //načti je
            $this->employee = Employee::readPost();

            $this->errors = [];
            $this->keys = filter_input(INPUT_POST, 'keys', FILTER_DEFAULT, FILTER_FORCE_ARRAY);
            if (!$this->keys || !in_array($this->employee->room, $this->keys)) {
                $this->errors['keys'] = "Zaměstnanec by měl mít minimálně klíč od své nové místnosti";
            }
            var_dump($this->keys);

            $isOk = $this->employee->validate($this->errors);
            if (!$isOk) {
                $this->state = self::STATE_FORM_REQUESTED;
            } else {
                //ulož je

                $success = $this->employee->update();
                $this->updateKey($success);

                //přesměruj
                $this->redirect(self::ACTION_UPDATE, $success);
            }
        }
    }

    protected function pageBody()
    {
        $stmtKeys = PDOProvider::get()->query("SELECT room_id rID, name, no FROM room");
        $this->keys = $stmtKeys->fetchAll();
        $stmtRooms = PDOProvider::get()->query("SELECT r.room_id rID, r.name rName, r.no rNo FROM room r");
        $this->room = $stmtRooms->fetchAll();

        return MustacheProvider::get()->render(
            'employeeForm',
            [
                'title' => $this->title,
                'employee' => $this->employee,
                'room' => $this->room,
                'keys' => $this->keys,
                'errors' => $this->errors
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

$page = new EmployeeUpdatePage();
$page->render();
