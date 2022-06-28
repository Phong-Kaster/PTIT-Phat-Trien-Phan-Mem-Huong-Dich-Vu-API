<?php
/**
 * Recovery Controller
 */
class RecoveryController extends Controller
{
    /**
     * Process
     */
    public function process()
    {
        $this->recovery();
    }


    /**
     * Recovery
     * @return void
     */
    private function recovery()
    {
        $this->resp->result = 0;
            
        $email = Input::post("email");
        
        if ($email) {
            $User = Controller::model("User", $email);

            if ($User->isAvailable() && $User->get("is_active") == 1) {
                try {
                    // Send instruction to email
                    // Send notification emails to admins
                    if(\Email::sendNotification("password-recovery", ["user" => $User])) {
                        $this->resp->result = 1;
                        $this->resp->email = $email;
                        $this->resp->msg = __('Password reset instruction sent to your email address.');
                        
                    } else {
                        $this->resp->msg = __("Couldn't send recovery email. Please try again later.");
                    }
                } catch (\Exception $e) {
                    // Failed to send notification email to admins
                    // Do nothing here, it's not critical error
                    $this->resp->msg =  __("Couldn't send recovery email. Please try again later.");
                }
            } else {
                $this->resp->msg =  __("We couldn't find your account");
            }
        }else{
            $this->resp->msg = __("Missing some of required data.");
        }
        $this->jsonecho();
    }
}