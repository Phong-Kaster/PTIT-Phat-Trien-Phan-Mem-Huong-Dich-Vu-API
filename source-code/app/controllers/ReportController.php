<?php
/**
 * Index Controller
 */
class ReportController extends Controller
{
    /**
     * Process
     */
    public function process()
    {   
        $AuthUser = $this->getVariable("AuthUser");
        $Route = $this->getVariable("Route");
        if( !$AuthUser )
        {
            header("Location: ".APPURL."/login");
            exit;
        }

        $page = isset($Route->params->page) ? $Route->params->page : "totalBalance";

        $this->setVariable("Settings", Controller::model("GeneralData", "settings"));

        $request_method = Input::method();
        if($request_method === 'GET'){
            switch ($page) {
                case 'totalBalance':
                    $this->getTotalBalance();
                    break;
                case 'transactions':
                    $this->getTransactions();
                    break;
                case 'categorymonthly':
                    $this->getCategoryMonthly();
                    break;
                case 'accounttransactions':
                    $this->getAccountTransactions();
                    break;
            }
        }
    }


    private function getTotalBalance()
    {
        /**Step 1 */
        $AuthUser = $this->getVariable("AuthUser");
        $date_type = Input::get("date");
        $this->resp->result = 0;
        
        if(!in_array($date_type, ["week", "month", "year"])){
            $this->resp->msg = __("Date type is invalid!");
            $this->jsonecho();
        }


        /**Step 2 */
        try 
        {
            $date = new \Moment\Moment("now", date_default_timezone_get());
            $from = $date->cloning()->startOf($date_type);
            $to = $date->cloning()->endOf($date_type);

            if($date_type == "week"){
                if($date->format("N") == 7){
                    $from->subtractDays(6);
                    $to->subtractDays(6);
                }else{
                    $from->addDays(1);
                    $to->addDays(1);
                }
                $this->resp->week = $this->getTotalBalanceByDate($from, $to);
            }else if($date_type == "month"){
                $this->resp->month = $this->getTotalBalanceByDate($from, $to);
            }else if($date_type == "year"){
                $this->resp->year = $this->getTotalBalanceByDate($from, $to);
            }
            $this->resp->result = 1;
        } 
        catch (\Exception $ex) 
        {
            $this->resp->msg = $ex->getMessage();
        }
        $this->jsonecho();
    }

    private function getTotalBalanceByDate($from, $to)
    {
        /**Step 1 */
        $AuthUser = $this->getVariable("AuthUser");
        /**Step 2 */
        $income   = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
                ->select(DB::raw('sum(amount) as total'))
                ->where("user_id", "=", $AuthUser->get("id"))
                ->where('type', '=', '1')
                ->whereBetween("transactiondate", $from->format("Y-m-d"), $to->format("Y-m-d"))
                ->get();

        $expense   = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
                ->select(DB::raw('sum(amount) as total'))
                ->where("user_id", "=", $AuthUser->get("id"))
                ->where('type', '=', '2')
                ->whereBetween("transactiondate", $from->format("Y-m-d"), $to->format("Y-m-d"))
                ->get();

        $balance   = $income[0]->total - $expense[0]->total;
        /**Step 5 */
        return  (double) $balance;
    }


