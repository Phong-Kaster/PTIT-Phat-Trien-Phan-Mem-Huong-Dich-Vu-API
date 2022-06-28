<?php

use OTPHP\HOTP;

/**
 * Password Reset Controller
 */
class PasswordResetController extends Controller
{
    /**
     * Process
     */
    public function process()
    {
        $request_method = Input::method();
        if($request_method==='POST'){
            if(Input::post("action")=="check"){
                $this->check();
            }
            else if(Input::post("action")=="reset"){
                $this->resetpass();
            }
        }
    }


    /**
     * Reset
     * @return void
     */

    private function resetpass()
    {
        $this->resp->result = 0;
        // INPUT
        $email = Input::post("email");
        $password = Input::post("password");
        $password_confirm = Input::post("password-confirm");
        $hash = Input::post("hash");
        $User = Controller::model("User", $email);
        if (!$User->isAvailable() || !$User->get("is_active") || !$User->get("secret") || !$User->get("data.recoveryhash") == $hash) 
        {
            $this->resp->msg =  __("User is invalid!");
            $this->jsonecho();
        }

     
        if ($password && $password_confirm) {
            if (mb_strlen($password) < 6) {
                $this->resp->msg =  __("Password must be at least 6 character length!");
            } else if ($password_confirm != $password) {
                $this->resp->msg =  __("Password confirmation didn't match!");
            } else {
                $data = json_decode($User->get("data"));
                unset($data->recoveryhash);
                $User->set("password", password_hash(Input::post("password"), PASSWORD_DEFAULT))
                    ->set("data", json_encode($data))
                     ->save();
                $this->resp->result = 1;
                $this->resp->msg = __("You've successfully reset your password!");
            }
        } else {
            $this->resp->msg =  __("All fields are required!");
        }

        $this->jsonecho();
    }

    private function check(){
        $this->resp->result = 0;
                // INPUT
        $email = Input::post("email");
        $code = Input::post("code");

        $User = Controller::model("User", $email);
        if (!$User->isAvailable() || !$User->get("is_active") || !$User->get("secret")) 
        {
            $this->resp->msg =  __("User is invalid!");
            $this->jsonecho();
        }

        $secret = $User->get("secret");
        $otp = OTPHP\TOTP::create($secret);

        $isVerified = $otp->verify($code);
        if (!$isVerified) {
            $isVerified = $otp->verify($code, time()-60);
        }

        if(!$isVerified){
            $this->resp->msg =  __("Code is invalid!");
            $this->jsonecho();
        }else{
            $hash = sha1(uniqid(readableRandomString(10), true));
            $User->set("data.recoveryhash", $hash)->save();
            $this->resp->result=1;
            $this->resp->msg =  __("Follow the next instruction to reset pass your password.");
            $this->resp->email = $email;
            $this->resp->hash = $hash;
            $this->jsonecho();
        }
    }
}