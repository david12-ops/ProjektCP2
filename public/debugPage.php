<?php
require_once __DIR__ . "/../bootstrap/bootstrap.php";

class DebugPage extends BasePage
{
    private $room;
    private $employee;

    protected function pageBody()
    {
        //        $this->room = new Room();
        //        $this->room->room_id = 10;
        //        $this->room->name = "Kartáčovna";
        //        $this->room->phone = null;
        //        $this->room->no = "331";
        //
        //        $this->room->insert();

        $this->room = Room::findByID(1);
        $this->employee = Employee::findByID(1);
        //        $this->room->name = "Čistírna";
        //        dump($this->room->update());

        //        $this->room = Room::findByID(15);
        //        dump(Room::deleteByID(15));

        //        $this->room = new Room();

        $errorsRoom = [
            'no' => 'pole je povinné',
            'name' => 'jméno nesmí obsahovat emotikony'
        ];

        $errorsEmp = [
            'name' => 'jméno nesmí obsahovat emotikony'
        ];

        //prezentovat data
        return MustacheProvider::get()->render(
            'roomForm',
            [
                'room' => $this->room,
                'errors' => $errorsRoom
            ],
            'employeeForm',
            [
                'employee' => $this->employee,
                'errors' => $errorsEmp,
            ]
        );
    }
}

//Sem přidat employees

$page = new DebugPage();
$page->render();
