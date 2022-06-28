<?php 
/**
 * Main app
 */
class App
{
    protected $router;
    protected $controller;

    // An array of the URL routes
    protected static $routes = [];


    /**
     * summary
     */
    public function __construct()
    {
        $this->controller = new Controller;
    }


    /**
     * Adds a new route to the App:$routes static variable
     * App::$routes will be mapped on a route 
     * initializes on App initializes
     * 
     * Format: ["METHOD", "/uri/", "Controller"]
     * Example: App:addRoute("GET|POST", "/post/?", "Post");
     */
    public static function addRoute()
    {
        $route = func_get_args();
        if ($route) {
            self::$routes[] = $route;
        }
    }


    /**
     * Get App::$routes
     * @return array An array of the added routes
     */
    public static function getRoutes()
    {
        return self::$routes;
    }


    /**
     * Get IP info
     * @return stdClass 
     */
    private function ipinfo()
    {
        $client = empty($_SERVER['HTTP_CLIENT_IP']) 
                ? null : $_SERVER['HTTP_CLIENT_IP'];
        $forward = empty($_SERVER['HTTP_X_FORWARDED_FOR']) 
                 ? null : $_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote = empty($_SERVER['REMOTE_ADDR']) 
                ? null : $_SERVER['REMOTE_ADDR'];

        if (filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        } else if (filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        } else {
            $ip = $remote;
        }


        if (!isset($_SESSION[$ip])) {
            $res = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=".$ip), true);

            $ipinfo = [
                "request" => "", // Requested Ip Address
                "status" => "", // Status code (200 for success)
                "credit" => "",
                "city" => "",
                "region" => "",
                "areaCode" => "",
                "dmaCode" => "",
                "countryCode" => "",
                "countryName" => "",
                "continentCode" => "",
                "latitude" => "",
                "longitude" => "",
                "regionCode" => "",
                "regionName" => "",
                "currencyCode" => "",
                "currencySymbol" => "",
                "currencySymbol_UTF8" => "",
                "currencyConverter" => "",
                "timezone" => "", // Will be used only in registration
                                  // process to detect user's 
                                  // timezone automatically
                "neighbours" => [], // Neighbour country codes (ISO 3166-1 alpha-2)
                "languages" => [] // Spoken languages in the country
                                  // Will be user to auto-detect user language
            ];
            if (is_array($res)) {
                foreach ($res as $key => $value) {
                    $key = explode("_", $key, 2);
                    if (isset($key[1])) {
                        $ipinfo[$key[1]] = $value;
                    }
                }
            }

            if ($ipinfo["latitude"] && $ipinfo["longitude"]) {
                $Settings = Controller::model("GeneralData", "settings");
                $username = $Settings->get("data.geonamesorg_username");

                if ($username) {
                    // Get timezone
                    if (!empty($ipinfo["latitude"]) && !empty($ipinfo["longitude"])) {
                        $res = @json_decode(file_get_contents("http://api.geonames.org/timezoneJSON?lat=".$ipinfo["latitude"]."&lng=".$ipinfo["longitude"]."&username=".$username));

                        if (isset($res->timezoneId)) {
                            $ipinfo["timezone"] = $res->timezoneId;
                        }
                    }


                    // Get neighbours
                    if (!empty($ipinfo["countryCode"])) {
                        $res = @json_decode(file_get_contents("http://api.geonames.org/neighboursJSON?country=".$ipinfo["countryCode"]."&username=".$username));

                        if (!empty($res->geonames)) {
                            foreach ($res->geonames as $r) {
                                $ipinfo["neighbours"][] = $r->countryCode;
                            }
                        }
                    }

                    // Get country
                    if (!empty($ipinfo["countryCode"])) {
                        $res = @json_decode(file_get_contents("http://api.geonames.org/countryInfoJSON?country=".$ipinfo["countryCode"]."&username=".$username));

                        if (!empty($res->geonames[0]->languages)) {
                            $langs = explode(",", $res->geonames[0]->languages);
                            foreach ($langs as $l) {
                                $ipinfo["languages"][] = $l;
                            }
                        }
                    }
                }
            }

            $_SESSION[$ip] = $ipinfo;
        }

        return json_decode(json_encode($_SESSION[$ip]));
    }


    /**
     * Create database connection
     * @return App 
     */
    private function db()
    {
        $config = [
            'driver' => 'mysql', 
            'host' => DB_HOST,
            'database' => DB_NAME,
            'username' => DB_USER,
            'password' => DB_PASS,
            'charset' => DB_ENCODING,
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]
        ];

