<?php 
    /**
     * Budgets Controller
     */
    class GoalsController extends Controller
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

            $request_method = Input::method();
            if($request_method === 'POST'){
                $this->save();
            }else if($request_method === 'GET'){
                $this->getAll();
            }
        }


        /***************************************
         * @author Phong-Kaster
         * $this->resp->result = 0 means fail
         * $this->resp->result = 1 means successful
         * 
         * get all budgets by AuthUser's ID
         ***************************************/

        private function getAll()
        {
            /**Step 1 */
            $AuthUser = $this->getVariable("AuthUser");
            /**Step 2 */
            $order = Input::get("order");
            $search = Input::get("search");
            $draw = (int)Input::get("draw");

            $length   = Input::get("length");
            $start    = Input::get("start");

            $status = (int)Input::get("status");
            $dateFrom = Input::get("dateFrom");
            $dateTo = Input::get("dateTo");

            if($dateFrom && !isValidDate($dateFrom, 'Y-m-d')){
                $this->resp->msg = __("Date from is invalid !");
                $this->jsonecho();
            }

            if($dateTo && !isValidDate($dateTo, 'Y-m-d')){
                $this->resp->msg = __("Date to is invalid !");
                $this->jsonecho();
            }

            if($draw){
                $this->resp->draw = $draw; 
            }
               
            $data = [];

            try 
            {

                $query = DB::table(TABLE_PREFIX.TABLE_GOALS)
                        ->where(TABLE_PREFIX.TABLE_GOALS.".user_id", "=", $AuthUser->get("id"))
                        ->select([
                            TABLE_PREFIX.TABLE_GOALS.".*"
                        ]);

                if(in_array($status, [1, 2, 3])){
                    $query->where(TABLE_PREFIX.TABLE_GOALS.".status", "=", $status);
                }

                if($dateFrom && $dateFrom){
                    $query->whereBetween(TABLE_PREFIX.TABLE_GOALS.".deadline", $dateFrom, $dateTo);
                }
                
                /**Step 6 */
                $search_query = trim((string)$search);
                if($search_query){
                    $query->where(function($q) use($search_query)
                        {
                            $q->where(TABLE_PREFIX.TABLE_GOALS.".name", 'LIKE', "%".$search_query.'%');
                        });     
                }


                /**Step 7 */
                if($order && isset($order["column"]) && isset($order["dir"])){
                    $sort =  in_array($order["dir"],["asc","desc"]) ? $order["dir"] : "desc";
                    $column_name = trim($order["column"]) != "" ? trim($order["column"]) : "id";
                    if(in_array($column_name, ["amount", "balance"])){
                        $query->orderBy(DB::raw($column_name. " * 1"), $sort);
                    }else{
                        $query->orderBy($column_name, $sort);
                    }
                }  
                
                /**Step 8 */
                $res = $query->get();
                $count = count($res);
                
                $this->resp->summary = array(
                    "total_count" => $count
                );

                
                /**Step 9 */
                $query->limit($length ? $length : 10)->offset($start ? $start : 0);
                $res = $query->get();

                foreach( $res as $g)
                {
                    $data[] = array(
                        "id" => (int)$g->id,
                        "name" => $g->name,
                        "balance" => (double)$g->balance,
                        "amount" => (double)$g->amount,
                        "deposit" => (double)$g->deposit,
                        "deadline" => $g->deadline,
                        "status" => (int)$g->status,
                    );
                }

                $this->resp->result = 1;
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
         * get all budgets by AuthUser's ID
         ***************************************/
        private function save()
        {
            /*Step 1*/
            $this->resp->result = 0;

            $AuthUser = $this->getVariable("AuthUser");

            /*Step 2*/
            $required_fields = ["name", "balance", "amount", "deadline"];
            foreach( $required_fields as $field)
            {
                if( !Input::post($field) )
                {
                    $this->resp->msg = __("Missing some required field !");
                    $this->jsonecho();
                }
            }

            /**Step 3 */

            $amount = (double)Input::post("amount");
            if( $amount <= 0)
            {
                $this->resp->msg = __("Amount can not less than 0 !");
                $this->jsonecho();
            }

            $balance = (double)Input::post("balance");
            if( $balance < 0)
            {
                $this->resp->msg = __("Balance can not less than 0 !");
                $this->jsonecho();
            }

            $remaining  = $amount - (0 + $balance);

            try 
            {
                /**Step 4 */
                $Goal = Controller::model("Goal");
                $Goal->set("user_id", $AuthUser->get("id") )
                    ->set("name", Input::post("name"))
                    ->set("balance", $balance)
                    ->set("amount", $amount)
                    ->set("deposit", 0)
                    ->set("status", $remaining > 0 ? 1 : 2)
                    ->set("deadline", Input::post("deadline"))
                    ->save();
                
                
                /**Step 5 */
                $this->resp->result = 1;
                $this->resp->goal = (int)$Goal->get("id");
                $this->resp->msg = __("Goals created successfully !");
            } catch (\Exception $ex) {
                $this->resp->msg = $ex->getMessage();
            }
            
            $this->jsonecho();
        }   
    }
?>