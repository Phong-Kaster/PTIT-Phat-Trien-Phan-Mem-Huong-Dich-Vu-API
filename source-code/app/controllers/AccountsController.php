<?php
/**
 * Accounts Controller
 */
class AccountsController extends Controller
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
        $this->setVariable("Settings", Controller::model("GeneralData", "settings"));

        $request_method = Input::method();
        if($request_method === 'POST'){
            $this->save();
        }else if($request_method === 'GET'){
            $this->getAll();
        }
    }


    

    /**
     * All account current User
     * @return array list 
     */
    private function getAll(){
        $this->resp->result = 0;
        $AuthUser = $this->getVariable("AuthUser");

        $order = Input::get("order");
        $search = Input::get("search");

        $length   = Input::get("length");
        $start    = Input::get("start");

        $draw = (int)Input::get("draw");

        if($draw){
            $this->resp->draw = $draw; 
        }
           
        $data = [];
        
        try {
            // Get accounts
            $query = DB::table(TABLE_PREFIX.TABLE_ACCOUNTS)
                    ->where("user_id", "=", $AuthUser->get("id"));

            $search_query = trim((string)$search);
            if($search_query){
                $query->where(function($q) use($search_query)
                    {
                        $q->where(TABLE_PREFIX.TABLE_ACCOUNTS.".name", 'LIKE', $search_query.'%')
                        ->orWhere(TABLE_PREFIX.TABLE_ACCOUNTS.".balance", 'LIKE', $search_query.'%')
                        ->orWhere(TABLE_PREFIX.TABLE_ACCOUNTS.".description", 'LIKE', $search_query.'%');
                    });     
            }
    
            if($order && isset($order["column"]) && isset($order["dir"]) ){
                $sort =  in_array($order["dir"],["asc","desc"]) ? $order["dir"] : "desc";
                $column_name = trim($order["column"]) != "" ? trim($order["column"]) : "id";
                if(in_array($column_name, ["balance"])){
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

            $query->limit($length ? $length : 10)->offset($start ? $start : 0);
            $res = $query->get();     

            foreach($res as $a){
                $id = (int)$a->id;
                $data[] = array(
                    "id" => $id,
                    "name" => $a->name,
                    "description" => $a->description,
                    "balance" => (double)$a->balance,
                    "accountnumber" => $a->accountnumber,
                );
            }
                $this->resp->data = $data;
            $this->resp->result = 1;
        } catch (\Exception $ex) {
            $this->resp->msg = $ex->getMessage();
        }
        $this->jsonecho();
    }

    /**
     * Save new account
     * @return void
     */
    private function save(){
        $this->resp->result = 0;
        $AuthUser = $this->getVariable("AuthUser");
        $Settings = $this->getVariable("Settings");
        
        $required_fields = ["name", "balance", "accountnumber"];
        foreach ($required_fields as $field) {
            if (!Input::post($field)) {
                $this->resp->msg = __("Missing some of required data.");
                $this->jsonecho();
            }
        }

        $balance = (double)Input::post("balance");
        if ($balance < 0) {
            $balance = 0;
        }

        if (isZeroDecimalCurrency($Settings->get("data.currency"))) {
            $balance = round($balance);
        }

        $data = DB::table(TABLE_PREFIX.TABLE_ACCOUNTS)
                ->where("user_id", "=", $AuthUser->get("id"))
                ->where("accountnumber", "=", Input::post("accountnumber"))
                ->select("id")
                ->get();

        if(count($data) > 0){
            $this->resp->msg = __("Account number is exist!");
            $this->jsonecho();
        }

       try {
            $Account = Controller::model("Account");
            $Account->set("name", Input::post("name"))
                    ->set("balance", $balance)
                    ->set("accountnumber", Input::post("accountnumber"))
                    ->set("description", Input::post("description"))
                    ->set("user_id", $AuthUser->get("id"))
                    ->set("updated_at", date("Y-m-d H:i:s"))
                    ->set("created_at", date("Y-m-d H:i:s"))
                    ->save();

       } catch (Exception $ex) {
            $this->resp->msg = $ex->getMessage();
            $this->jsonecho();
       }

        $this->resp->result = 1;
        $this->resp->account = (int)$Account->get("id");

        $this->resp->msg = __("Account added successfully! Please refresh the page.");
        $this->jsonecho();
    }
}