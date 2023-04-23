<?php
session_start();
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class RoomCreatePage extends CRUDPage
{
    private ?Room $room;
    private ?array $errors = [];
    private int $state;

    protected function extraHTMLHeaders(): string
    {
        return '<link href="/styles/styleError" rel="stylesheet">';
    }

    protected function checkNo($numberOfRoom, $phoneOfRoom): bool
    {
        $stmtNoOfRoom = PDOProvider::get()->prepare("SELECT room_id FROM room Where no =:no");
        $stmtNoOfRoom->execute(['no' => $numberOfRoom]);

        $stmtPhoneOfRoom = PDOProvider::get()->prepare("SELECT room_id FROM room Where phone =:phone");
        $stmtPhoneOfRoom->execute(['phone' => $phoneOfRoom]);

        if ($stmtNoOfRoom->rowCount() !== 0) {
            $this->errors['no'] = "Toto číslo místnosti už má jiná místnost";
        }
        if ($stmtPhoneOfRoom->rowCount() !== 0) {
            $this->errors['phone'] = "Toto tel. číslo místnosti už má jiná místnost";
        }

        return count($this->errors) === 0;
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
        $this->title = "Založit novou místnost";

        //když chce formulář
        if ($this->state === self::STATE_FORM_REQUESTED) {
            //jdi dál
            $this->room = new Room();
        }

        //když poslal data
        elseif ($this->state === self::STATE_DATA_SENT) {
            //načti je
            $this->room = Room::readPost();
            //zkontroluj je, jinak formulář
            $this->errors = [];
            $this->checkNo($this->room->no, $this->room->phone);
            $this->room->validate($this->errors);
            if ($this->errors) {
                $this->state = self::STATE_FORM_REQUESTED;
            } else {
                //ulož je
                $success = $this->room->insert();

                //přesměruj
                $this->redirect(self::ACTION_INSERT, $success);
            }
        }
    }

    public function isUnique($value, $array): bool
    {
        $isunique = true;
        foreach ($array as $val) {

            if ($value === $val) {
                $isunique = false;
                break;
            }
        }
        return $isunique;
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
    $page = new RoomCreatePage();
    $page->render();
}
