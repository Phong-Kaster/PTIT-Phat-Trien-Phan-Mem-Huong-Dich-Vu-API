<?php
/**
 * Login Controller
 */
class LoginController extends Controller
{
    /**
     * Process
     */
    public function process()
    {
        $AuthUser = $this->getVariable("AuthUser");
        if ($AuthUser) {
            $this->resp->result = 1;
            $this->resp->msg = __("You are Already Logged in");
            $this->jsonecho();
        }
        $this->login();
    }


    /**
     * Login
     * @return void
     */
    private function login()
    {
        $this->resp->result = 0;
        $username = Input::post("username");
        $password = Input::post("password");

        if ($username && $password) {
            $User = Controller::model("User", $username);

            if ($User->isAvailable() &&
                $User->get("is_active") == 1 &&
                password_verify($password, $User->get("password"))) 
            {
                $data = array(
                    "account_type" => $User->get("account_type"),
                    "email" => $User->get("email"),
                    "firstname" => $User->get("firstname"),
                    "lastname" => $User->get("lastname"),
                    "avatar" => $User->get("avatar"),
                    "id" => (int)$User->get("id"),
                    "is_active" => (bool)$User->get("is_active"),
                    "date" => $User->get("date"),
                );

                $payload = $data;

                $payload["hashPass"] = md5($User->get("password"));
                $payload["iat"] = time();

                $jwt = Firebase\JWT\JWT::encode($payload, MP_SALT);

                $this->resp->result = 1;
                $this->resp->msg = __("Your account has been logged in successfully");
                $this->resp->accessToken = $jwt;
                $this->resp->data = $data;

                $this->jsonecho();
            }
        }

        $this->resp->msg = __("Login credentials didn't match!");
        $this->jsonecho();
    }
}