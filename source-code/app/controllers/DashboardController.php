<?php
/**
 * Dashboard Controller
 */
class DashboardController extends Controller
{
    /**
     * Process
     */
    public function process()
    {   
        /**Step 1 */
        $AuthUser = $this->getVariable("AuthUser");
        $Route = $this->getVariable("Route");

        /**Step 2 */
        if( !$AuthUser )
        {
            header("Location: ".APPURL."/login");
            exit;
        }

        /**Step 3 */
        $page = isset($Route->params->page) ? $Route->params->page : "category" ;
        $type = "";
        

        if( in_array($page, ["category", "latest", "categoryyearly"]) )
        {
            $type = $Route->params->type == "income" ? 1 : 2;
        }
    
        /**Step 4 */
        $request_method = Input::method();
        if($request_method === 'GET'){
            switch ($page) {
                case 'accountbalance':
                    $this->statisticsByAccountBalance();
                    break;
                case 'category':
                    $this->statisticsByCategory($type);
                    break;
                case 'incomevsexpense':
                    $this->statisticsByIncomeVsExpense();
                    break;
                case 'latest':
                    $this->statisticsBylatest($type);
                    break;
                case 'latestall':
                    $this->statisticByLatestAll();
                    break;
            }
        }
    }

    private function statisticsByAccountBalance()
    {
        $AuthUser = $this->getVariable("AuthUser");

        $this->resp->result = 0;
        $data = [];

        $date = new \Moment\Moment("now", date_default_timezone_get());
        try 
        {
            $subIncome = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
                        ->select([
                            "account_id", 
                            DB::raw('SUM(amount) as amount')
                        ])
                        ->where('type', '=', 1)
                        ->where("user_id", "=", $AuthUser->get("id"))
                        ->where(DB::raw("YEAR(transactiondate) = ".date("Y")))
                        ->groupBy("account_id");

            $subExpense = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
                ->select([
                    "account_id", 
                    DB::raw('SUM(amount) as amount')
                ])
                ->where('type', '=', 2)
                ->where("user_id", "=", $AuthUser->get("id"))
                ->where(DB::raw("YEAR(transactiondate) = ".date("Y")))
                ->groupBy("account_id");

            $data = DB::table([TABLE_PREFIX.TABLE_ACCOUNTS => 'p'])
            ->where("p.user_id", "=", $AuthUser->get("id"))
            ->leftJoin(
                DB::subQuery($subIncome, 'b'), 
                function($table){
                    $table->on("b.account_id", "=", "p.id");
                }
            )
            ->leftJoin(
                DB::subQuery($subExpense, 'a'), 
                function($table){
                    $table->on("b.account_id", "=", "p.id");
                }
            )
            ->select([
                DB::raw("p.name"),
                DB::raw("COALESCE(a.amount, 0) as income"),
                DB::raw("COALESCE(b.amount, 0) as expense"),
                DB::raw("COALESCE(p.balance + (COALESCE(a.amount, 0) - COALESCE(b.amount, 0)), 0) as balance"),
            ])
            ->groupBy("p.id")
            ->get();
            
            $count = count($data);
            for ($i=0; $i < $count; $i++) { 
                $data[$i]->balance = (double) $data[$i]->balance;
                $data[$i]->expense = (double) $data[$i]->expense;
                $data[$i]->income = (double) $data[$i]->income;
            }
            $this->resp->result = 1;
            $this->resp->data = $data;
        } 
        catch (\Exeception $ex) 
        {
            $this->resp->msg = $ex->getMessage();
        }
        $this->jsonecho();
    }

