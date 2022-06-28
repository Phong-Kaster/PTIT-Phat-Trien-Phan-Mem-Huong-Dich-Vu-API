<?php
/**
 * Signup Controller
 */
class SignupController extends Controller
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
        $this->signup();
    }


    /**
     * Signup
     * @return void
     */
    private function signup()
    {
        $this->resp->result = 0;
        $required_fields  = [
            "firstname", "lastname", "email", 
            "password", "password-confirm",
        ];

        $required_ok = true;
        foreach ($required_fields as $field) {
            if (!Input::post($field)) {
                $this->resp->msg = __("Missing some required field!");
                $this->jsonecho();
            }
        }


        if (!filter_var(Input::post("email"), FILTER_VALIDATE_EMAIL)) {
            $this->resp->msg = __("Email is not valid!");
            $this->jsonecho();
        } else {
            $User = Controller::model("User", Input::post("email"));
            if ($User->isAvailable()) {
                $this->resp->msg = __("Email is not available!");
                $this->jsonecho();
            }
        }

        if (mb_strlen(Input::post("password")) < 6) {
            $this->resp->msg = __("Password must be at least 6 character length!");
            $this->jsonecho();
        } else if (Input::post("password-confirm") != Input::post("password")) {
            $this->resp->msg = __("Password confirmation didn't match!");
            $this->jsonecho();
        }

       
        try 
        {        
            $User->set("email", strtolower(Input::post("email")))
                ->set("password", password_hash(Input::post("password"), PASSWORD_DEFAULT))
                ->set("firstname", Input::post("firstname"))
                ->set("lastname", Input::post("lastname"))
                ->set("is_active", 1)
                ->save();

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
            $this->resp->accessToken = $jwt;
            $this->resp->data = $data;
            $this->resp->msg = __("Your account has been created successfully!");
        } 
        catch (\Exception $ex) 
        {
            $this->resp->msg = __("Oops! Something went wrong. Please try again!");
        }
        $this->jsonecho();
    }
}