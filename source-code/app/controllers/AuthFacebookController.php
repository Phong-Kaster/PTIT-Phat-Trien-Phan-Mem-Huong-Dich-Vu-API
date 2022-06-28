<?php
/**
 * AuthFacebook Controller
 */
class AuthFacebookController extends Controller
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
        $accessToken = Input::post("access_token");

        if( !$accessToken )
        {
            $this->resp->msg = __("Missing some required field !");
            $this->jsonecho();
        }
        // Validate user token
        $url = "https://graph.facebook.com/me?fields=id,name,first_name,last_name,email,picture.type(large)&access_token=". $accessToken;
        $tokenresp = @json_decode(file_get_contents($url));
        
        if (empty($tokenresp->id))
        {
            $this->resp->msg = __("Invalid token");
            $this->jsonecho();
        }

        $email = $tokenresp->email;
        $firstname = $tokenresp->first_name;
        $lastname = $tokenresp->last_name;
        $picture = $tokenresp->picture->data->url;
        try 
        {        
          $User = Controller::model("User", $email);

          if (!$User->isAvailable()) 
          {
            $tempname = uniqid();
            $ext = "jpeg";
    
            $filepath = UPLOAD_PATH . "/" . $tempname . "." .$ext;
            download_image($picture, $filepath);
            
            $User->set("email", $email)
                ->set("password", password_hash(uniqid(), PASSWORD_DEFAULT))
                ->set("firstname", $firstname)
                ->set("lastname", $lastname)
                ->set("avatar", $tempname . "." .$ext)
                ->set("is_active", 1)
                ->save();
          }
          
          if( !$User->get("is_active") ){
            $this->resp->msg = __("Account is banned!");
            $this->jsonecho();
          }
            
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
            $this->jsonecho();
        }

        $this->resp->result = 1;
        $this->resp->msg = __("Login is success!");
        $this->jsonecho();
    }
}