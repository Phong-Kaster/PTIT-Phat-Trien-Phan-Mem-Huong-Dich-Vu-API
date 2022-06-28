<?php
/**
 * Settings Controller
 */
class SettingsController extends Controller
{
    /**
     * Process
     * Step 1: declare local variable
     * Step 2: declare local variable from database
     * Step 3: handle request depends on called method
     */
    public function process()
    {
        /**Step 1 */
        $AuthUser = $this->getVariable("AuthUser");
        $Route = $this->getVariable("Route");

        
        require_once(APPPATH.'/inc/currencies.inc.php');
        $page = isset($Route->params->page) ? $Route->params->page : "site";

        /**Step 2 */
        $this->setVariable("Settings", Controller::model("GeneralData", "settings"))
             ->setVariable("Integrations", Controller::model("GeneralData", "integrations"))
             ->setVariable("SMTP", Controller::model("GeneralData", "smtp"))
             ->setVariable("page", $page)
             ->setVariable("Currencies", $Currencies);

        /**Step 3 */
        $request_method = Input::method();
        if($request_method === 'GET')
        {
            $this->get();
        }
        else if ($request_method == 'POST') 
        {
            if (!$AuthUser || !$AuthUser->isAdmin())
            {
                header("Location: ".APPURL."/login");
                exit;
            } 
            $this->save();
        }
    }

    /**
     * Save changes
     * @return boolean 
     */
    private function save()
    {
        $page = $this->getVariable("page");
        $method = "save";
        $parts = explode("-", $page);
        foreach ($parts as $p) {
            $method .= ucfirst(strtolower($p));
        }

        return $this->$method();
    }

    /**
     * Get info
     * @return boolean 
     */
    private function get()
    {
        $page = $this->getVariable("page");

        $method = "get";
        $parts = explode("-", $page);
        foreach ($parts as $p) {
            $method .= ucfirst(strtolower($p));
        }

        return $this->$method();
    }

    private function getSite(){
        $page = $this->getVariable("page");
        $Settings = $this->getVariable("Settings");
        $this->resp->data = json_decode($Settings->get("data"));
        $this->resp->method = 1;
        $this->resp->result = 1;
        $this->jsonecho();
    }

    private function getIntegrations(){
        $page = $this->getVariable("page");
        $Integrations = $this->getVariable("Integrations");
        $this->resp->data = json_decode($Integrations->get("data"));
        $this->resp->result = 1;
        $this->jsonecho();
    }

    private function getSmtp(){
        $AuthUser = $this->getVariable("AuthUser");
        if (!$AuthUser || !$AuthUser->isAdmin()){
            header("Location: ".APPURL."/login");
            exit;
        } 
        $page = $this->getVariable("page");
        $SMTP = $this->getVariable("SMTP");
        $data = json_decode($SMTP->get("data"));

        try {
            $password = \Defuse\Crypto\Crypto::decrypt($data->password, 
                        \Defuse\Crypto\Key::loadFromAsciiSafeString(CRYPTO_KEY));
        } catch (Exception $e) {
            $password = "";
        }

        $data->password = $password;
        $this->resp->data = $data;
        $this->resp->result = 1;
        $this->resp->method = 1;
        $this->jsonecho();
    }

    /**
     * Save site settings
     * @return boolean 
     */
    private function saveSite()
    {  
        $Settings = $this->getVariable("Settings");
        $do_save= false;
        

        if (!is_null(Input::post("site_name"))) {
            $Settings->set("data.site_name", Input::post("site_name"));
            $do_save = true;
        }

        if (!is_null(Input::post("site_description"))) {
            $Settings->set("data.site_description", Input::post("site_description"));
            $do_save = true;
        }
        
        if (!is_null(Input::post("site_keywords"))) {
            $Settings->set("data.site_keywords", Input::post("site_keywords"));
            $do_save = true;
        }

        if (!is_null(Input::post("site_slogan"))) {
            $Settings->set("data.site_slogan", Input::post("site_slogan"));
            $do_save = true;
        }

        if (!is_null(Input::post("currency"))) {
            $Settings->set("data.currency", Input::post("currency"));
            $do_save = true;
        }

        if (!is_null(Input::post("language"))) {
            $language = Config::get("default_applang");
            foreach (Config::get("applangs") as $al) {
                if ($al["code"] == Input::post("language")) {
                    $language = Input::post("language");
                    break;
                }
            }
            $Settings->set("data.language", $language);
            $do_save = true;
        }

        if (!is_null(Input::post("logotype"))) {
            $Settings->set("data.logotype", Input::post("logotype"));
            $do_save = true;
        }

        if (!is_null(Input::post("logomark"))) {
            $Settings->set("data.logomark", Input::post("logomark"));
            $do_save = true;
        }

        if ($do_save) {
            $Settings->save();
        }

        $this->resp->result = 1;
        $this->resp->msg = __("Changes saved!");
        $this->resp->data = json_decode($Settings->get("data"));
        $this->jsonecho();

        return $this;
    }