    /**
     * @author Hau
     * 
     */
    private function statisticsByIncomeVsExpense()
    {
        $AuthUser = $this->getVariable("AuthUser");    
    
        $this->resp->result = 0;
        $type = Input::get("type");
        $date_type = Input::get("date");

        if(!in_array($date_type, ["week", "month", "year"])){
            $this->resp->msg = __("Date type is invalid!");
            $this->jsonecho();
        }

        try 
        {
            $date = new \Moment\Moment("now", date_default_timezone_get());            

            if($date_type == "month"){
                $from = $date->cloning()->startOf("year");
                $to = $date->cloning()->endOf("year");
                if($type == "all"){
                    $this->resp->income = $this->statisticsByTypeMonthly(1, $from, $to);
                    $this->resp->expense = $this->statisticsByTypeMonthly(2, $from, $to);
                }
                else if($type == "income"){
                    $this->resp->income = $this->statisticsByTypeMonthly(1, $from, $to);
                }
                else if($type == "expense"){
                    $this->resp->expense = $this->statisticsByTypeMonthly(2, $from, $to);
                }
            }
            else if($date_type == "week"){
                $from = $date->cloning()->startOf($date_type);
                $to = $date->cloning()->endOf($date_type);

                
                if($date->format("N") == 7){
                    $from->subtractDays(6);
                    $to->subtractDays(6);
                }else{
                    $from->addDays(1);
                    $to->addDays(1);
                }
                

                if($type == "all"){
                    $this->resp->income = $this->statisticsByTypeWeekly(1, $from, $to);
                    $this->resp->expense = $this->statisticsByTypeWeekly(2, $from, $to);
                }
                else if($type == "income"){
                    $this->resp->income = $this->statisticsByTypeWeekly(1, $from, $to);
                }
                else if($type == "expense"){
                    $this->resp->expense = $this->statisticsByTypeWeekly(2, $from, $to);
                }
            }
            else if($date_type == "year"){
                $from = $date->cloning()->subtractYears(1)->startOf("year");
                $to = $date->cloning()->endOf("year");
                if($type == "all"){
                    $this->resp->income = $this->statisticsByTypeYearly(1, $from, $to);
                    $this->resp->expense = $this->statisticsByTypeYearly(2, $from, $to);
                }
                else if($type == "income"){
                    $this->resp->income = $this->statisticsByTypeYearly(1, $from, $to);
                }
                else if($type == "expense"){
                    $this->resp->expense = $this->statisticsByTypeYearly(2, $from, $to);
                }
            }

            $this->resp->result = 1;
            $this->resp->date = array(
                'from' => $from->format("Y-m-d"),
                'to' => $to->format("Y-m-d"),
            );
        } 
        catch (\Exception $ex)
        {
            $this->resp->msg = $ex->getMessage();
        }
        
        $this->jsonecho();
    }

