<?php 
    /**
     * Budgets Controller
     */
    class BudgetsController extends Controller
    {
        /**
         * Process
         */
        public function process()
        {
            $AuthUser = $this->getVariable("AuthUser");
            $Route = $this->getVariable("Route");

            // Auth
            if (!$AuthUser){
                header("Location: ".APPURL."/login");
                exit;
            } 

            $page = isset($Route->params->page) ? $Route->params->page : "category";
            $action = isset($Route->params->action) ? $Route->params->action : "";

            $request_method = Input::method();
            if($request_method === 'POST')
            {
                $this->save();
            }
            else if($request_method === 'GET')
            {
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

            $this->resp->result = 0;
            $order = Input::get("order");
            $search = Input::get("search");
            $draw = (int)Input::get("draw");

            $length   = Input::get("length") ? (int)Input::get("length") : 10;
            $start    = Input::get("start") ? (int)Input::get("start") : 0;

            /**Step 2*/
            if($draw){
                $this->resp->draw = $draw; 
            }
            $data = [];

            try 
            {
                /**Step 5 */
                $query = DB::table(TABLE_PREFIX.TABLE_BUDGETS)
                        ->where(TABLE_PREFIX.TABLE_BUDGETS.".user_id", "=", $AuthUser->get("id"))
                        ->join(TABLE_PREFIX.TABLE_CATEGORIES,
                                TABLE_PREFIX.TABLE_CATEGORIES.".id",
                                "=",
                                TABLE_PREFIX.TABLE_BUDGETS.".category_id"
                        )
                        ->leftJoin(
                            TABLE_PREFIX.TABLE_USERS,
                            TABLE_PREFIX.TABLE_BUDGETS.".user_id",
                            "=",
                            TABLE_PREFIX.TABLE_USERS.".id"
                        );  
                
                $query->select([ 
                    TABLE_PREFIX.TABLE_BUDGETS.".*",
                    DB::raw(TABLE_PREFIX.TABLE_CATEGORIES.".name as category_name" ),
                    DB::raw(TABLE_PREFIX.TABLE_CATEGORIES.".id as category_id" ),
                    DB::raw(TABLE_PREFIX.TABLE_CATEGORIES.".type as category_type"),
                    DB::raw(TABLE_PREFIX.TABLE_CATEGORIES.".description as category_description"),
                    DB::raw(TABLE_PREFIX.TABLE_CATEGORIES.".color as category_color"),
                ]);
                
                /**Step 6 */
                $search_query = trim((string)$search);
                if( $search_query )
                {
                    $query->where(function($q) use($search_query)
                    {
                        $q->where(TABLE_PREFIX.TABLE_TRANSACTIONS.".description", 'LIKE', $search_query.'%')
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

                $res = $query->get();
                $count = count($res);
                
                

                /**Step 9 */
                $query->limit($length)->offset($start);
                $res = $query->get();


                foreach($res as $t )
                {
                    $data[] = array(
                        "id" => (int)$t->id,
                        "category" => array(
                            "id" => (int)$t->category_id,
                            "name" => $t->category_name,
                            "type" => (int)$t->category_type,
                            "color" => "#".$t->category_color,
                            "description" => $t->category_description,
                        ),
                        "amount" => (double)$t->amount,
                        "fromdate" => $t->fromdate,
                        "todate" => $t->todate,
                        "description" => $t->description
                    );
                }

                $this->resp->result = 1;
                $this->resp->summary = array(
                    "total_count" => $count
                );
                $this->resp->data = $data;
            } 
            catch (\Exception $ex) {
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
            try 
            {
                /*Step 1*/
                $this->resp->result = 0;
                $AuthUser = $this->getVariable("AuthUser");

                /*Step 2*/
                $required_fields = ["month", "year", "category_id","amount", "description"];
                foreach( $required_fields as $field)
                {
                    if( !Input::post($field) )
                    {
                        $this->resp->msg = __("Missing some required field !");
                        $this->jsonecho();
                    }
                }

                $currentYear = date('Y');
                $currentMonth = date('m');
                if( Input::post("year") < $currentYear)
                {
                    $this->resp->msg = __("Selected year can not less than current year !");
                    $this->jsonecho();
                }
                if(Input::post("year") == $currentYear && Input::post("month") < $currentMonth  )
                {
                    $this->resp->msg = __("Selected month can not less than current month !");
                    $this->jsonecho();
                }

                $amount = (int)Input::post("amount");
                if($amount < 1){
                    $this->resp->msg = __("Amount must greater than 0 !");
                    $this->jsonecho();
                }

                $year = Input::post("year");
                $month = Input::post("month");
                $dayOf12Month = [0, 31,28,31,30,31,30,31,31,30,31,30,31];
                $theLastDayOfMonth = $dayOf12Month[ (int)$month ];
                $fromdate = $year."-".$month."-01";

                if( $month == 2 )
                {
                    $theLastDayOfMonth = $this->isALeapYear($year);
                }
                $todate   = $year."-".$month."-".$theLastDayOfMonth;

                $Category = Controller::model("Category", Input::post("category_id"));
                if (!$Category->isAvailable() && 
                    $Category->get("user_id") != $AuthUser->get("id") ) 
                {
                    $this->resp->msg = __("Category ID doesn't exist!");
                    $this->jsonecho();
                }


                /**Step 3 */
                $Budget = Controller::model("Budget");
                $Budget->set("user_id", $AuthUser->get("id") )
                    ->set("category_id", $Category->get("id") )
                    ->set("amount", $amount)
                    ->set("fromdate", $fromdate)
                    ->set("todate", $todate)
                    ->set("description", Input::post("description"))
                    ->save();
                
                $this->resp->result = 1;
                $this->resp->budget = (int)$Budget->get("id");
                $this->resp->fromdate = $fromdate;
                $this->resp->todate = $todate;
                $this->resp->msg = __("Budgets created successfully !");
            } catch (\Exception $ex) {
                $this->resp->msg = $ex->getMessage();
                $this->resp->fromdate = $fromdate;
                $this->resp->todate = $todate;
            }
            
            $this->jsonecho();
        }
        
        /***
         * @author Phong-Kaster
         * 
         * @year is the year we need to check it is a leap year or a normal year
         * 
         * this function handle when @month is Ferbruary. It figures out the last day of ferbruary is 28th or 29th
         * 
         * If the year is evenly divisible by 4, go to step 2. Otherwise, go to step 5.
         * If the year is evenly divisible by 100, go to step 3. Otherwise, go to step 4.
         * If the year is evenly divisible by 400, go to step 4. Otherwise, go to step 5.
         * The year is a leap year (it has 366 days).
         * The year is not a leap year (it has 365 days).
         * 
         * @return the last day of Ferbuary is 28th or 29th 
         */
        private function isALeapYear($year)
        {
            $theLastDayOfFerbrary = 28;
            $flag = is_numeric($year);


            if( !$flag  )
                return;

            if( $year % 4 == 0 && 
                $year % 100 != 0 ||
                $year % 400 == 0 )
            {
                 $theLastDayOfFerbrary = 29;
            }

            return $theLastDayOfFerbrary;
        }
    }
?>