        new \Pixie\Connection('mysql', $config, 'DB');
        return $this;
    }


    /**
     * Check and get authorized user data
     * Define $AuthUser variable
     */
    private function auth()
    {
        $AuthUser = null;
        $headers = apache_request_headers();
        $Authorization = null;
        if(isset($headers['authorization'])){
            $Authorization = $headers['authorization'];
        }

        if(isset($headers['Authorization'])){
            $Authorization = $headers['Authorization'];
        }
        if(isset($Authorization)){
            $matches = array();
            preg_match('/JWT (.*)/', $Authorization, $matches);
            if(isset($matches[1])){
                $accessToken = $matches[1];
                try {
                    $decoded = Firebase\JWT\JWT::decode($accessToken, MP_SALT, array('HS256'));
                    $User = Controller::Model("User", $decoded->id);
    
                    if (isset($decoded->hashPass) && $User->isAvailable() && $User->get("is_active") == 1 && md5($User->get("password")) == $decoded->hashPass){
                        $AuthUser = $User;
                    }
                } catch (\Exception $th) {
                    return $AuthUser;
                }
            }
        }
        if (Input::cookie("accessToken")) {
            try {
                $decoded = Firebase\JWT\JWT::decode(Input::cookie("accessToken"), MP_SALT, array('HS256'));
                $User = Controller::Model("User", $decoded->id);

                if (isset($decoded->hashPass) && $User->isAvailable() && $User->get("is_active") == 1 && md5($User->get("password")) == $decoded->hashPass){
                    $AuthUser = $User;
                }
            } catch (\Exception $th) {
                return $AuthUser;
            }
            
        }
        return $AuthUser;
    }


    /**
     * Define ACTIVE_LANG constant
     * Include languge strings
     */
    private function i18n()
    {   
        $Route = $this->controller->getVariable("Route");
        $AuthUser = $this->controller->getVariable("AuthUser");
        $IpInfo = $this->controller->getVariable("IpInfo");

        if ($AuthUser) {
            // Get saved lang code for authorized user.
            $lang = $AuthUser->get("preferences.language");
        } else if (isset($Route->params->lang)) {
            // Direct link or language change
            // Getting lang from route
            $lang = $Route->params->lang;
        } else if (Input::cookie("lang")) {
            // Returninn user (non-auth),
            // Getting lang. from the cookie
            $lang = Input::cookie("lang");
        } else {
            // New user
            // Getting lang. from ip-info
            $lang = Config::get("default_applang");

            if ($IpInfo->languages) {
                foreach ($IpInfo->languages as $l) {
                    foreach (Config::get("applangs") as $al) {
                        if ($al["code"] == $l || $al["shortcode"] == $l) {
                            // found, break loops
                            $lang = $al["code"];
                            break 2;
                        }
                    }
                }
            }
        }


        // Validate found language code
        $active_lang = Config::get("default_applang");
        foreach (Config::get("applangs") as $al) {
            if ($al["code"] == $lang || $al["shortcode"] == $lang) {
                // found, break loop
                $active_lang = $al["code"];
                break;
            }
        }

        define("ACTIVE_LANG", $active_lang);
        @setcookie("lang", ACTIVE_LANG, time()+30 * 86400, "/");


        $Translator = new Gettext\Translator;

        // Load app. locale
        $path = APPPATH . "/locale/" . ACTIVE_LANG . "/messages.po";
        if (file_exists($path)) {
            $translations = Gettext\Translations::fromPoFile($path);
            $Translator->loadTranslations($translations);
        }

        $Translator->register(); // Register global functions

        // Set other library locales
        try {
            \Moment\Moment::setLocale(str_replace("-", "_", ACTIVE_LANG));
        } catch (Exception $e) {
            // Couldn't load locale
            // There is nothing to do here,
            // Fallback to default language
        }
    }


    /**
     * Analize route and load proper controller
     * @return App
     */
    private function route()
    {
        // Initialize the router
        $router = new AltoRouter();
        $router->setBasePath(BASEPATH);

        // Load plugin/theme routes first
        // TODO: Update router.map in modules to App::addRoute();
        $GLOBALS["_ROUTER_"] = $router;
        \Event::trigger("router.map", "_ROUTER_");
        $router = $GLOBALS["_ROUTER_"];

        // Load global routes
        include APPPATH."/inc/routes.inc.php";
        
        // Map the routes
        $router->addRoutes(App::getRoutes());

        // Match the route
        $route = $router->match();
        $route = json_decode(json_encode($route));

        if ($route) {
            if (is_array($route->target)) {
                require_once $route->target[0];
                $controller = $route->target[1];
            } else {
                $controller = $route->target."Controller";
            }
        } else {
            header("HTTP/1.0 404 Not Found");
            $controller = "IndexController";
        }

        $this->controller = new $controller;
        $this->controller->setVariable("Route", $route);
    }



    /**
     * Process
     */
    public function process()
    {
        /**
         * Create database connection
         */
        $this->db();

        /**
         * Get IP Info
         */
        $IpInfo = $this->ipinfo();

        /**
         * Auth.
         */
        $AuthUser = $this->auth();


        /**
         * Analize the route
         */
        $this->route();
        $this->controller->setVariable("IpInfo", $IpInfo);
        $this->controller->setVariable("AuthUser", $AuthUser);


        /**
         * Init. locales
         */
        $this->i18n();


        $this->controller->process();
    }
}