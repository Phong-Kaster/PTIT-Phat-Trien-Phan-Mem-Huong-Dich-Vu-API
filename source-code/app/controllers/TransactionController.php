<?php 
    /**
     * Transaction Controller
     */
    class TransactionController extends Controller
    {
        public function process()
        {
            $AuthUser = $this->getVariable("AuthUser");
            $Route = $this->getVariable("Route");
            if( !$AuthUser )
            {
                header("Location: ".APPURL."/login");
                exit;
            }

            if( !isset($Route->params->id) )
            {
                $this->resp->msg = __("ID is required if you want to check transaction !");
                $this->jsonecho();
            }

            $Transaction = Controller::model("Transaction", $Route->params->id);
            if( !$Transaction->isAvailable() || 
                $Transaction->get("user_id") != $AuthUser->get("id") )
            {
                $this->resp->msg = __("Transaction ID doesn't exist !");
                $this->jsonecho();
            }
            $this->setVariable("Transaction", $Transaction);

            /**
             * $_SERVER['REQUEST_METHOD'] is one of the PHP server variables. 
             * It determines: Which request method was used to access the page; 
             * i.e. 'GET', 'HEAD', 'POST', 'PUT'. It's generally defaulted to GET though, 
             * so don't rely on it for determining if a form has been posted or not (eg if not POST then must be GET etc)
             */
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
         ***************************************/
        private function getById()
        {
            /**Step 1 */
            $this->resp->result = 0;
            $AuthUser = $this->getVariable("AuthUser");
            $Transaction = $this->getVariable("Transaction");

            /**Step 4 */
            $this->resp->result = 1;
            $this->resp->data = array(
                "id" => $Transaction->get("id"),
                "category_id" => $Transaction->get("category_id"),
                "account_id"  => $Transaction->get("account_id"),
                "name"        => $Transaction->get("name"),
                "amount"      => $Transaction->get("amount"),
                "reference"   => $Transaction->get("reference"),
                "transactiondate" => $Transaction->get("transactiondate"),
                "type"        => $Transaction->get("type"),
                "description" => $Transaction->get("description")
            );

            $this->jsonecho();
        }



        /***************************************
         * @author Phong-Kaster
         * 
         * $this->resp->result = 0 means fail
         * $this->resp->result = 1 means successful
         * 
         * change transaction's detail by its ID
         ***************************************/
        private function save()
        {
            /**Step 1 */
            $this->resp->result = 0;
            $AuthUser = $this->getVariable("AuthUser");
            $Transaction = $this->getVariable("Transaction");

            /**Step 2 */
            $required_fields = ["category_id", "account_id", "amount", "type", "transactiondate"];
            foreach($required_fields as $field )
            {
                if( !Input::put($field) )
                {
                    $this->resp->msg = __("Missing some compulsory fields !");
                    $this->jsonecho();
                }
            }

            $transactiondate = Input::put("transactiondate");

            if(!isValidDate($transactiondate)){
                $this->resp->msg = __("transactiondate is invalid!");
                $this->jsonecho();
            }


            /**Step 3 */
            $type = Input::put("type");
            if(  !in_array($type, array("1", "2"))  )
            {
                $this->resp->msg = __("Transaction's type accepts value between 1 and 2 only ! 1 means inflow, 2 means outflow");
                $this->jsonecho();
            }
            

            /**Step 4 */
            $Category = Controller::model("Category", Input::put("category_id"));
            if (!$Category->isAvailable() && 
                $Category->get("user_id") != $AuthUser->get("id") ) 
            {
                $this->resp->msg = __("Category ID doesn't exist!");
                $this->jsonecho();
            }

            if($type != $Category->get("type")){
                $type = $Category->get("type");
            }


            /**Step 5 - Kiem tra xem account_id co thuoc user_id  hay khong ? */
            $Account = Controller::model("Account", Input::put("account_id"));
            if(!$Account->isAvailable() || $Account->get("user_id") != $AuthUser->get("id")){
                $this->resp->msg = __("Account ID doesn't exist!");
                $this->jsonecho();
            }

            try 
            {
                /**Step 7 */
                $Transaction
                    ->set("category_id", $Category->get("id"))
                    ->set("account_id", $Account->get("id"))
                    ->set("name", Input::put("name"))
                    ->set("amount", Input::put("amount"))
                    ->set("reference", Input::put("reference"))
                    ->set("transactiondate", $transactiondate)
                    ->set("type", $type)
                    ->set("description", Input::put("description"))
                    ->save();



                /**Step 8 */
                $this->resp->result = 1;
                $this->resp->msg = __("Transaction changed successfully !");
                $this->resp->transaction = (int)$Transaction->get("id");
                $this->jsonecho();
            } 
            catch (\Exception $ex) 
            {
               $this->resp->msg = $ex->getMessage();
               $this->jsonecho();
            }
            
        }



        /***************************************
         * @author Phong-Kaster
         * 
         * $this->resp->result = 0 means fail
         * $this->resp->result = 1 means successful
         * 
         * remove transaction by its ID
         ***************************************/
        private function remove()
        {
            /**Step 1 */
            $this->resp->result = 0;
            $AuthUser = $this->getVariable("AuthUser");
            $Transaction = $this->getVariable("Transaction");



            $Transaction->delete();

            /**Step 4 */
            $this->resp->result = 1;
            $this->resp->msg = __("Transaction deleted successfully !");
            $this->resp->transaction = (int)$Transaction->get("id");
            $this->jsonecho();
        }
    }
?>