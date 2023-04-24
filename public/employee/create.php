<?php
session_start();
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class EmployeeCreatePage extends CRUDPage
{
    private ?Employee $employee;
    private ?array $errors = [];
    private int $state;
    private ?array $keys = [];
    private $room;
    private $confirmPass;

    protected function extraHTMLHeaders(): string
    {
        return '<link href="/styles/styleError.css" rel="stylesheet">';
    }

    protected function checkKey()
    {
        $this->keys = filter_input(INPUT_POST, 'keys', FILTER_DEFAULT, FILTER_FORCE_ARRAY);

        if (!$this->keys || !in_array($this->employee->room, $this->keys)) {
            $this->errors['keys'] = "Zaměstnanec by měl mít minimálně klíč od své nové místnosti";
        }
    }

    protected function checkPassword($password)
    {
        //Načti z inputu potvrzovací heslo
        $this->confirmPass = filter_input(INPUT_POST, 'confirmPassword', FILTER_DEFAULT);
        //preg_match('`[A-Z]`',$password)  at least one upper case 
        //preg_match('`[a-z]`',$password)  at least one lower case 
        //preg_match('`[0-9]`',$password)  at least one digit 
        //preg_match('`[\$\*\.,+\-=@]`', $this->password) at least one of this symbols 
        if (!isset($password) || (!$password)) {
            $this->errors['password'] = 'Heslo musí být vyplněné';
        } elseif (strlen($password) < 8) {
            $this->errors['password'] = 'Heslo musí být minimálně 8 znaků dlouhé';
        } elseif (!preg_match('`[A-Z]`', $password) || !preg_match('`[a-z]`', $password) || !preg_match('`[0-9]`', $password) || !preg_match('`[\$\*\.,+\-=@]`', $password)) {
            $this->errors['password'] = 'Heslo musí obsahovat malé a velké písmeno, číslice a symboly z těchto vybraných ($,*,tečka(.),čárka(,),+,-,=,@)';
        }

        //Pokud se neshodují hesla
        if (trim($password) !== trim($this->confirmPass)) {
            $this->errors['confirmPassword'] = 'Hesla se neshodují';
        }
    }

    protected function checkLogin($login)
    {
        //Vybrat employee_id toho kdo má potencionálně stejý login
        $stmtLoginOfUser = PDOProvider::get()->prepare("SELECT employee_id FROM employee Where login =:login");
        $stmtLoginOfUser->execute(['login' => $login]);

        //Pokud je záznam
        if ($stmtLoginOfUser->rowCount() !== 0) {
            //Vypíše chybu
            $this->errors['login'] = "Toto uživatelské jméno už má jiný zaměstnanec";
        }
    }

    protected function insertKey($success)
    {
        if ($success) {
            foreach ($this->keys as $key) {

                $stmtInsert = PDOProvider::get()->prepare("INSERT INTO `key` (employee, room) VALUES (:employeeId, :roomId)");
                $success = $stmtInsert->execute(['employeeId' => $this->employee->employee_id, 'roomId' => $key]);

                if (!$success) {
                    break;
                }
            }
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
        $this->title = "Přidat nového zaměstnance";
        if ($this->state === self::STATE_FORM_REQUESTED) {

            $this->employee = new Employee();
        } elseif ($this->state === self::STATE_DATA_SENT) {
            $this->employee = Employee::readPost();
            $this->errors = [];

            //Kontrola uživatelskýho jména
            $this->checkLogin($this->employee->login);
            //Kontrola hesla
            $this->checkPassword($this->employee->password);
            //Kontrola klíčů
            $this->checkKey();
            //Kontrola zaměstnance
            $this->employee->validate($this->errors);

            if ($this->errors) {
                $this->state = self::STATE_FORM_REQUESTED;
            } else {
                $this->employee->password = password_hash($this->employee->password, PASSWORD_DEFAULT);
                $success = $this->employee->insert();
                $this->insertKey($success);

                $this->redirect(self::ACTION_INSERT, $success);
            }
        }
    }

    protected function pageBody()
    {
        $stmtKeys = PDOProvider::get()->query("SELECT room_id rID, name, no FROM room");
        $stmtRooms = PDOProvider::get()->query("SELECT r.room_id rID, r.name rName, r.no rNo FROM room r");
        $this->keys = $stmtKeys->fetchAll();
        $this->room = $stmtRooms->fetchAll();

        return MustacheProvider::get()->render(
            'employeeAdminForm',
            [
                'title' => $this->title,
                'employee' => $this->employee,
                'keys' => $this->keys,
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
    $page = new EmployeeCreatePage();
    $page->render();
}
