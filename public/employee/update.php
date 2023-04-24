<?php
session_start();
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class EmployeeUpdatePage extends CRUDPage
{
    private ?employee $employee;
    private ?array $errors = [];
    private int $state;
    private ?array $keys;
    private ?array $activeKeys;
    private $room;
    private $activeRoom;
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

    protected function checkLogin($loginFromForm)
    {
        //Vybrat login, který ale nemá login upravovaného zaměstnance
        $stmtLogin = PDOProvider::get()->prepare("SELECT login FROM employee WHERE login =:login AND employee_id !=:employeeId");
        $stmtLogin->execute(['employeeId' => $this->employee->employee_id, 'login' => $loginFromForm]);

        //Pokud je záznam, tak vypíše chybu
        if ($stmtLogin->rowCount() !== 0) {
            $this->errors['login'] = "Toto uživatelské jméno už má jiný zaměstnanec";
        }
    }

    protected function updateKey($success)
    {
        $stmtDelete = PDOProvider::get()->prepare("DELETE FROM `key` WHERE `key`.employee =:employeeId");
        $stmtDelete->execute(['employeeId' => $this->employee->employee_id]);
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

    protected function checkPassword($password)
    {
        //Načti z inputu potvrzovací heslo
        $this->confirmPass = filter_input(INPUT_POST, 'confirmPassword', FILTER_DEFAULT);
        //preg_match('`[A-Z]`',$password)  jedno velké písmeno 
        //preg_match('`[a-z]`',$password)  jedno malé písmeno 
        //preg_match('`[0-9]`',$password)  jedno číslo 
        //preg_match('`[\$\*\.,+\-=@]`', $this->password) jeden ze symbolů 

        //Pokud bude vyplněné
        if (trim($password)) {
            //Pokud bude menší než 8 nebo nebude splňovat preg_match
            if (strlen($password) < 8) {
                $this->errors['password'] = 'Heslo musí být minimálně 8 znaků dlouhé';
            } elseif (!preg_match('`[A-Z]`', $password) || !preg_match('`[a-z]`', $password) || !preg_match('`[0-9]`', $password) || !preg_match('`[\$\*\.,+\-=@]`', $password)) {
                $this->errors['password'] = 'Heslo musí obsahovat malé a velké písmeno, číslice a symboly z těchto vybraných ($,*,tečka(.),čárka(,),+,-,=,@)';
            }

            //Pokud se neshodují hesla
            if ($password !== $this->confirmPass) {
                $this->errors['confirmPassword'] = 'Hesla se neshodují';
            }
        }

        //Pokud bude vyplněný potvrzovací heslo a zároveň nebude heslo
        if (!trim($password) && trim($this->confirmPass)) {
            $this->errors['confirmPassword'] = 'Pro potvrzení hesla je třeba vyplnit vaše heslo';
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

    protected function checkAdminCheckbox($isAdmin, $id)
    {
        if ($id === $_SESSION['id']) {
            if ($isAdmin === 0) {
                $this->errors['admin'] = 'Přihlášený admin nemůže upravovat své práva';
            }
        }
    }

    protected function prepare(): void
    {
        if (!$this->isAdmin()) {
            throw new ForbiddenException();
        }

        parent::prepare();
        $this->findState();
        $this->title = "Upravit zaměstnance";

        //když chce formulář
        if ($this->state === self::STATE_FORM_REQUESTED) {
            $employeeId = filter_input(INPUT_GET, 'employeeId', FILTER_VALIDATE_INT);
            if (!$employeeId)
                throw new BadRequestException();
            $this->employee = Employee::findByID($employeeId);
            if (!$this->employee)
                throw new NotFoundException();
        }

        //když poslal data
        elseif ($this->state === self::STATE_DATA_SENT) {
            //načti je
            $this->employee = Employee::readPost();
            $this->errors = [];

            //Kontrola klíčů
            $this->checkKey();
            //Kontrola hesla
            $this->checkPassword($this->employee->password);
            //Kontrola uživatelskýho jména
            $this->checkLogin($this->employee->login);
            //Kontrola přihlášeného admina pro kontrolu uprav svých práv
            $this->checkAdminCheckbox($this->employee->admin, $this->employee->employee_id);
            //Kontrola zaměstnance
            $this->employee->validate($this->errors);

            if ($this->errors) {
                $this->state = self::STATE_FORM_REQUESTED;
            } else {
                //ulož je
                $this->employee->password = password_hash($this->employee->password, PASSWORD_DEFAULT);
                $success = $this->employee->update($this->employee->password);
                $this->updateKey($success);

                //přesměruj
                $this->redirect(self::ACTION_UPDATE, $success);
            }
        }
    }

    protected function pageBody()
    {
        $stmtKeys = PDOProvider::get()->query("SELECT room_id,room_id rID, name, no FROM room");
        $this->keys = $stmtKeys->fetchAll(PDO::FETCH_UNIQUE);

        $stmtActiveKeys = PDOProvider::get()->prepare("SELECT r.room_id,r.room_id rID, r.name, r.no from room r Join `key` k on r.room_id = k.room Join employee e on k.employee = e.employee_id WHERE employee_id =:employeeId");
        $stmtActiveKeys->execute(['employeeId' => $this->employee->employee_id]);
        $this->activeKeys = $stmtActiveKeys->fetchAll(PDO::FETCH_UNIQUE);

        $this->keys = array_diff_key($this->keys, $this->activeKeys);

        if ($this->employee->room) {
            $stmtRooms = PDOProvider::get()->query("SELECT r.room_id rID, r.name rName, r.no rNo FROM room r Where r.room_id != {$this->employee->room}");
            $this->room = $stmtRooms->fetchAll();

            $stmtActiveRooms = PDOProvider::get()->query("SELECT r.room_id rID, r.name rName, r.no rNo FROM room r Where r.room_id = {$this->employee->room}");
            $this->activeRoom = $stmtActiveRooms->fetchAll();
        } else {
            $stmtRooms = PDOProvider::get()->query("SELECT r.room_id rID, r.name rName, r.no rNo FROM room r");
            $this->room = $stmtRooms->fetchAll();
        }

        return MustacheProvider::get()->render(
            'employeeAdminForm',
            [
                'title' => $this->title,
                'employee' => $this->employee,
                'room' => $this->room,
                'activeRoom' => $this->activeRoom,
                'keys' => array_values($this->keys),
                'activeKeys' => array_values($this->activeKeys),
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
    $page = new EmployeeUpdatePage();
    $page->render();
}
