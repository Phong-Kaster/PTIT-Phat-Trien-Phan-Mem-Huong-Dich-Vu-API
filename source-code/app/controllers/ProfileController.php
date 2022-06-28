<?php
/**
 * Profile Controller
 */
class ProfileController extends Controller
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
        
        $request_method = Input::method();
        if($request_method === 'GET'){
            $this->getProfile();
        }else if ($request_method == 'POST') {
            if(Input::post("action") == "save"){
                $this->save();
            }else if(Input::post("action") == "avatar"){
                $this->updateAvatar();
            }else if(Input::post("action") == "language"){
                $this->updateLanguage();
            }
        }
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
        $required_fields = ["firstname", "lastname"];

        foreach ($required_fields as $field) {
            if (!Input::post($field)) {
                $this->resp->msg = __("Missing some of required data.");
                $this->jsonecho();
            }
        }

        // Start setting data
        $AuthUser->set("firstname", Input::post("firstname"))
                 ->set("lastname", Input::post("lastname"))
                 ->save();

        $this->resp->result = 1;
        $this->resp->msg = __("Changes saved!");
        $this->resp->data = array(
            "id" => (int)$AuthUser->get("id"),
            "account_type" => $AuthUser->get("account_type"),
            "email" => $AuthUser->get("email"),
            "firstname" => $AuthUser->get("firstname"),
            "lastname" => $AuthUser->get("lastname"),
            "avatar" => $AuthUser->get("avatar"),
            "language" => $AuthUser->get("language"),
            "is_active" => (bool)$AuthUser->get("is_active"),
            "date" => $AuthUser->get("date"),
        );
        $this->jsonecho();
    }


    private function getProfile(){
        $AuthUser = $this->getVariable("AuthUser");
        $this->resp->result = 1;
        $this->resp->data = array(
            "id" => (int)$AuthUser->get("id"),
            "account_type" => $AuthUser->get("account_type"),
            "email" => $AuthUser->get("email"),
            "firstname" => $AuthUser->get("firstname"),
            "lastname" => $AuthUser->get("lastname"),
            "avatar" => $AuthUser->get("avatar"),
            "language" => $AuthUser->get("language"),
            "is_active" => (bool)$AuthUser->get("is_active"),
            "date" => $AuthUser->get("date")
        );
        $this->resp->msg = __("Get data successful!");
        $this->jsonecho();
    }


    private function updateAvatar(){
        $this->resp->result = 0;
        $AuthUser = $this->getVariable("AuthUser");
        // Check file
        if (empty($_FILES["file"]) || $_FILES["file"]["size"] <= 0) {
            $this->resp->msg = __("File not received!");
            $this->jsonecho();
        }

        // Check file extension
        $ext = strtolower(pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION));
        $allow = ["jpeg", "jpg", "png"];
        if (!in_array($ext, $allow)) {
            $this->resp->msg = __("Only ".join(",", $allow)." files are allowed");
            $this->jsonecho();
        }

        // Upload file
        $tempname = "avatar_user_".$AuthUser->get("id");
        $temp_dir = UPLOAD_PATH;
        if (!file_exists($temp_dir)) {
            mkdir($temp_dir);
        } 
        $filepath = $temp_dir . "/" . $tempname . "." .$ext;
        if (!move_uploaded_file($_FILES["file"]["tmp_name"], $filepath)) {
            $this->resp->msg = __("Oops! An error occured. Please try again later!");
            $this->jsonecho();
        }

        $AuthUser->set("avatar", $tempname . "." .$ext)->save();
        $this->resp->result = 1;
        $this->resp->msg = __("Upload successful");
        $this->resp->image = $tempname . "." .$ext;
        $this->jsonecho();
        
    }

    private function updateLanguage(){
        $this->resp->result = 0;
        $AuthUser = $this->getVariable("AuthUser");
        $langcode = Input::post("langcode");

        try {
            $AuthUser->set("language", $langcode) ->save();
        } catch (\Exception $ex) {
            $this->resp->msg = $ex->getMessage();
            $this->jsonecho();
        }

        $this->resp->result = 1;
        $this->resp->msg = __("Save Changes!");
        $this->jsonecho();
        
    }
}