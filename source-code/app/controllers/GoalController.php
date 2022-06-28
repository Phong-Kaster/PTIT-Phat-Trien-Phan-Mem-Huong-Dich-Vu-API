<?php 
    class GoalController extends Controller
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

            $Goal = Controller::model("Goal", $Route->params->id);

            if (!$Goal->isAvailable()|| $AuthUser->get("id") != $Goal->get("user_id")) {
                $this->resp->msg = __("Goal doesn't exist!");
                $this->jsonecho();
            }

            $this->setVariable("Goal", $Goal);

            $request_method = Input::method();
            if($request_method === 'PUT')
            {
                $this->save();
            }
            else if($request_method === 'GET')
            {
                $this->getById();
            }
            else if($request_method === 'DELETE')
            {
                $this->remove();
            }
            else if ($request_method == 'POST') 
            {
                if(Input::post("action") == "deposit"){
                    $this->deposit();
                }
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
            $Goal = $this->getVariable("Goal");

            /*Step 3*/
            $required_fields = [ "name", "amount", "balance", "deadline"];
            foreach( $required_fields as $field)
            {
                if( !Input::put($field) )
                {
                    $this->resp->msg = __("Missing some compulsory field !");
                    $this->jsonecho();
                }
            }

            $deadline = Input::put("deadline");

            if(!isValidDate($deadline)){
                $this->resp->msg = __("Deadline is invalid !");
                $this->jsonecho();
            }

            $amount = (double)Input::put("amount");
            if( $amount <= 0)
            {
                $this->resp->msg = __("Amount can not less than 0 !");
                $this->jsonecho();
            }

            $balance = (double)Input::put("balance");
            if( $balance < 0)
            {
                $this->resp->msg = __("Balance can not less than 0 !");
                $this->jsonecho();
            }

            try 
            {
                $remaining  = $amount - ($Goal->get("deposit") + $balance);

                /**Step 4 */
                $Goal->set("name", Input::put("name"))
                    ->set("balance", $balance)
                    ->set("amount", $amount)
                    ->set("status", $remaining > 0 ? 1 : 2)
                    ->set("deadline", Input::put("deadline"))
                    ->save();
                    
                
                
                $this->resp->result = 1;
                $this->resp->goal = (int)$Goal->get("id");
                $this->resp->msg = __("Goal changed successfully !");
                $this->jsonecho();
            } catch (\Exception $ex) {
                $this->resp->msg = $ex->getMessage();
                $this->jsonecho();
            }
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
            /**Step 1 */
            $this->resp->result = 0;
            $AuthUser = $this->getVariable("AuthUser");
            $Goal = $this->getVariable("Goal");

            $Goal->delete();

            /**Step 4 */
            $this->resp->result = 1;
                $this->resp->goal = (int)$Goal->get("id");
            $this->resp->msg = __("Goal is deleted successfully !");
            $this->jsonecho();
        }


        private function getByID()
        {
            $this->resp->result = 0;
            $AuthUser = $this->getVariable("AuthUser");
            $Goal = $this->getVariable("Goal");

            $this->resp->result = 1;
            $this->resp->data = array(
                "id" =>  $Goal->get("id"),
                "name" => $Goal->get("name"),
                "balance" => (double) $Goal->get("balance"),
                "amount" => (double) $Goal->get("amount"),
                "deposit" => (double) $Goal->get("deposit"),
                "deadline" => $Goal->get("deadline")
            );

            
            $this->jsonecho();
        }


        private function deposit()
        {
            $this->resp->result = 0;
            $AuthUser = $this->getVariable("AuthUser");
            $Goal = $this->getVariable("Goal");
           
            $required_fields = [ "deposit"];
            foreach( $required_fields as $field)
            {
                if( !Input::post($field) )
                {
                    $this->resp->msg = __("Missing some required field !");
                    $this->jsonecho();
                }
            }

            $target   = $Goal->get("amount");
            $deposit   = $Goal->get("deposit");
            $balance   = $Goal->get("balance");
            $totaldeposit  = $deposit + $balance;
            $remaining   = $target - ($deposit + $balance);

            if($remaining <= 0){
                $this->resp->msg = __('Deposit have been done');
                $this->jsonecho();
            }

            $deposit = $Goal->get("deposit") + Input::post("deposit");
            $remaining  = $Goal->get("amount") - ($deposit + $Goal->get("balance"));

            $Goal->set("deposit", $deposit)
                ->set("status", $remaining > 0 ? 1 : 2)
                 ->save();

            

            $this->resp->result = 1;
            $this->resp->goal = (int)$Goal->get("id");
            $this->resp->msg = __('Deposit have been added');
            $this->jsonecho();
        }
    }
?>