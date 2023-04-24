<?php
session_start();
require_once __DIR__ . "/../bootstrap/bootstrap.php";

class FirstBage extends CRUDPage
{
    public function __construct()
    {
        $this->title = "Prohlížeč databáze firmy";
    }

    protected function extraHTMLHeaders(): string
    {
        return '<link href="../styles/styleError.css" rel="stylesheet">';
    }

    private ?employee $employee;
    private ?array $errors = [];
    private int $state;
    private $newPassword;
    private $confirmPassword;
    private $pass;

    protected function checkNewPassword($password): int
    {
        $error = 0;
        //preg_match('`[A-Z]`',$password)  jedno velké písmeno 
        //preg_match('`[a-z]`',$password)  jedno malé písmeno 
        //preg_match('`[0-9]`',$password)  jedno číslo 
        //preg_match('`[\$\*\.,+\-=@]`', $this->password) jeden ze symbolů 
        if (!$password) {
            $this->errors['newPassword'] = 'Nové heslo musí být vyplněné krom mezer';
            $error = 1;
        } elseif (strlen($password) < 8) {
            $this->errors['newPassword'] = 'Heslo musí být minimálně 8 znaků dlouhé';
            $error = 1;
        } elseif (!preg_match('`[A-Z]`', $password) || !preg_match('`[a-z]`', $password) || !preg_match('`[0-9]`', $password) || !preg_match('`[\$\*\.,+\-=@]`', $password)) {
            $this->errors['newPassword'] = 'Heslo musí obsahovat malé a velké písmeno, číslice a symboly z těchto vybraných ($,*,tečka(.),čárka(,),+,-,=,@)';
            $error = 1;
        }

        return $error;
    }

    private function update($password): bool
    {
        $queryOfUpdtPass = "UPDATE employee  SET `password` = :password WHERE `employee_id` = :employeeId";
        $stmt = PDOProvider::get()->prepare($queryOfUpdtPass);
        $stmt->execute(['employeeId' => $_SESSION['id'], 'password' => $password]);
        if ($stmt)
            return true;
    }

    protected function checkPassOfUser($password): int
    {
        $error = 0;
        if (!password_verify($password, $this->employee->password)) {
            $this->errors['password'] = 'Heslo je nesprávné';
            $error = 1;
        }
        return $error;
    }

    protected function checkConfirmPass($newPassword, $confirmPassword): int
    {
        $error = 0;
        if ($newPassword != $confirmPassword) {
            $this->errors['confirmPass'] = 'Nové heslo a potvrzené heslo se neshoduje';
            $error = 1;
        }
        return $error;
    }

    protected function redirect(string $action, bool $success): void
    {
        $data = [
            'action' => $action,
            'success' => $success ? 1 : 0
        ];
        header('Location: employee/list.php?' . http_build_query($data));
        exit;
    }

    protected function prepare(): void
    {
        parent::prepare();
        $this->findState();
        $this->title = "Úprava uživatele";

        //když chce formulář
        $this->employee = Employee::findByID($_SESSION['id']);
        if (!$this->employee)
            throw new NotFoundException();

        //když poslal data
        if ($this->state === self::STATE_DATA_SENT) {
            //načti je
            $this->errors = [];
            $this->newPassword = filter_input(INPUT_POST, 'newPassword', FILTER_DEFAULT);
            $this->confirmPassword = filter_input(INPUT_POST, 'confirmPass', FILTER_DEFAULT);
            $this->pass = filter_input(INPUT_POST, 'password', FILTER_DEFAULT);

            $isOk = $this->checkPassOfUser($this->pass) + $this->checkNewPassword($this->newPassword) + $this->checkConfirmPass($this->newPassword, $this->confirmPassword);
            if ($isOk >= 1) {
                $this->state = self::STATE_FORM_REQUESTED;
            } else {
                $this->employee->password = password_hash($this->newPassword, PASSWORD_DEFAULT);
                //přesměruj a ulož
                $this->redirect(self::ACTION_UPDATE, $this->update($this->employee->password));
            }
        }
    }

    protected function pageBody()
    {
        return MustacheProvider::get()->render(
            'employeeUserForm',
            ['errors' => $this->errors,  'extraHeaders' => $this->extraHTMLHeaders()]
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
    $page = new FirstBage();
    $page->render();
}
