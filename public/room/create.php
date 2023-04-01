<?php
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class RoomCreatePage extends CRUDPage
{
    private ?Room $room;
    private ?array $errors = [];
    private int $state;

    protected function prepare(): void
    {
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
            $isOk = $this->room->validate($this->errors);
            if (!$isOk) {
                $this->state = self::STATE_FORM_REQUESTED;
            } else {
                //ulož je
                $success = $this->room->insert();

                //přesměruj
                $this->redirect(self::ACTION_INSERT, $success);
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

$page = new RoomCreatePage();
$page->render();