    /**
     * Save Google Analytics settings
     * @return boolean 
     */
    private function saveGoogleAnalytics()
    {  
        $Integrations = $this->getVariable("Integrations");
        $do_save= false;
        

        if (!is_null(Input::post("property-id"))) {
            $Integrations->set("data.google.analytics.property_id", Input::post("property-id"));
            $do_save = true;
        }


        if ($do_save) {
            $Integrations->save();
        }

        $this->resp->result = 1;
        $this->resp->msg = __("Changes saved!");
        $this->jsonecho();

        return $this;
    }

    /**
     * Save SMTP settings
     * @return boolean 
     */
    private function saveSmtp()
    {
        $SMTP = $this->getVariable("SMTP");

        if (Input::post("host")) {
            $host = Input::post("host");
            $port = Input::post("port");
            $encryption = strtolower(Input::post("encryption"));
            if (!in_array($encryption, ["ssl", "tls"])) {
                $encryption = "";
            }
            $auth = (bool)Input::post("auth");
            $username = $auth ? Input::post("username") : "";
            $password = $auth ? Input::post("password") : "";
            $from = Input::post("from");

            if (!in_array($port, [25, 465, 587])) {
                $this->resp->msg = __("Invalid port number");
                $this->jsonecho(); 
            }

            if ($from && !filter_var($from, FILTER_VALIDATE_EMAIL)) {
                $this->resp->msg = __("From email is not valid");
                $this->jsonecho();
            }

            // Check SMTP Connection
            $smtp = new PHPMailer\PHPMailer\SMTP;
            $connected = false;
            // $smtp->do_debug = SMTP::DEBUG_CONNECTION;

            try {
                //Connect to an SMTP server
                $options = [];

                // If your mail server is on GoDaddy
                // Probably you should uncomment following 5 lines
                // 
                // $options["ssl"] = [
                //     'verify_peer' => false,
                //     'verify_peer_name' => false,
                //     'allow_self_signed' => true
                // ];

                if (!$smtp->connect($host, $port, 30, $options)) {
                    $this->resp->msg = __("Connection failed");
                    $this->jsonecho();
                }

                //Say hello
                if (!$smtp->hello(gethostname())) {
                    $this->resp->msg = __("Connection failed");
                    $this->jsonecho();
                }

                //Get the list of ESMTP services the server offers
                $e = $smtp->getServerExtList();
                
                //If server can do TLS encryption, use it
                if (is_array($e) && array_key_exists('STARTTLS', $e)) {
                    $tlsok = $smtp->startTLS();

                    if (!$tlsok) {
                        $this->resp->msg = __("Failed to start encryption");
                        $this->jsonecho();
                    }

                    //Repeat EHLO after STARTTLS
                    if (!$smtp->hello(gethostname())) {
                        $this->resp->msg = __("Encryption failed");
                        $this->jsonecho();
                    }

                    //Get new capabilities list, which will usually now include AUTH if it didn't before
                    $e = $smtp->getServerExtList();
                }

                //If server supports authentication, do it (even if no encryption)
                if ($auth && is_array($e) && array_key_exists('AUTH', $e)) {
                    
                    if ($smtp->authenticate($username, $password)) {
                        $connected = true;
                    } else {
                        $this->resp->msg = __("Authentication failed");
                        $this->jsonecho();
                    }
                }
            } catch (Exception $e) {
                $this->resp->msg = __("Connection failed");
                $this->jsonecho();
            }

            $smtp->quit(true);
            
            if (!$connected) {
                $this->resp->msg = __("Authentication failed");
                $this->jsonecho();
            }


            // Encrypt the password
            try {
                $passhash = Defuse\Crypto\Crypto::encrypt($password, 
                            Defuse\Crypto\Key::loadFromAsciiSafeString(CRYPTO_KEY));
            } catch (\Exception $e) {
                $this->resp->msg = $e->getMessage();
                $this->jsonecho();
            }


            $data = [
                "host" => $host,
                "port" => $port,
                "encryption" => $encryption,
                "auth" => $auth,
                "username" => $username,
                "password" => $passhash,
                "from" => $from
            ];
        } else {
            $data = [
                "host" => "",
                "port" => "",
                "encryption" => "",
                "auth" => false,
                "username" => "",
                "password" => "",
                "from" => ""
            ];
        }

        $SMTP->set("data", json_encode($data));
        $SMTP->save();
        
        $this->resp->result = 1;
        $this->resp->msg = __("Changes saved!");
        $this->jsonecho();

        return $this;
    }
}