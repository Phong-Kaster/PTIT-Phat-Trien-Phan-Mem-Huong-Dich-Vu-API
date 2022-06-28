<?php 
    class NotificationController extends Controller
    {
        public function process()
        {
            $Route = $this->getVariable("Route");
            $AuthUser = $this->getVariable("AuthUser");

            // Auth
            if (!$AuthUser){
                header("Location: ".APPURL."/login");
                exit;
            } 

            if (!isset($Route->params->id)) {
                $this->resp->msg = __("ID is required!");
                $this->jsonecho(); 
            }

            $Notification = Controller::model("Notification", $Route->params->id);

            if (!$Notification->isAvailable()|| $AuthUser->get("id") != $Notification->get("user_id")) {
                $this->resp->msg = __("Notification doesn't exist!");
                $this->jsonecho();
            }

            $this->setVariable("Notification", $Notification);

            $request_method = Input::method();
            if($request_method === 'GET')
            {
                $this->read();
            }
        }


        private function read()
        {
          $this->resp->result = 0;
          $AuthUser = $this->getVariable("AuthUser");
          $Notification = $this->getVariable("Notification");

          try {
            $Notification->set("is_read", 1)->save();
          } catch (\Exception $ex) {
            $this->resp->msg = $ex->getMessage();
            $this->jsonecho();
          }

          $this->resp->result = 1;
          $this->resp->data = array(
            "id" =>  $Notification->get("id"),
            "title" => $Notification->get("title"),
            "content" => $Notification->get("content"),
            "is_read" => (bool) $Notification->get("is_read"),
            "created_at" => $Notification->get("created_at"),
            "updated_at" => $Notification->get("updated_at")
          );
          
          $this->resp->msg = __("Notification is marked as read.");
          $this->jsonecho();
        }
    }
?>