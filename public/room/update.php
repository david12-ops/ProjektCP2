<?php
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class RoomUpdatePage extends CRUDPage
{
    private ?Room $room;
    private ?array $errors = [];
    private int $state;

    protected function prepare(): void
    {
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

$page = new RoomUpdatePage();
$page->render();
