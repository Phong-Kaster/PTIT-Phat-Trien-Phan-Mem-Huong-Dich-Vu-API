<?php
/**
 * User Controller
 */
class UserController extends Controller
{
    /**
     * Process
     */
    public function process()
    {
        $Route = $this->getVariable("Route");
        $AuthUser = $this->getVariable("AuthUser");

        // Auth
        if (!$AuthUser){
            header("Location: ".APPURL."/login");
            exit;
        }else if (!$AuthUser->isAdmin()) {
            header("Location: ".APPURL."/dashboard");
            exit;
        }

        $request_method = Input::method();
        if($request_method === 'PUT'){
            $this->save();
        }else if($request_method === 'GET'){
            $this->getById();
        }else if($request_method === 'DELETE'){
            $this->remove();
        }else if($request_method === 'PATCH'){
            $this->restore();
        }

    }

    /**
     * @author Phong
     * lay user theo id
     */
    private function getById()
    {
        /**Step 1 */
        $AuthUser = $this->getVariable("AuthUser");
        $Route = $this->getVariable("Route");
        $this->resp->result = 0;

        if( !isset($Route->params->id) )
        {
            $this->resp->msg = __("ID is required !");
            $this->jsonecho();
        }

        /**Step 2 */
        $User = Controller::model("User", $Route->params->id );
        if( !$User->isAvailable() )
        {
            $this->resp->msg = __("This user does not exist !");
            $this->jsonecho();
        }

        /**Step 3 */
        $this->resp->result = 1;
        $this->resp->data = array(
            "id"          => (int)$User->get("id"),
            "account_type" => $User->get("account_type"),
            "email"       => $User->get("email"),
            "firstname"   => $User->get("firstname"),
            "lastname"    => $User->get("lastname"),
            "is_active"    => (bool)$User->get("is_active"),
            "date"    => $User->get("date")
        );
        $this->jsonecho();
    }

    /**
     * @author Phong
     * xoa user theo id
     */
    private function remove()
    {
        try 
        {
            //code...
            $AuthUser = $this->getVariable("AuthUser");
            $Route = $this->getVariable("Route");
            $this->resp->result = 0;

            if( !isset($Route->params->id) )
            {
                $this->resp->msg = __("ID is required !");
                $this->jsonecho();
            }

            $User = Controller::model("User", $Route->params->id);
            if( !$User->isAvailable() )
            {
                $this->resp->msg = __("This user does not exist !");
                $this->jsonecho();
            }

            if( $User->get("is_active") == 0 )
            {
                $this->resp->msg = __("This user was deactivate !");
                $this->jsonecho();
            }

            if (!$AuthUser->canEdit($User)) {
                $this->resp->msg = __("You don't have a permission to modify this user's data!");
                $this->jsonecho();   
            }

            if ($AuthUser->get("id") == $User->get("id")) {
                $this->resp->msg = __("You can not deactive your own account!");
                $this->jsonecho();
            }

            $User->set("is_active", 0)
                 ->save();
            // $User->delete();

            $this->resp->result = 1;
            $this->resp->user = (int)$User->get("id");
            $this->resp->msg = __("This user is deactivated !");
        } 
        catch (\Exception $ex) 
        {
            $this->resp->msg = $ex->getMessage();
        }
        $this->jsonecho();
    }


    /**
     * @author Phong
     * sua thong tin user
     */
    private function save()
    {
        /**Step 1 */
        $AuthUser = $this->getVariable("AuthUser");
        $Route = $this->getVariable("Route");
        $this->resp->result = 0;

        if( !isset($Route->params->id) )
        {
            $this->resp->msg = __("ID is required !");
            $this->jsonecho();
        }


        /**Step 2 */
        $required_fields = ["firstname", "lastname", "account_type"];
        foreach( $required_fields as $field )
        {
            if( !Input::put($field) )
            {
                $this->resp->msg = __("Missing some required field ".$field);
                $this->jsonecho();
            }
        }

        // Step 2.2
        $activeStatus = Input::put("is_active") == "true" ? 1 : 0;


        // Step 2.3
        $validAccount_type = ["admin", "member"];
        $account_type = Input::put("account_type");
        if( !in_array( $account_type, $validAccount_type) )
        {
            $account_type = "member";
        }


        /**Step 3 */
        $User = Controller::model("User", $Route->params->id);
        if( !$User->isAvailable() )
        {
            $this->resp->msg = __("There isn't any user who have this email !");
            $this->jsonecho();
        }

        if (!$AuthUser->canEdit($User)) {
            $this->resp->msg = __("You don't have a permission to modify this user's data!");
            $this->jsonecho();   
        }


        try 
        {        
            /**Step 4 */
            $User->set("account_type", $account_type)
                ->set("firstname", Input::put("firstname") )
                ->set("lastname", Input::put("lastname") )
                ->set("is_active", $activeStatus)
                ->save();
                
            $this->resp->result = 1;
            $this->resp->user = (int)$User->get("id");
            $this->resp->msg = __("User's information changed successfully !");
        } 
        catch (\Exception $ex) 
        {
            $this->resp->msg = $ex->getMessage();
        }
        $this->jsonecho();
    }

    /**
     * @author Hau
     * restore theo id
     */
    private function restore()
    {
        try 
        {
            $AuthUser = $this->getVariable("AuthUser");
            $Route = $this->getVariable("Route");
            $this->resp->result = 0;

            if( !isset($Route->params->id) )
            {
                $this->resp->msg = __("ID is required!");
                $this->jsonecho();
            }

            $User = Controller::model("User", $Route->params->id);
            if( !$User->isAvailable() )
            {
                $this->resp->msg = __("This user does not exist!");
                $this->jsonecho();
            }

            if( $User->get("is_active") == 1 )
            {
                $this->resp->msg = __("This user was activate!");
                $this->jsonecho();
            }

            if (!$AuthUser->canEdit($User)) {
                $this->resp->msg = __("You don't have a permission to modify this user's data!");
                $this->jsonecho();   
            }

            $User->set("is_active", 1)
                 ->save();

            $this->resp->result = 1;
            $this->resp->user = (int)$User->get("id");
            $this->resp->msg = __("This user is Activated!");
        } 
        catch (\Exception $ex) 
        {
            $this->resp->msg = $ex->getMessage();
        }
        $this->jsonecho();
    }
}