<?php
session_start();
require_once __DIR__ . "/../bootstrap/bootstrap.php";

class Login extends CRUDPage
{
    private ?array $errors = [];
    private int $state;

    public function __construct()
    {
        $this->title = "Login";
    }
    protected function pageHeader(): string
    {
        return "";
    }

    protected function extraHTMLHeaders(): string
    {
        return '<link href="/styles/styleLogin.css" rel="stylesheet">';
    }
    protected function pageFooter(): string
    {
        return "";
    }

    protected function valiadteIput($loginName, $pass): bool
    {
        if (empty($loginName)) {
            $this->errors['login'] = "Uživatelské jméno musí být vyplněno";
        } elseif (empty($pass)) {
            $this->errors['password'] = "Heslo musí být vyplněno";
        }

        return count($this->errors) === 0;
    }

    protected function prepare(): void
    {
        parent::prepare();
        $this->findState();

        //když poslal data
        if ($this->state === self::STATE_DATA_SENT) {
            if (isset($_POST['uname']) && isset($_POST['password'])) {
                function validateData($data)
                {
                    $data = trim($data);
                    $data = stripslashes($data);
                    $data = htmlspecialchars($data);
                    return $data;
                }
                $uname = validateData($_POST['uname']);
                $password = validateData($_POST['password']);

                $stmtUser = PDOProvider::get()->prepare("SELECT employee_id, password FROM employee WHERE login =:User");
                $stmtUser->execute(['User' => $uname]);
                $User = $stmtUser->fetch();


                if ($this->valiadteIput($uname, $password)) {
                    if (!$User || !password_verify($password, $User->password)) {
                        $this->errors['error'] = "Nesprávné uživatelské jméno nebo heslo";
                    } else {
                        $_SESSION['id'] = $User->employee_id;
                        header("Location: FirstPage.php");
                        exit();
                    }
                }
            }
        }
    }

    protected function pageBody()
    {
        return MustacheProvider::get()->render(
            'loginForm',
            [
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


$page = new Login();
$page->render();
