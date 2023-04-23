<?php
session_start();
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class RoomUpdatePage extends CRUDPage
{
    private ?Room $room;
    private ?array $errors = [];
    private int $state;

    protected function extraHTMLHeaders(): string
    {
        return '<link href="/styles/styleError" rel="stylesheet">';
    }

    protected function checkRoom($numberOfRoomFromForm, $phoneNumberFromForm)
    {
        //Vybrat číslo krom upravované místnosti
        $stmtNoOfRoom = PDOProvider::get()->prepare("SELECT room_id FROM room WHERE no =:no AND room_id !=:roomId");
        $stmtNoOfRoom->execute(['roomId' => $this->room->room_id, 'no' => $numberOfRoomFromForm]);

        //Vybrat tel. číslo krom upravované místnosti
        $stmtPhoneOfRoom = PDOProvider::get()->prepare("SELECT room_id FROM room WHERE phone =:phone AND room_id !=:roomId");
        $stmtPhoneOfRoom->execute(['roomId' => $this->room->room_id, 'phone' => $phoneNumberFromForm]);

        //Pokud je záznam
        if ($stmtNoOfRoom->rowCount() !== 0) {
            //Vypiš chybovou hlášku
            $this->errors['no'] = "Toto číslo místnosti už má jiná místnost";
        }
        //Pokud je záznam 
        if ($stmtPhoneOfRoom->rowCount() !== 0) {
            //Vypiš chybovou hlášku
            $this->errors['phone'] = "Toto tel. číslo místnosti už má jiná místnost";
        }
    }

    private function isAdmin(): bool
    {
        $stmtAdmin = PDOProvider::get()->query("SELECT `admin` FROM employee WHERE employee_id ={$_SESSION['id']}");
        $Admin = $stmtAdmin->fetch();

        if ($Admin->admin === 1) {
            return  true;
        } else {
            return  false;
        }
    }

    protected function prepare(): void
    {
        if (!$this->isAdmin()) {
            throw new ForbiddenException();
        }

        parent::prepare();
        $this->findState();
        $this->title = "Upravit místnost";

        //když chce formulář
        if ($this->state === self::STATE_FORM_REQUESTED) {
            $roomId = filter_input(INPUT_GET, 'roomId', FILTER_VALIDATE_INT);
            if (!$roomId)
                throw new BadRequestException();

            //jdi dál
            $this->room = Room::findByID($roomId);
            if (!$this->room)
                throw new NotFoundException();
        }

        //když poslal data
        elseif ($this->state === self::STATE_DATA_SENT) {
            //načti je
            $this->room = Room::readPost();

            //zkontroluj je, jinak formulář
            $this->errors = [];

            //Kontrola čísla místnosti
            $this->checkRoom($this->room->no, $this->room->phone);

            $isOk = $this->room->validate($this->errors);
            if (!$isOk) {
                $this->state = self::STATE_FORM_REQUESTED;
            } else {
                //ulož je
                $success = $this->room->update();

                //přesměruj
                $this->redirect(self::ACTION_UPDATE, $success);
            }
        }
    }

    protected function pageBody()
    {
        return MustacheProvider::get()->render(
            'roomForm',
            [
                'title' => $this->title,
                'room' => $this->room,
                'errors' => $this->errors,
                'extraHeaders' => $this->extraHTMLHeaders()
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

if (empty($_SESSION['id'])) {
    header("Location: /index.php");
} else {
    $page = new RoomUpdatePage();
    $page->render();
}