    private function statisticsByTypeMonthly($type, $from, $to)
    {
        $AuthUser = $this->getVariable("AuthUser");

        try 
        {
            // for income
            $query = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
                ->select(
                    DB::raw("SUM( IF( MONTH(".TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate) = 1, ".TABLE_PREFIX.TABLE_TRANSACTIONS.".amount, 0) ) AS jan"),
                    DB::raw("SUM( IF( MONTH(".TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate) = 2, ".TABLE_PREFIX.TABLE_TRANSACTIONS.".amount, 0) ) AS feb"),
                    DB::raw("SUM( IF( MONTH(".TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate) = 3, ".TABLE_PREFIX.TABLE_TRANSACTIONS.".amount, 0) ) AS mar"),
                    DB::raw("SUM( IF( MONTH(".TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate) = 4, ".TABLE_PREFIX.TABLE_TRANSACTIONS.".amount, 0) ) AS apr"),
                    DB::raw("SUM( IF( MONTH(".TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate) = 5, ".TABLE_PREFIX.TABLE_TRANSACTIONS.".amount, 0) ) AS may"),
                    DB::raw("SUM( IF( MONTH(".TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate) = 6, ".TABLE_PREFIX.TABLE_TRANSACTIONS.".amount, 0) ) AS jun"),
                    DB::raw("SUM( IF( MONTH(".TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate) = 7, ".TABLE_PREFIX.TABLE_TRANSACTIONS.".amount, 0) ) AS jul"),
                    DB::raw("SUM( IF( MONTH(".TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate) = 8, ".TABLE_PREFIX.TABLE_TRANSACTIONS.".amount, 0) ) AS aug"),
                    DB::raw("SUM( IF( MONTH(".TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate) = 9, ".TABLE_PREFIX.TABLE_TRANSACTIONS.".amount, 0) ) AS sep"),
                    DB::raw("SUM( IF( MONTH(".TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate) = 10, ".TABLE_PREFIX.TABLE_TRANSACTIONS.".amount, 0) ) AS oct"),
                    DB::raw("SUM( IF( MONTH(".TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate) = 11, ".TABLE_PREFIX.TABLE_TRANSACTIONS.".amount, 0) ) AS nov"),
                    DB::raw("SUM( IF( MONTH(".TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate) = 12, ".TABLE_PREFIX.TABLE_TRANSACTIONS.".amount, 0) ) AS 'dec'")
                )
                ->where("user_id", "=", $AuthUser->get("id"))
                ->where("type", "=", $type)
                ->whereBetween("transactiondate", $from->format("Y-m-d"), $to->format("Y-m-d"));

                $result = $query->get();
                $data = [];
                
                $date = $from->cloning();
    
                $index = 1;
                foreach ($result[0] as $key => $value) {
                    $data[] = array(
                        "id" => $index,
                        "date" => $date->format("Y-m-d"),
                        "name" => $date->format("M"),
                        "value" => (double) $value
                    );
                    $date->addMonths(1);
                    $index+=1;
                }  
                return $data;
        } 
        catch (\Exception $ex) 
        {
            throw new Exception($ex->getMessage());
            
        }
    }

    private function statisticsByTypeWeekly($type, $from, $to)
    {
        $AuthUser = $this->getVariable("AuthUser");

        try
        {
            // for income
            $query = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
                ->select(
                    DB::raw("SUM( IF( WEEKDAY(".TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate) = 0, ".TABLE_PREFIX.TABLE_TRANSACTIONS.".amount, 0) ) AS mon"),
                    DB::raw("SUM( IF( WEEKDAY(".TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate) = 1, ".TABLE_PREFIX.TABLE_TRANSACTIONS.".amount, 0) ) AS tue"),
                    DB::raw("SUM( IF( WEEKDAY(".TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate) = 2, ".TABLE_PREFIX.TABLE_TRANSACTIONS.".amount, 0) ) AS wed"),
                    DB::raw("SUM( IF( WEEKDAY(".TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate) = 3, ".TABLE_PREFIX.TABLE_TRANSACTIONS.".amount, 0) ) AS thu"),
                    DB::raw("SUM( IF( WEEKDAY(".TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate) = 4, ".TABLE_PREFIX.TABLE_TRANSACTIONS.".amount, 0) ) AS fri"),
                    DB::raw("SUM( IF( WEEKDAY(".TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate) = 5, ".TABLE_PREFIX.TABLE_TRANSACTIONS.".amount, 0) ) AS sat"),
                    DB::raw("SUM( IF( WEEKDAY(".TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate) = 6, ".TABLE_PREFIX.TABLE_TRANSACTIONS.".amount, 0) ) AS sun"),
                )
                ->where("user_id", "=", $AuthUser->get("id"))
                ->where("type", "=", $type)
                ->whereBetween("transactiondate", $from->format("Y-m-d"), $to->format("Y-m-d"));

            $result = $query->get();
            $data = [];
            $date = $from->cloning();

            $index = 1;
            foreach ($result[0] as $key => $value) {
                $data[] = array(
                    "id" => $index,
                    "date" => $date->format("Y-m-d"),
                    "name" => $date->format("D"),
                    "value" => (double) $value
                );
                $date->addDays(1);
                $index+=1;
            }  
            return $data;
        } 
        catch (\Exception $ex) 
        {
            throw new Exception($ex->getMessage());
            
        }
    }

    private function statisticsByTypeYearly($type, $from, $to)
    {
        $AuthUser = $this->getVariable("AuthUser");

        try
        {
            // for income
            $query = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
                ->select(
                    DB::raw("YEAR(".TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate) AS year"),
                    DB::raw("SUM(".TABLE_PREFIX.TABLE_TRANSACTIONS.".amount) AS amount"),
                )
                ->where("user_id", "=", $AuthUser->get("id"))
                ->where("type", "=", $type)
                ->whereBetween("transactiondate", $from->format("Y-m-d"), $to->format("Y-m-d"))
                ->groupBy(DB::raw("YEAR(".TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate)"))
                ->orderBy(DB::raw("YEAR(".TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate)"), "ASC");

            $result = $query->get();
            $data = [];
            $date = $from->cloning();

            $index = 1;

            foreach ($result as $value) {
                $data[] = array(
                    "id" => $index,
                    "date" => $date->format("Y-m-d"),
                    "name" => $value->year,
                    "value" => (double) $value->amount
                );
                $date->addYears(1);
                $index+=1;
            }  
            return $data;
        } 
        catch (\Exception $ex) 
        {
            throw new Exception($ex->getMessage());
            
        }
    }


    /**
     * @author Phong
     * statistics By latest Income | Expense Transactions in this week
     */
    private function statisticsBylatest($type)
    {
        $AuthUser = $this->getVariable("AuthUser");
        $this->resp->result = 1;
        
        $length   = Input::get("length") ? (int)Input::get("length") : 10;
        $start    = Input::get("start") ? (int)Input::get("start") : 0;
        $data = [];

        try 
        {
            $date = new \Moment\Moment("now", date_default_timezone_get());
            $fromdate = $date->cloning()->startOf("week");
            $todate = $date->cloning()->endOf("week");

            if($date->format("N") == 7){
                $fromdate->subtractDays(6);
                $todate->subtractDays(6);
            }else{
                $fromdate->addDays(1);
                $todate->addDays(1);
            }
            
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

                            ])
                            ->whereBetween(TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate", $fromdate->format("Y-m-d"), $todate->format("Y-m-d"))
                            ->orderBy(TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate", "desc");

            $res = $query->get();
            $count = count($res);
            
            $this->resp->summary = array(
                "total_count" => $count
            );

            $query->limit($length)->offset($start);
            $result = $query->get();
            foreach($result as $t)
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
            $this->resp->data = $data;
        } 
        catch (\Exception $ex) 
        {
            $this->resp->msg = $ex->getMessage();
        }

        $this->jsonecho();
    }

    /**
     * @author Hau
	 * Show income or expense by category monthly and yearly.
	 * @param int type income = 1 and expense = 2
	 * @return object
	 */
    private function statisticsByCategory($type)
    {
        /**Step 1 */
        $AuthUser = $this->getVariable("AuthUser");
        $date = Input::get("date");
        $this->resp->result = 0;

        if(!in_array($date, ["week", "month", "year"])){
            $this->resp->msg = __("Date type is invalid!");
            $this->jsonecho();
        }

        $data = [];
        $date_time = new \Moment\Moment("now", date_default_timezone_get());

        try 
        {
            $from = $date_time->cloning()->startOf($date);
            $to = $date_time->cloning()->endOf($date);

            
            if($date == "week"){
                if($date_time->format("N") == 7){
                    $from->subtractDays(6);
                    $to->subtractDays(6);
                }else{
                    $from->addDays(1);
                    $to->addDays(1);
                }
            }

            $query = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
                    ->leftJoin(
                        TABLE_PREFIX.TABLE_CATEGORIES,
                        TABLE_PREFIX.TABLE_TRANSACTIONS.".category_id",
                        "=",
                        TABLE_PREFIX.TABLE_CATEGORIES.".id"
                    )
                    ->select([
                        TABLE_PREFIX.TABLE_CATEGORIES.".name",
                        TABLE_PREFIX.TABLE_CATEGORIES.".id",
                        TABLE_PREFIX.TABLE_CATEGORIES.".color",
                        DB::raw("SUM(".TABLE_PREFIX.TABLE_TRANSACTIONS.".amount) as amount"),
                        DB::raw("COUNT(".TABLE_PREFIX.TABLE_TRANSACTIONS.".id) as total")
                    ])
                    ->where(TABLE_PREFIX.TABLE_TRANSACTIONS.".user_id", "=", $AuthUser->get("id"))
                    ->where(TABLE_PREFIX.TABLE_TRANSACTIONS.".type", "=", $type)
                    ->whereBetween(TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate", $from->format("Y-m-d"), $to->format("Y-m-d"));

            $query->groupBy([
                    TABLE_PREFIX.TABLE_CATEGORIES.".id",
                    TABLE_PREFIX.TABLE_CATEGORIES.".name",
                    TABLE_PREFIX.TABLE_CATEGORIES.".color",
                ]);

            $result = $query->get();

            foreach($result as $t)
            {
                $data[] = array(
                    "id" => (int)$t->id,
                    "name" => $t->name,
                    "color" => "#".$t->color,
                    "amount" => (double)$t->amount,
                    "total" => (int)$t->total,
                );
            }

            $this->resp->result = 1;
            $this->resp->data = $data;
            $this->resp->date = array(
                'from' => $from->format("Y-m-d"),
                'to' => $to->format("Y-m-d"),
            );
        } 
        catch (\Exception $ex) 
        {
            $this->resp->msg = $ex->getMessage();
        }
        $this->jsonecho();
    }


    /**
     * @author Phong
     * Show all latest transactions
     * this function supports mobile recycle view in home fragment
     */
    private function statisticByLatestAll()
    {
        /**Step 1 */
        $AuthUser = $this->getVariable("AuthUser");
        $this->resp->result = 1;
        

        $search   = Input::get("search");
        $order    = Input::get("order");
        $length   = Input::get("length") ? (int)Input::get("length") : 10;
        $start    = Input::get("start") ? (int)Input::get("start") : 0;
        $data = [];

        try 
        {
            $date = new \Moment\Moment("now", date_default_timezone_get());
            $fromdate = $date->cloning()->startOf("week")->addDays(-6);
            $todate = $date->cloning()->endOf("week");
            
            $query = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
                            ->where(TABLE_PREFIX.TABLE_TRANSACTIONS.".user_id", "=", $AuthUser->get("id") )
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

                            ])
                            ->whereBetween(TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate", $fromdate->format("Y-m-d"), $todate->format("Y-m-d"))
                            ->orderBy(TABLE_PREFIX.TABLE_TRANSACTIONS.".id", "desc");


            $search_query = trim((string)$search);
            if( $search_query)
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

            $res = $query->get();
            $count = count($res);
            
            $this->resp->summary = array(
                "total_count" => $count
            );

            $query->limit($length)->offset($start);
            $result = $query->get();
            foreach($result as $t)
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
            $this->resp->data = $data;
            $this->resp->fromdate = $fromdate;
            $this->resp->todate = $todate;
        } 
        catch (\Exception $ex) 
        {
            $this->resp->msg = $ex->getMessage();
        }

        $this->jsonecho();
    }
}