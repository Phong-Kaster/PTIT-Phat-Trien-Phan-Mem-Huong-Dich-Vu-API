<?php
/**
 * ChangePassword Controller
 */
class ChangePasswordController extends Controller
{
    /**
     * Process
     */
    public function process()
    {
        $AuthUser = $this->getVariable("AuthUser");

        if (!$AuthUser){
            $this->resp->result = 0;
            $this->resp->msg = __("An active access token must be used to query information about the current user.");
            $this->jsonecho();
        }
        
        $this->save();
    }


    /**
     * Save changes
     * @return void 
     */
    private function save()
    {
        $this->resp->result = 0;
        $AuthUser = $this->getVariable("AuthUser");


        // Check required fields
        $required_fields = ["current-password", "password", "password-confirm"];

        foreach ($required_fields as $field) {
            if (!Input::post($field)) {
                $this->resp->msg = __("Missing some of required data.");
                $this->jsonecho();
            }
        }

        if(!password_verify(Input::post("current-password"), $AuthUser->get("password")) ){
            $this->resp->msg = __("The current password you have entered is incorrect");
            $this->jsonecho();
        }
        // Check pass.
        if (mb_strlen(Input::post("password")) < 6) {
          $this->resp->msg = __("Password must be at least 6 character length!");
          $this->jsonecho();
        } 

        if (Input::post("password-confirm") != Input::post("password")) {
            $this->resp->msg = __("Password confirmation didn't match!");
            $this->jsonecho();
        }

        $passhash = password_hash(Input::post("password"), PASSWORD_DEFAULT);
        $AuthUser->set("password", $passhash)
                 ->save();

        
        $data = array(
            "account_type" => $AuthUser->get("account_type"),
            "email" => $AuthUser->get("email"),
            "firstname" => $AuthUser->get("firstname"),
            "lastname" => $AuthUser->get("lastname"),
            "id" => (int)$AuthUser->get("id"),
            "is_active" => (bool)$AuthUser->get("is_active")
        );

        $payload = $data;

        $payload["hashPass"] = md5($AuthUser->get("password"));
        $payload["iat"] = time();
        $jwt = Firebase\JWT\JWT::encode($payload, MP_SALT);

            
        $this->resp->result = 1;
        $this->resp->accessToken = $jwt;
        $this->resp->data = $data;
        $this->resp->msg = __("Changes saved!");
        $this->jsonecho();
    }
}