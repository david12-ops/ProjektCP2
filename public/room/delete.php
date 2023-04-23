<?php
session_start();

require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class RoomDeletePage extends CRUDPage
{
    private $IsOk;
    private $nameOfEmp;
    private $surnameOfemp;

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
    protected function extraHTMLHeaders(): string
    {
        return '<link href="/styles/stylePopUp.css" rel="stylesheet"><link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">';
    }

    public function __construct()
    {
        $this->title = "Info o mazání";
    }

    protected function prepare(): void
    {
        if (!$this->isAdmin()) {
            throw new ForbiddenException();
        }

        parent::prepare();
        $roomId = filter_input(INPUT_POST, 'roomId', FILTER_VALIDATE_INT);
        if (!$roomId)
            throw new BadRequestException();

        //když poslal data
        //sql jestli je v místnosti člověk
        $stmt = PDOProvider::get()->query("SELECT `surname`, `name`, `employee_id` FROM `employee` WHERE `room`= {$roomId}");
        $people = $stmt->fetch();

        //pokud tam bude
        if ($people) {
            $this->nameOfEmp = $people->name;
            $this->surnameOfemp = $people->surname;
            $this->IsOk = false;
        } else {
            //smaž
            $success = Room::deleteByID($roomId);
            //přesměruj
            $this->redirect(self::ACTION_DELETE, $success);
        }
    }

    protected function pageBody()
    {
        if (!$this->IsOk) {
            return MustacheProvider::get()->render(
                'PopupWindow',
                [
                    'title' => "Místnost nelze smazat",
                    'description' => "Nelze smazat místnost z důvodu že je to domovská místnost zaměstnance {$this->nameOfEmp} {$this->surnameOfemp}. Pro smazání místnosti je třeba smazat zaměstnance.",
                    'extraHeaders' => $this->extraHTMLHeaders()
                ]
            );
        }
    }
}

if (empty($_SESSION['id'])) {
    header("Location: /index.php");
} else {
    $page = new RoomDeletePage();
    $page->render();
}
