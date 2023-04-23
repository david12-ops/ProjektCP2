<?php
session_start();
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class EmployeesPage extends CRUDPage
{
    private $alert = [];

    public function __construct()
    {
        $this->title = "Výpis zaměstnanců";
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
                $message = 'Zaměstnanec byl přidán';
            } else if ($crudAction === self::ACTION_UPDATE) {
                $message = 'Úprava zaměstnance byla úspěšná';
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
        $emploeyees = Employee::getAll(['name' => 'ASC']);
        //prezentovat data

        if ($this->isAdmin()) {
            $html .= MustacheProvider::get()->render('employeeList', ['employee' => $emploeyees]);
        } else {
            $html .= MustacheProvider::get()->render('employeeUserList', ['employee' => $emploeyees]);
        }
        return $html;
    }
}

if (empty($_SESSION['id'])) {
    header("Location: /index.php");
} else {
    $page = new EmployeesPage();
    $page->render();
}
