<?php 
/**
 * Email class to send advanced HTML emails
 * 
 * @author Onelab <hello@onelab.co>
 */
class Email extends PHPMailer\PHPMailer\PHPMailer{
    /**
     * Email template html
     * @var string
     */
    public static $template;


    /**
     * Email and notification settings from database
     * @var DataEntry
     */
    public static $emailSettings;


    /**
     * Site settings
     * @var DataEntry
     */
    public static $siteSettings;


    public function __construct(){  
        parent::__construct();

        // Get settings
        $emailSettings = self::getEmailSettings();
        
        // Get site name
        $siteSettings = self::getSiteSettings();

        $this->CharSet = "UTF-8";
        $this->isHTML();

        if ($emailSettings->get("data.host")) {
            $this->isSMTP();

            if ($emailSettings->get("data.from")) {
                $this->From = $emailSettings->get("data.from");
                $this->FromName = htmlchars($siteSettings->get("data.site_name"));
            }
            
            $this->Host = $emailSettings->get("data.host");
            $this->Port = $emailSettings->get("data.port");
            $this->SMTPSecure = $emailSettings->get("data.encryption");

            if ($emailSettings->get("data.auth")) {
                $this->SMTPAuth = true;
                $this->Username = $emailSettings->get("data.username");

                try {
                    $password = \Defuse\Crypto\Crypto::decrypt($emailSettings->get("data.password"), 
                                \Defuse\Crypto\Key::loadFromAsciiSafeString(CRYPTO_KEY));
                } catch (Exception $e) {
                    $password = $emailSettings->get("data.password");
                }
                $this->Password = $password;
            }


            // If your mail server is on GoDaddy
            // Probably you should uncomment following 7 lines

            // $this->SMTPOptions = array(
            //     'ssl' => array(
            //         'verify_peer' => false,
            //         'verify_peer_name' => false,
            //         'allow_self_signed' => true
            //     )
            // );
        }
    }


    /**
     * Send email with $content
     * @param  string $content Email content
     * @return boolen          Sending result
     */
    public function sendmail($content){
            //code...
            $html = self::getTemplate();
            $html = str_replace("{{email_content}}", $content, $html);

            $this->Body = $html;

            return $this->send();
    }


    /**
     * Get email settings
     * @return string|null 
     */
    private static function getEmailSettings()
    {
        if (is_null(self::$emailSettings)) {
            self::$emailSettings = \Controller::model("GeneralData", "smtp");
        }

        return self::$emailSettings;
    }

    /**
     * Get site settings
     * @return string|null
     */
    private static function getSiteSettings()
    {
        if (is_null(self::$siteSettings)) {
            self::$siteSettings = \Controller::model("GeneralData", "settings");
        }

        return self::$siteSettings;
    }


    /**
     * Get template HTML
     * @return string 
     */
    private static function getTemplate()
    {   
        if (!self::$template) {
            $html = file_get_contents(APPPATH."/inc/email-template.inc.php");
            $Settings = self::getSiteSettings();
            
            $html = str_replace(
                [
                    "{{site_name}}",
                    "{{foot_note}}",
                    "{{appurl}}",
                    "{{copyright}}"
                ], 
                [
                    htmlchars($Settings->get("data.site_name")),
                    __("Thanks for using %s.", htmlchars($Settings->get("data.site_name"))),
                    APPURL,
                    __("All rights reserved.")
                ], 
                $html
            );
            
            self::$template = $html;
        }

        return self::$template;
    }




    /**
     * Send notifications
     * @param  string $type notification type
     * @return [type]       
     */
    public static function sendNotification($type = "new-user", $data = [])
    {
        switch ($type) {
            case "new-user":
                return self::sendNewUserNotification($data);
                break;

            case "password-recovery":
                return self::sendPasswordRecoveryEmail($data);
                break;
            
            default:
                break;
        }
    }


    /**
     * Send notification email to admins about new users
     * @return bool
     */
    private static function sendNewUserNotification($data = [])
    {

            $siteSettings = self::getSiteSettings();
            
            $user = $data["user"];
            $password = $data["password"];

            $mail = new Email;
            $mail->Subject = ("New Registration");
            $mail->addAddress( $user->get("email") );

            $app_url = str_replace("/api", "", APPURL);
            $emailbody = "<p>Hello, </p>"
                    . "<p>Someone signed up in <a href='".$app_url."'>".htmlchars($siteSettings->get("data.site_name"))."</a> with following data:</p>"
                    . "<div style='margin-top: 30px; font-size: 14px; color: #9b9b9b'>"
                    . "<div><strong>Firstname:</strong> ".htmlchars($user->get("firstname"))."</div>"
                    . "<div><strong>Lastname:</strong> ".htmlchars($user->get("lastname"))."</div>"
                    . "<div><strong>Email:</strong> ".htmlchars($user->get("email"))."</div>"
                    . "<div><strong>Password:</strong> ".htmlchars($password)."</div>"
                    . "</div>";
            return $mail->sendmail($emailbody);
    }

    /**
     * Send recovery instructions to the user
     * @return bool
     */
    private static function sendPasswordRecoveryEmail($data = [])
    {
        $siteSettings = self::getSiteSettings();
        $mail = new Email;
        $mail->Subject = __("Password Recovery");
        $user = $data["user"];

        $hash = sha1(uniqid(readableRandomString(6), true));

        
        $secret = "";
        if($user->get("secret")){
            $secret = $user->get("secret");
            $otp = OTPHP\TOTP::create($secret);
        }else{
            $otp = OTPHP\TOTP::create(null);
            $secret = $otp->getSecret();
            $user->set("secret", $secret)
                ->save();
        }

        $mail->addAddress($user->get("email"));

        $app_url = str_replace("/api", "", APPURL);
        $emailbody = "<p>".__("Hi %s", htmlchars($user->get("firstname"))).", </p>"
                   . "<p>".__("Someone requested password reset instructions for your account on %s. If this was not you, feel free to ignore this mail maybe someone input your email by mistake. Your account is still safe.", "<a href='".$app_url."'>".htmlchars($siteSettings->get("data.site_name"))."</a>")."</p>"
                   . "<div style='margin-top: 30px; font-size: 14px; color: #9b9b9b'>"
                   . "<p>Use this verification code to recovery your account: </p>"
                   . "<b style='font-size: 16px; line-height: 24px; padding: 6px 12px;letter-spacing: 8px;'  >".$otp->now()."</b>"
                   . "</div>";

        return $mail->sendmail($emailbody);
    }
}