    /**
     * @author Phong
     * lay ra danh sach cac giao dich theo loai
     * $type la loai giao dich can lay ra. 1 la thu tien, 2 la nhan tien
     * getTransactions
     */
    private function getTransactions()
    {
        /**Step 1 */
        $AuthUser = $this->getVariable("AuthUser");

        $draw = Input::get("draw");
        $search = Input::get("search");

        $category = Input::get("category_id");
        $account = Input::get("account_id");

        $fromdate = Input::get("fromdate");
        $todate = Input::get("todate");

        $order = Input::get("order");
        $type = (int)Input::get("type");

        $length   = Input::get("length") ? (int)Input::get("length") : 10;
        $start    = Input::get("start") ? (int)Input::get("start") : 0;
        
        /**Step 2*/
        $this->resp->result = 0;
        if($draw){
            $this->resp->draw = $draw; 
        }
        $data = [];

        try 
        {
            /**Step 3 */
            $query = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
                   ->where(TABLE_PREFIX.TABLE_TRANSACTIONS.".user_id", "=", $AuthUser->get("id"))
                   ->join(TABLE_PREFIX.TABLE_ACCOUNTS,
                          TABLE_PREFIX.TABLE_ACCOUNTS.".id",
                          "=",
                          TABLE_PREFIX.TABLE_TRANSACTIONS.".account_id"
                    )
                    ->join(TABLE_PREFIX.TABLE_CATEGORIES,
                            TABLE_PREFIX.TABLE_CATEGORIES.".id",
                            "=",
                            TABLE_PREFIX.TABLE_TRANSACTIONS.".category_id"
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
                        DB::raw(TABLE_PREFIX.TABLE_CATEGORIES.".color as category_color"),
                        DB::raw(TABLE_PREFIX.TABLE_CATEGORIES.".type as category_type"),
                        DB::raw(TABLE_PREFIX.TABLE_CATEGORIES.".description as category_description"),

                        DB::raw(TABLE_PREFIX.TABLE_ACCOUNTS.'.name as account_name'), 
                        DB::raw(TABLE_PREFIX.TABLE_ACCOUNTS.'.id as account_id'), 
                        DB::raw(TABLE_PREFIX.TABLE_ACCOUNTS.'.balance as account_balance'), 
                        DB::raw(TABLE_PREFIX.TABLE_ACCOUNTS.'.accountnumber as account_accountnumber'), 
                        DB::raw(TABLE_PREFIX.TABLE_ACCOUNTS.'.description as account_description'), 
 
                    ]);

            /**Step 4 */
            $search_query = trim((string)$search);
            if( $search_query )
            {
                $query->where(function($q) use($search_query) {
                    $q->where(TABLE_PREFIX.TABLE_TRANSACTIONS.".name", 'LIKE', $search_query.'%');
                    $q->orWhere(TABLE_PREFIX.TABLE_TRANSACTIONS.".description", 'LIKE', $search_query.'%');
                    $q->orWhere(TABLE_PREFIX.TABLE_CATEGORIES.".name", 'LIKE', $search_query.'%');
                    $q->orWhere(TABLE_PREFIX.TABLE_ACCOUNTS.".name", 'LIKE', $search_query.'%');
                });

            }
            if( in_array($type , [1, 2]))
            {
                $query->where(TABLE_PREFIX.TABLE_TRANSACTIONS.".type", "=", $type);
            }

            if( $category )
            {
                $query->where(TABLE_PREFIX.TABLE_TRANSACTIONS.".category_id", "=", $category);
            }

            if( $account )
            {
                $query->where(TABLE_PREFIX.TABLE_TRANSACTIONS.".account_id", "=", $account);
            }

            if( $fromdate )
            {   
                $query->where(TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate", ">=", $fromdate);
            }

            if( $todate )
            {
                $query->where(TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate", "<=", $todate);
            }
            
            if($order && isset($order["column"]) && isset($order["dir"])){
                $sort =  in_array($order["dir"],["asc","desc"]) ? $order["dir"] : "desc";
                $column_name = trim($order["column"]) != "" ? 
                        trim($order["column"]) : "id";
                $column_name = str_replace(".", "_", $column_name);

                if(in_array($column_name, ["amount"])){
                    $query->orderBy(DB::raw($column_name. " * 1"), $sort);
                }else{
                    $query->orderBy($column_name, $sort);
                }
            }

            $result = $query->get();
            $count = count($result);    
            $this->resp->summary = array(
                "total_count" => $count
            );

            $query->limit($length ? $length : 10)->offset($start ? $start : 0);
            
            $res = $query->get();
            foreach($res as $t)
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
     * @author Phong
     * lay tong thu nhap ca nam theo danh muc
     * lay tong thu nhap tung thang theo danh muc
     * Step 1: khai bao bien cuc bo
     * Step 2: kiem tra xem co the loai khong ? Neu co thi tiep tuc, khong thi dung lai
     * Step 3: lay danh sach cac category
     * Step 4: lay tong thu nhap ca nam theo danh muc
     * Step 5: lay tong thu nhap tung thang theo danh muc
     */
    private function getCategoryMonthly()
    {
        /**Step 1 */
        $AuthUser = $this->getVariable("AuthUser");

        $order = Input::get("order");
        $search = Input::get("search");
        $draw = (int)Input::get("draw");
        $type = (int)Input::get("type");

        $length   = Input::get("length") ? (int)Input::get("length") : 10;
        $start    = Input::get("start") ? (int)Input::get("start") : 0;

        $this->resp->result = 0;
        if($draw){
            $this->resp->draw = $draw;
        }
        $data = [];
        
        $date = new \Moment\Moment("now", date_default_timezone_get());

        if(!in_array($type, [1, 2])){
            $type = 1;
        }

        try 
        {
            $query = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
            ->leftJoin(
                TABLE_PREFIX.TABLE_CATEGORIES,
                TABLE_PREFIX.TABLE_TRANSACTIONS.".category_id",
                "=",
                TABLE_PREFIX.TABLE_CATEGORIES.".id"
            )
            ->select([
                TABLE_PREFIX.TABLE_CATEGORIES.".name` AS `category",
                TABLE_PREFIX.TABLE_TRANSACTIONS.".type` AS `type",
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
                DB::raw("SUM( IF( MONTH(".TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate) = 12, ".TABLE_PREFIX.TABLE_TRANSACTIONS.".amount, 0) ) AS 'dec'"),
                DB::raw("SUM( ".TABLE_PREFIX.TABLE_TRANSACTIONS.".amount ) AS total"),
            ])
            ->where(TABLE_PREFIX.TABLE_TRANSACTIONS.".user_id", "=", $AuthUser->get("id"))
            ->where(TABLE_PREFIX.TABLE_TRANSACTIONS.".type", "=", $type)
            ->where(DB::raw('YEAR('.TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate".') ='.date("Y")))
            ->groupBy(array( TABLE_PREFIX.TABLE_TRANSACTIONS.".type",  TABLE_PREFIX.TABLE_CATEGORIES.".name"));

            
            if($order && isset($order["column"]) && isset($order["dir"])){
                $sort =  in_array($order["dir"],["asc","desc"]) ? $order["dir"] : "desc";
                $column_name = trim($order["column"]) != "" ? 
                        trim($order["column"]) : "id";
                $column_name = str_replace(".", "_", $column_name);

                $query->orderBy($column_name, $sort);
            }

            $result = $query->get();
            $count = count($result);    
            $this->resp->summary = array(
                "total_count" => $count
            );

            $query->limit($length ? $length : 10)->offset($start ? $start : 0);
            $result = $query->get();

            foreach($result as $item){
                $data[] = array(
                    "jan" => (double)$item->{"jan"},
                    "feb" => (double)$item->{"feb"},
                    "mar" => (double)$item->{"mar"},
                    "apr" => (double)$item->{"apr"},
                    "may" => (double)$item->{"may"},
                    "jun" => (double)$item->{"jun"},
                    "jul" => (double)$item->{"jul"},
                    "aug" => (double)$item->{"aug"},
                    "sep" => (double)$item->{"sep"},
                    "oct" => (double)$item->{"oct"},
                    "nov" => (double)$item->{"nov"},
                    "dec" => (double)$item->{"dec"},
                    "category" => $item->{"category"},
                    "total" => (double) $item->total
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
     * @author Phong
     * lay cac giao dich theo tai khoan thuc hien
     * Step 1: khai bao bien cuc bo
     * Step 2: khai bao lop $resp de tra du lieu
     * Step 3: truy van kiem tra xem co bao nhieu giao dich theo $userID
     * Step 4: loc du lieu neu co cac tham so truyen den
     * Step 5: tra du lieu ve
     */
    private function getAccountTransactions()
    {
        /**Step 1 */
        $AuthUser = $this->getVariable("AuthUser");
        $Settings = $this->getVariable("Settings");

        $account  = Input::get("account");

        $fromdate = Input::get("fromdate");
        $todate   = Input::get("todate");

       
        $search     = Input::get("search");
        $order    = Input::get("order");

        $length   = Input::get("length") ? (int)Input::get("length") : 10;
        $start    = Input::get("start") ? (int)Input::get("start") : 0;



        /**Step 2 */
        $resp = new stdClass();
        $resp->result = 0;
        $data = [];

        if($fromdate && !isValidDate($fromdate)){
            $this->resp->msg = __("FromDate is invalid!");
            $this->jsonecho();
        }

        if($todate && !isValidDate($todate)){
            $this->resp->msg = __("ToDate is invalid!");
            $this->jsonecho();
        }

        try 
        {
            $subIncome = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
                            ->select([
                                "id", 
                                DB::raw('SUM(amount) as amount')
                            ])
                            ->where('type', '=', 1)
                            ->where("user_id", "=", $AuthUser->get("id"))
                            ->groupBy("id");

            $subExpense = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
                            ->select([
                                "id",
                                DB::raw('SUM(amount) as amount')
                            ])
                            ->where('type', '=', 2)
                            ->where("user_id", "=", $AuthUser->get("id"))
                            ->groupBy("id");
                
            $query = DB::table([TABLE_PREFIX.TABLE_TRANSACTIONS => 'p'])
                        ->where("p.user_id", "=", $AuthUser->get("id"))
                        ->leftJoin(
                            DB::subQuery($subIncome, 'a'), 
                            function($table){
                                $table->on("a.id", "=", "p.id");
                            }
                        )
                        ->leftJoin(
                            DB::subQuery($subExpense, 'b'), 
                            function($table){
                                $table->on("b.id", "=", "p.id");
                            }
                        )
                        ->leftJoin( TABLE_PREFIX.TABLE_CATEGORIES,
                                TABLE_PREFIX.TABLE_CATEGORIES.".id",
                                    "=",
                                "p.category_id")
                        ->select([
                            DB::raw("p.*"),
                            DB::raw(TABLE_PREFIX.TABLE_CATEGORIES.".name as cat_name"),
                            DB::raw("IFNULL(a.amount,'-') as income, IFNULL(b.amount,'-') as expense")
                        ]);

            /**Step 5*/
            $search_query = trim((string)$search);
            if( $search_query )
            {
                $query->where(function($q) use($search_query) {
                    $q->where("p.name", 'LIKE', $search_query.'%');
                });
            }
            
            if( $fromdate )
            {   
                $query->where("p.transactiondate", ">=", $fromdate);
            }

            if( $account )
            {   
                $query->where("p.account_id", "=", $account);
            }

            if( $todate )
            {
                $query->where("p.transactiondate", "<=", $todate);
            }
            if( $fromdate && $todate )
            {
                $query->whereBetween("p.transactiondate", $fromdate, $todate);
            }

            if($order && count($order) > 0){
                $sort =  in_array($order[0]["dir"],["asc","desc"]) ? $order[0]["dir"] : "desc";
                switch($order[0]["column"]){
                    case 0:
                        $query->orderBy("name", $sort);
                        break;
                    case 1:
                        $query->orderBy("cat_name", $sort);
                        break;
                    case 2:
                        $query->orderBy("reference", $sort);
                        break;
                    case 3:
                        $query->orderBy("description", $sort);
                        break;
                    case 4:
                        $query->orderBy("transactiondate", $sort);
                        break;
                    case 5:
                        $query->orderBy(DB::raw("income * 1"), $sort);
                        break;
                    case 6:
                        $query->orderBy(DB::raw("expense * 1"), $sort);
                        break;
                }
            }
             
            $query->limit($length ? $length : 10)->offset($start ? $start : 0);    
            $res = $query->get();

            foreach($res as $r)
            {
                $type = $r->type;

                $data[] = array(
                    "name" => $r->name,
                    "category" => $r->cat_name,
                    "reference" => $r->reference,
                    "description" => $r->description,
                    "transactiondate" => $r->transactiondate,
                    "income" => $r->income == "-" ? "-" : "<p class=\"text-green netincome\"><b>".$Settings->get("data.currency").$r->income."</b></p>",
                    "expense" =>$r->expense == "-" ? "-" : "<p class=\"text-red netexpense\"><b>".$Settings->get("data.currency").$r->expense."</b></p>",
                );
            }

            $count = count($data);
            $resp->recordsFiltered = $count;
            $resp->recordsTotal = $count;
            $resp->result = 1;
            $resp->data =$data;
            $this->resp = $resp;
        } 
        catch (\Exception $ex) 
        {
            $this->resp->result = 1;
            $this->resp->msg = $ex->getMessage();
        }
        $this->jsonecho();
    }
}
?>