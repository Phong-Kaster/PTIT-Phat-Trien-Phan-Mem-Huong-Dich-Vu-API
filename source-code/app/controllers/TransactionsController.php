<?php 
    class TransactionsController extends Controller
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


            $page = isset($Route->params->page) ? $Route->params->page : "income";
            
            $type = $page == "income" ? 1 : 2;

            $request_method = Input::method();
            if($request_method === 'POST'){
                $this->save();
            }else if($request_method === 'GET'){
                $this->getAll($type);
            }
        }


        /***************************************
         * @author Phong-Kaster
         * $this->resp->result = 0 means fail
         * $this->resp->result = 1 means successful
         * 
         * get all transactions by AuthUser ID
         ***************************************/
        
        private function getAll($type)
        {

            /**Step 1 */
            $AuthUser = $this->getVariable("AuthUser");
            /**Step 2 */
            $order = Input::get("order");
            $search = Input::get("search");
            $draw = (int)Input::get("draw");

            $length   = Input::get("length") ? (int)Input::get("length") : 10;
            $start    = Input::get("start") ? (int)Input::get("start") : 0;
    

            /**Step 3 */
            $this->resp->result = 0;
            if($draw){
                $this->resp->draw = $draw; 
            }   
            $data = [];

            try 
            {
                /**Step 5 */
                /**
                 * DB::query(select * 
                 * from TABLE_TRANSACTIONS as t,
                 *        TABLE_ACCOUNTS as a,
                 *         TABLE_CATEGORIES as c
                 * where t.user_id = 1
                 * and t.type = 1
                 * and a.id = t.account_id
                 * and c.id = t.category_id)
                 */
                $query = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
                        ->where(TABLE_PREFIX.TABLE_TRANSACTIONS.".user_id", "=", $AuthUser->get("id") )
                        ->where(TABLE_PREFIX.TABLE_TRANSACTIONS.".type", "=", $type)
                        ->leftJoin(
                            TABLE_PREFIX.TABLE_ACCOUNTS,
                            TABLE_PREFIX.TABLE_TRANSACTIONS.".account_id",
                            "=",
                            TABLE_PREFIX.TABLE_ACCOUNTS.".id"
                        )
                        ->leftJoin(
                            TABLE_PREFIX.TABLE_CATEGORIES,
                            TABLE_PREFIX.TABLE_TRANSACTIONS.".category_id",
                            "=",
                            TABLE_PREFIX.TABLE_CATEGORIES.".id"
                        )
                        ->leftJoin(
                            TABLE_PREFIX.TABLE_USERS,
                            TABLE_PREFIX.TABLE_TRANSACTIONS.".user_id",
                            "=",
                            TABLE_PREFIX.TABLE_USERS.".id"
                        )
                        ->select([
                            TABLE_PREFIX.TABLE_TRANSACTIONS.".*", 
                            DB::raw(TABLE_PREFIX.TABLE_CATEGORIES.".name as category_name" ),
                            DB::raw(TABLE_PREFIX.TABLE_CATEGORIES.".id as category_id" ),
                            DB::raw(TABLE_PREFIX.TABLE_CATEGORIES.".type as category_type"),
                            DB::raw(TABLE_PREFIX.TABLE_CATEGORIES.".color as category_color"),
                            DB::raw(TABLE_PREFIX.TABLE_CATEGORIES.".description as category_description"),
    
                            DB::raw(TABLE_PREFIX.TABLE_ACCOUNTS.'.name as account_name'), 
                            DB::raw(TABLE_PREFIX.TABLE_ACCOUNTS.'.id as account_id'), 
                            DB::raw(TABLE_PREFIX.TABLE_ACCOUNTS.'.balance as account_balance'), 
                            DB::raw(TABLE_PREFIX.TABLE_ACCOUNTS.'.accountnumber as account_accountnumber'), 
                            DB::raw(TABLE_PREFIX.TABLE_ACCOUNTS.'.description as account_description'), 
    
                        ]);


                /**Step 6 */
                $search_query = trim((string)$search);
                if( $search_query )
                {
                    $query->where(function($q) use($search_query)
                    {
                        $q->where(TABLE_PREFIX.TABLE_TRANSACTIONS.".reference", 'LIKE', $search_query.'%')
                        ->orWhere(TABLE_PREFIX.TABLE_TRANSACTIONS.".description", 'LIKE', $search_query.'%')
                        ->orWhere(TABLE_PREFIX.TABLE_TRANSACTIONS.".name", "LIKE", $search_query.'%')
                        ->orWhere(TABLE_PREFIX.TABLE_CATEGORIES.".name", 'LIKE', $search_query.'%');
                    });  
                }

                /**Step 7 */
                if($order && isset($order["column"]) && isset($order["dir"]))
                {
                    $type = $order["dir"];
                    $validType = ["asc","desc"];
                    $sort =  in_array($type, $validType) ? $type : "desc";


                    $column_name = trim($order["column"]) != "" ? trim($order["column"]) : "id";
                    $column_name = str_replace(".", "_", $column_name);
    

                    if(in_array($column_name, ["amount"])){
                        $query->orderBy(DB::raw($column_name. " * 1"), $sort);
                    }else{
                        $query->orderBy($column_name, $sort);
                    }
                }
                
                /**Step 8 */
                $result = $query->get();
                $count = count($result);    
                $this->resp->summary = array(
                    "total_count" => $count
                );
                
                /**Step 9 */
                $query->limit($length ? $length : 10)->offset($start ? $start : 0);
                $res = $query->get();

                foreach( $res as $t)
                {
                    $data[] = array( 
                        "amount" => (double) $t->amount,
                        "description" => $t->description,
                        "name" => $t->name,
                        "reference" => $t->reference,
                        "transactiondate" => $t->transactiondate,
                        "id" => (int)$t->id,
                        "type" => (int)$t->type,

                        "account" => array(
                            "id" => (int)$t->account_id,
                            "name" => $t->account_name,
                            "balance" => (double)$t->account_balance,
                            "accountnumber" => $t->account_accountnumber,
                            "description" => $t->account_description,
                        ),
    
                        "category" => array(
                            "id" => (int)$t->category_id,
                            "name" => $t->category_name,
                            "type" => (int)$t->category_type,
                            "color" => "#".$t->category_color,
                            "description" => $t->category_description,
                        ),
                    );
                }

                $this->resp->result = 1;
                $this->resp->search = $search_query ;
                $this->resp->data = $data;
            } 
            catch (\Exception $ex) 
            {
                $this->resp->msg = $ex->getMessage();
            }
            $this->jsonecho();
        }
    

        /***************************************
         * @author Phong-Kaster
         * $this->resp->result = 0 means fail
         * $this->resp->result = 1 means successful
         * 
         * purpose: get all transactions by AuthUser ID
         * 
         * Step 1: declare a local variable and $AuthUser
         * Step 2: are required fields filled up ?
         * Step 3: check transaction code is correct
         *      + type = 1 means it receives money
         *      + type = 2 means it transfer money
         * Step 4: is category ID is valid ?
         * Step 5: is account ID valid ?
         * Step 6: is account balance enough to do the transaction
         * Step 7: write the transaction into database
         * Step 8: update latest account balance
         * 
         * return @self
         ***************************************/
        private function save()
        {
            try 
            {
            
                /**Step 1 */
                $this->resp->result = 0;
                $AuthUser = $this->getVariable("AuthUser");



                /**Step 2 */
                $required_fields = ["category_id","account_id","amount","reference", "type"];

                foreach($required_fields as $field )
                {
                    if( !Input::post($field) )
                    {
                        $this->resp->msg = __("Missing some compulsory fields !");
                        $this->jsonecho();
                    }
                }



                /**Step 3 */
                $type = Input::post("type");
                if(  !in_array($type, array("1", "2"))  )
                {
                    $this->resp->msg = __("Transaction's type accepts value between 1 and 2 only ! 1 means inflow, 2 means outflow");
                    $this->jsonecho();
                }
                
                
                if(!isValidDate(Input::post("transactiondate"))){
                    $this->resp->msg = __("Transaction date is invalid!");
                    $this->jsonecho();
                }

                /**Step 4 */
                $Category = Controller::model("Category", Input::post("category_id"));
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
                $Account = Controller::model("Account", Input::post("account_id"));
                if(!$Account->isAvailable() || $Account->get("user_id") != $AuthUser->get("id")){
                    $this->resp->msg = __("Account ID doesn't exist!");
                    $this->jsonecho();
                }


                /**Step 7 */
                $Transaction = Controller::model("Transaction");
                $Transaction->set("user_id", $AuthUser->get("id"))
                            ->set("category_id", $Category->get("id"))
                            ->set("account_id", $Account->get("id"))
                            ->set("name", Input::post("name"))
                            ->set("amount", Input::post("amount"))
                            ->set("reference", Input::post("reference"))
                            ->set("transactiondate", Input::post("transactiondate"))
                            ->set("type", $type)
                            ->set("description", Input::post("description"))
                            ->save();

                            
                $this->resp->result = 1;
                $this->resp->msg = __("Transaction created successfully !");
                $this->resp->transaction = (int)$Transaction->get("id");
            }
            catch(Exception $ex)
            {
                $this->resp->msg = $ex->getMessage();
            }
            $this->jsonecho();
        }
    }
?>