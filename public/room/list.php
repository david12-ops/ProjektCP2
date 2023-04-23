<?php
session_start();
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class RoomsPage extends CRUDPage
{
    private $alert = [];

    public function __construct()
    {
        $this->title = "Výpis místností";
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
        parent::prepare();
        //pokud přišel výsledek, zachytím ho
        $crudResult = filter_input(INPUT_GET, 'success', FILTER_VALIDATE_INT);
        $crudAction = filter_input(INPUT_GET, 'action');

        if (is_int($crudResult)) {
            $this->alert = [
                'alertClass' => $crudResult === 0 ? 'danger' : 'success'
            ];

            $message = '';
            if ($crudResult === 0) {
                $message = 'Operace nebyla úspěšná';
            } else if ($crudAction === self::ACTION_DELETE) {
                $message = 'Smazání proběhlo úspěšně';
            } else if ($crudAction === self::ACTION_INSERT) {
                $message = 'Místnost založena úspěšně';
            } else if ($crudAction === self::ACTION_UPDATE) {
                $message = 'Úprava místnosti byla úspěšná';
            }

            $this->alert['message'] = $message;
        }
    }


    protected function pageBody()
    {
        $html = "";
        //zobrazit alert
        if ($this->alert) {
            $html .= MustacheProvider::get()->render('crudResult', $this->alert);
        }

        //získat data
        $rooms = Room::getAll(['name' => 'ASC']);
        //prezentovat data
        if ($this->isAdmin() === true) {
            $html .= MustacheProvider::get()->render('roomList', ['rooms' => $rooms]);
        } else {
            $html .= MustacheProvider::get()->render('roomUserList', ['rooms' => $rooms]);
        }

        return $html;
    }
}

if (empty($_SESSION['id'])) {
    header("Location: /index.php");
} else {
    $page = new RoomsPage();
    $page->render();
}
