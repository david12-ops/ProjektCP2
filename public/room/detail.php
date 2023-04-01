<?php
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class RoomDetailPage extends BasePage
{
    //upozornit na to že zamšstnanec ma klic od místnosti
    private $room;
    private $employees;
    private $key;

    protected function prepare(): void
    {
        parent::prepare();
        //získat data z GET
        $roomId = filter_input(INPUT_GET, 'roomId', FILTER_VALIDATE_INT);
        if (!$roomId)
            throw new BadRequestException();

        //najít místnost v databázi
        $this->room = Room::findByID($roomId);
        if (!$this->room)
            throw new NotFoundException();

        $stmt = PDOProvider::get()->prepare("SELECT `surname`, `name`, `employee_id` FROM `employee` WHERE `room`= :roomId ORDER BY `surname`, `name`");
        $stmtKey = PDOProvider::get()->prepare("Select e.employee_id ,e.name, e.surname FROM employee e JOIN `key` k ON e.employee_id = k.employee Where k.room=:roomId");
        $stmt->execute(['roomId' => $roomId]);
        $stmtKey->execute(['roomId' => $roomId]);
        $this->employees = $stmt->fetchAll();
        $this->key = $stmtKey->fetchAll();

        $this->title = "Detail místnosti {$this->room->no}";
    }

    protected function pageBody()
    {
        //prezentovat data
        return MustacheProvider::get()->render(
            'roomDetail',
            ['room' => $this->room, 'employees' => $this->employees, 'key' => $this->key]
        );
    }
}

$page = new RoomDetailPage();
$page->render();
