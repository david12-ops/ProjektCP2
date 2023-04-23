<?php
session_start();
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class EmployeeDeletePage extends CRUDPage
{
    private $isLoggedAdmin;
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

        $employeeId = filter_input(INPUT_POST, 'employeeId', FILTER_VALIDATE_INT);
        if (!$employeeId)
            throw new BadRequestException();

        //když poslal data a je přihlášený admin
        if ($employeeId === $_SESSION['id']) {
            //nastaví vlastnost na true
            $this->isLoggedAdmin = true;
        } else {
            //pokud není přihlášený admin, tak provede smazání zaměstnance
            $success = Employee::deleteByID($employeeId);
            //přesměruj
            $this->redirect(self::ACTION_DELETE, $success);
        }
    }

    protected function pageBody()
    {
        if ($this->isLoggedAdmin) {
            return MustacheProvider::get()->render(
                'PopupWindow',
                [
                    'title' => "Admin nemůže mazat sám sebe",
                    'description' => "Přihlášený admin nemůže mazat sám sebe. To musí jíny uživatel s právem správce",
                    'extraHeaders' => $this->extraHTMLHeaders()
                ]
            );
        }
    }
}

if (empty($_SESSION['id'])) {
    header("Location: /index.php");
} else {
    $page = new EmployeeDeletePage();
    $page->render();
}
