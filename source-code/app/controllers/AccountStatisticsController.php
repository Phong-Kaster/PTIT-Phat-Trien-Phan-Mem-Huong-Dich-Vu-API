<?php 
/**
 * AccountStatistics Controller
 */
class AccountStatisticsController extends Controller{
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
        
        $action = isset($Route->params->action) ? $Route->params->action : "";
        $Account = Controller::model("Account");
        if(isset($Route->params->id)){
          $Account->select($Route->params->id);
        }

        if(!$Account->isAvailable() || $Account->get("user_id") != $AuthUser->get("id")){
          $this->resp->result = 0;
          $this->resp->msg = __("Account doesn't exist.");
          $this->jsonecho();
        }

        $this->setVariable("Settings", Controller::model("GeneralData", "settings"))
            ->setVariable("Account", $Account);

        if($action == "accountbalancebymonth"){
          $this->accountbalancebymonth();
        }else if($action == "getaccounttransaction"){
          $this->getaccounttransaction();
        }
    }

    private function accountbalancebymonth(){
      $this->resp->result = 0;
      $AuthUser = $this->getVariable("AuthUser");
      $Account = $this->getVariable("Account");
      
      $year = date('Y');

      $res = array(
        "jan" => 0,
        "feb" => 0,
        "mar" => 0,
        "apr" => 0,
        "may" => 0,
        "jun" => 0,
        "jul" => 0,
        "aug" => 0,
        "sep" => 0,
        "oct" => 0,
        "nov" => 0,
        "dec" => 0,
      );

      $month = 1;
      foreach ($res as $key => $value) {
        try {
          $query = $this->accountbalancebymonthQuery($Account->get("id"), $month, $year, $AuthUser->get("id"));
          $res[$key] = (double) $query[0]->balance;
          $month++;
        } catch (Exception $ex) {
          $this->resp->msg = __("Oops! Something went wrong. Please try again later!!");
          $this->jsonecho();
        }
      }
      $this->resp->result = 1;
      $this->resp->data = $res;
      $this->jsonecho();
    }

    private function accountbalancebymonthQuery($accountid, $month, $year, $user_id){
      $subIncome = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
                  ->select([
                      "account_id", 
                      DB::raw('SUM(amount) as amount')
                  ])
                  ->where('type', '=', 1)
                  ->where("user_id", "=", $user_id)
                  ->where(DB::raw("MONTH(transactiondate) = ".$month))
                  ->where(DB::raw("YEAR(transactiondate) = ".$year))
                  ->groupBy("account_id");

        $subExpense = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
                    ->select([
                    "account_id", 
                    DB::raw('SUM(amount) as amount')
                    ])
                    ->where('type', '=', 2)
                    ->where("user_id", "=", $user_id)
                    ->where(DB::raw("MONTH(transactiondate) = ".$month))
                    ->where(DB::raw("YEAR(transactiondate) = ".$year))
                    ->groupBy("account_id");

        $data = DB::table([TABLE_PREFIX.TABLE_ACCOUNTS => 'p'])
              ->where("p.user_id", "=", $user_id)
              ->where("p.id", "=", $accountid)
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
                DB::raw("COALESCE((COALESCE(a.amount, 0) - COALESCE(b.amount, 0)), 0) as balance"),
              ])
              ->groupBy("p.id")
              ->get();
        return $data;
    }

    private function getaccounttransaction(){
      $this->resp->result = 0;
      $AuthUser = $this->getVariable("AuthUser");
      $Settings = $this->getVariable("Settings");
      $Account = $this->getVariable("Account");
      

      $order = Input::get("order");
      $draw = (int)Input::get("draw");

      $length   = Input::get("length") ? (int)Input::get("length") : 10;
      $start    = Input::get("start") ? (int)Input::get("start") : 0;

      if($draw){
          $this->resp->draw = $draw; 
      }
      $data = [];

      try {
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
                    ->where("p.account_id", "=", $Account->get("id"))
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
                        DB::raw(TABLE_PREFIX.TABLE_CATEGORIES.".name as category_name"),
                        DB::raw("IFNULL(a.amount,'-') as income, IFNULL(b.amount,'-') as expense")
                    ]);
        
    
        if($order && isset($order["column"]) && isset($order["dir"])){
          $type = $order["dir"];
          $validType = ["asc","desc"];
          $sort =  in_array($type, $validType) ? $type : "desc";

          $column_name = trim($order["column"]) != "" ? trim($order["column"]) : "id";
          $column_name = str_replace(".", "_", $column_name);


          if(in_array($column_name, ["income", "expense"])){
            $query->orderBy(DB::raw($column_name. " * 1"), $sort);
          }else{
            $query->orderBy($column_name, $sort);
          }
        }

        $res = $query->get();
        $count = count($res);
        
        $query->limit($length)->offset($start);
        $res = $query->get();

        foreach($res as $r)
        {
            $type = $r->type;
            $data[] = array(
                "name" => $r->name,
                "category" => array(
                  "name" => $r->category_name,
                ),
                "reference" => $r->reference,
                "description" => $r->description,
                "transactiondate" => $r->transactiondate,
                "income" => (double)$r->income,
                "expense" => (double)$r->expense,
            );
        }

        $this->resp->result = 1;
        $this->resp->summary = array(
            "total_count" => $count
        );
        $this->resp->data = $data;

      } catch (\Exception $ex) {
          $this->resp->msg = $ex->getMessage();
      }
      $this->jsonecho();
    }
}
?>