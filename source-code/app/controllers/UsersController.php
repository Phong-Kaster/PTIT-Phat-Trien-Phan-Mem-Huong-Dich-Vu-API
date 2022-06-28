<?php
/**
 * Users Controller
 */
class UsersController extends Controller
{
    /**
     * Process
     */
    public function process()
    {
        $AuthUser = $this->getVariable("AuthUser");

        // Auth
        if (!$AuthUser){
            header("Location: ".APPURL."/login");
            exit;
        }
        else if (!$AuthUser->isAdmin()) 
        {
            header("Location: ".APPURL."/dashboard");
            exit;
        }

        $request_method = Input::method();
        if($request_method === 'POST'){
            $this->save();
        }else if($request_method === 'GET'){
            $this->getAll();
        }
    }

    /**
     * @author Phong
     * lay tat ca thong tin cua cac user
     */
    private function getAll()
    {
        /**Step 1 */
        $AuthUser = $this->getVariable("AuthUser");
        $this->resp->result = 0;

        $search = Input::get("search");
        $start = Input::get("start");
        $length = Input::get("length");
        $order = Input::get("order");
        $search = Input::get("search");
        $draw = Input::get("draw");

        if($draw){
            $this->resp->draw = $draw; 
        }
           
        $data = [];

        try 
        {
            $query = DB::table(TABLE_PREFIX.TABLE_USERS)
                        ->select([
                            "id",
                            "account_type",
                            "email",
                            "firstname",
                            "lastname",
                            "is_active",
                            "avatar",
                            "date"
                        ]);

            $search_query = trim((string)$search);
            if($search_query){
                $query->where(function($q) use($search_query)
                    {
                        $q->where(TABLE_PREFIX.TABLE_USERS.".firstname", 'LIKE', $search_query.'%')
                        ->orWhere(TABLE_PREFIX.TABLE_USERS.".lastname", 'LIKE', $search_query.'%')
                        ->orWhere(TABLE_PREFIX.TABLE_USERS.".account_type", 'LIKE', $search_query.'%')
                        ->orWhere(TABLE_PREFIX.TABLE_USERS.".email", 'LIKE', $search_query.'%');
                    });     
            }


            if($order && isset($order["column"]) && isset($order["dir"]) ){
                $sort =  in_array($order["dir"],["asc","desc"]) ? $order["dir"] : "desc";
                $column_name = trim($order["column"]) != "" ? trim($order["column"]) : "id";
                $query->orderBy($column_name, $sort);
            }  
            
            $res = $query->get();
            $count = count($res);
            $this->resp->summary = array(
                "total_count" => $count
            );

            $query->limit($length ? $length : 10)->offset($start ? $start : 0);

            foreach($res as $r)
            {
                $data[] = array(
                    "id"=> (int)$r->id,
                    "email" => $r->email,
                    "account_type" => $r->account_type,
                    "firstname" => $r->firstname,
                    "lastname" => $r->lastname,
                    "avatar" => $r->avatar,
                    "is_active" => (bool)$r->is_active,
                    "date" => $r->date
                );
            }

            $this->resp->data = $data;
            $this->resp->result = 1;
        } 
        catch (\Exception $ex) 
        {
            $this->resp->msg = $ex->getMessage();
        }
        $this->jsonecho();
    }


    /**
     * @author Phong
     * them moi mot user
     */
    private function save()
    {
        /**Step 1 */
        $this->resp->result = 0;
        $AuthUser = $this->getVariable("AuthUser");

        /**Step 2 */
        $required_fields = ["email", "firstname", "lastname", "account_type" ];
        foreach( $required_fields as $field)
        {
            if( !Input::post($field) )
            {
                $this->resp->msg = __("Missing a required field: ".$field);
                $this->jsonecho();
            }
        }

        // Step 2.1
        if (!filter_var(Input::post("email"), FILTER_VALIDATE_EMAIL)) {
            $this->resp->msg = __("Email is not valid.");
            $this->jsonecho();
        }

        // Step 2.2
        $activeStatus = Input::put("is_active") == "true" ? 1 : 0;

        // Step 2.3
        $validAccount_type = ["admin", "member"];
        $account_type = Input::post("account_type");
        if( !in_array( $account_type, $validAccount_type) )
        {
            $account_type = "member";
        }

        // Step 2.4
        $User = Controller::model("User", Input::post("email"));
        if( $User->isAvailable() )
        {
            $this->resp->msg = __("There is an user who have this email !");
            $this->jsonecho();
        }


        /**Step 3 */
        
        $defaultPassword = "123456";
        $hashPassword = password_hash( $defaultPassword, PASSWORD_DEFAULT);

        /**Step 4 */
        $User = Controller::model("User");
        $User->set("account_type",  $account_type)
                ->set("email",        Input::post("email") )
                ->set("password",     $hashPassword)
                ->set("firstname",    Input::post("firstname") )
                ->set("lastname",     Input::post("lastname") )
                ->set("is_active",    $activeStatus)
                ->set("date",         date("Y-m-d H:i:s"))
                ->save();

        
        
        try 
        {
            //code...
            \Email::sendNotification("new-user", ["user" => $User, 'password' => $defaultPassword ]);
        } 
        catch (\Exception $ex) 
        {
            $this->resp->msg = $ex->getMessage();
            $this->jsonecho();
        }
        $this->resp->result = 1;
        $this->resp->msg = __("Created successfully !");
        $this->resp->user = (int)$User->get("id");
        $this->jsonecho();
    }
}