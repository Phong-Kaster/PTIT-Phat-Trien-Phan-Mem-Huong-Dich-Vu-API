<?php
/**
 * Notifications Controller
 */
class NotificationsController extends Controller
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
        $this->setVariable("Settings", Controller::model("GeneralData", "settings"));

        $request_method = Input::method();
        if($request_method === 'POST'){
            $this->save();
        }else if($request_method === 'GET'){
            $this->getAll();
        }
    }


    

    /**
     * All account current User
     * @return array list 
     */
    private function getAll(){
        $this->resp->result = 0;
        $AuthUser = $this->getVariable("AuthUser");

          
        $data = [];
        
        try {
            // Get accounts
            $query = DB::table(TABLE_PREFIX.TABLE_NOTIFICATIONS)
                    ->where("user_id", "=", $AuthUser->get("id"))
                    ->orderBy("id", "desc")
                    ->limit(5)
                    ->offset(0);
            $res = $query->get();     

            foreach($res as $a){
                $id = (int)$a->id;
                $data[] = array(
                    "id" => $id,
                    "title" => $a->title,
                    "content" => $a->content,
                    "is_read" => (bool)$a->is_read,
                    "created_at" => $a->created_at,
                    "updated_at" => $a->updated_at,
                );
            }
            $this->resp->data = $data;
            $this->resp->result = 1;        
        } catch (\Exception $ex) {
            $this->resp->msg = $ex->getMessage();
        }
        $this->jsonecho();
    }

    /**
     * read all notification
     * @return void
     */
    private function save(){
        $this->resp->result = 0;
        $AuthUser = $this->getVariable("AuthUser");        

       try {
           
        DB::table(TABLE_PREFIX.TABLE_NOTIFICATIONS)
            ->where("user_id", "=", $AuthUser->get("id"))
            ->update(array(
              "is_read" => 1
            ));

       } catch (Exception $ex) {
            $this->resp->msg = $ex->getMessage();
            $this->jsonecho();
       }

        $this->resp->result = 1;
        $this->resp->msg = __("All notification is marked as read.");
        $this->jsonecho();
    }
}