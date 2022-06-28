<?php 
    class BudgetController extends Controller
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

            $Budget = Controller::model("Budget", $Route->params->id);
            
            if (!$Budget->isAvailable() || 
                $AuthUser->get("id") != $Budget->get("user_id")) 
            {
                $this->resp->msg = __("Budget doesn't exist!");
                $this->jsonecho();
            }

            $this->setVariable("Budget", $Budget);

            $request_method = Input::method();
            if($request_method === 'PUT'){
                $this->save();
            }else if($request_method === 'GET'){
                $this->getById();
            }else if($request_method === 'DELETE'){
                $this->remove();
            }
        }


        /***************************************
         * @author Phong-Kaster
         * $this->resp->result = 0 means fail
         * $this->resp->result = 1 means successful
         * 
         * change and save budgets
         ***************************************/
        private function save()
        {
            /*Step 1*/
            $this->resp->result = 0;
            $AuthUser = $this->getVariable("AuthUser");
            $Budget = $this->getVariable("Budget");
           

            /*Step 2*/
            $required_fields = ["amount","description"];
            foreach( $required_fields as $field)
            {
                if( !Input::put($field) )
                {
                    $this->resp->msg = __("Missing some required field !");
                    $this->jsonecho();
                }
            }

            /**Step 3 */
           

            $Budget->set("amount", Input::put("amount"))
                    ->set("description", Input::put("description"))
                    ->save();
            
            $this->resp->result = 1;
            $this->resp->msg = __("Budgets changed successfully !");
            $this->jsonecho();
        }



        /***************************************
         * @author Phong-Kaster
         * $this->resp->result = 0 means fail
         * $this->resp->result = 1 means successful
         * 
         * change and save budgets
         ***************************************/
        private function remove()
        {
            $this->resp->result = 0;
            $AuthUser = $this->getVariable("AuthUser");
            $Budget = $this->getVariable("Budget");

            // check budgets
            $Budget->delete();

            $this->resp->result = 1;
            $this->resp->msg = __("Budget is deleted successfully !");
            $this->resp->budget = (int)$Budget->get("id");
            $this->jsonecho();
        }


        private function getByID()
        {
            $this->resp->result = 0;
            $AuthUser = $this->getVariable("AuthUser");
            $Budget = $this->getVariable("Budget");

            $date = explode("-", $Budget->get("todate"));
            $this->resp->result = 1;
            $this->resp->budget = array(
                "id" =>  (int)$Budget->get("id"),
                "category" => array(
                    "id" => (int)$Budget->get("category_id"),
                    "name" => $Budget->get("category_name"),
                    "type" => (int)$Budget->get("category_type"),
                    "description" => $Budget->get("category_description"),
                    "color" => "#".$Budget->get("category_color"),
                ),
                "amount" => (double)$Budget->get("amount"),
                "fromdate" => $Budget->get("fromdate"),
                "todate" => $Budget->get("todate"),
                "description" => $Budget->get("description")
            );
            $this->resp->months = $date[1];
            $this->resp->years = $date[0];
            $this->jsonecho();
        }
    }
?>