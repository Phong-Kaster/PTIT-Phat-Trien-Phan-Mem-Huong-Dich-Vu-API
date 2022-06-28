<?php
/**
 * Account Controller
 */
class AccountController extends Controller
{
    /**
     * Process
     */
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

        $Account = Controller::model("Account", $Route->params->id);
        if (!$Account->isAvailable() || $Account->get("user_id") != $AuthUser->get("id")) {
            $this->resp->msg = __("Account doesn't exist!");
            $this->jsonecho();
        }

        $this->setVariable("Settings", Controller::model("GeneralData", "settings"))
                ->setVariable("Account", $Account);

        $request_method = Input::method();
        if($request_method === 'PUT'){
            $this->save();
        }else if($request_method === 'GET'){
            $this->getById();
        }else if($request_method === "DELETE"){
            $this->remove();
        }
    }


    /**
     * Save edit account
     * @return void 
     */
    private function save()
    {
        $this->resp->result = 0;
        $AuthUser = $this->getVariable("AuthUser");
        $Account = $this->getVariable("Account");
        $Settings = $this->getVariable("Settings");

        $required_fields = ["name", "balance", "accountnumber"];
        foreach ($required_fields as $field) {
            if (!Input::put($field)) {
                $this->resp->msg = __("Missing some of required data.");
                $this->jsonecho();
            }
        }
      

        $balance = (double)Input::put("balance");
        if ($balance < 0) {
            $balance = 0;
        }

        if (isZeroDecimalCurrency($Settings->get("data.currency"))) {
            $balance = round($balance);
        }

        try {
            $Account->set("name", Input::put("name"))
                ->set("balance", $balance)
                ->set("accountnumber", Input::put("accountnumber"))
                ->set("description", Input::put("description"))
                ->set("updated_at", date("Y-m-d H:i:s"))
                ->save();
        } catch (Exception $ex) {
            $this->resp->msg = $ex->getMessage();
            $this->jsonecho();
        }


        $this->resp->result = 1;
        $this->resp->account = (int)$Account->get("id");


        $this->resp->msg = __("Changes saved!");
        $this->jsonecho();
    }

    /**
     * get account by id
     * @return void 
     */
    private function getById()
    {
        
        $this->resp->result = 0;
        $AuthUser = $this->getVariable("AuthUser");
        $Account = $this->getVariable("Account");

        $this->resp->result = 1;
        $this->resp->data = array(
            "id" => (int) $Account->get("id"),
            "balance" => strval($Account->get("balance")),
            "name" => $Account->get("name"),
            "description" => $Account->get("description"),
            "accountnumber" => $Account->get("accountnumber"),
            "updated_at" => $Account->get("updated_at")
        );
        $this->jsonecho();
        
        
    }


    /**
     * Remove account
     * @return void 
     */
    private function remove()
    {
        $this->resp->result = 0;

        $AuthUser = $this->getVariable("AuthUser");
        $Account = $this->getVariable("Account");

        try {
             // check transactions
            $data = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
                ->where("user_id", "=", $AuthUser->get("id"))
                ->where("account_id", "=", $Account->get("id"))
                ->select("id")
                ->get();

            if(count($data) > 0){
                $this->resp->msg = __("This account contains transactions data so that it cannot be deleted!");
                $this->jsonecho();
            }

            $Account->delete();
        } catch (\Exception $ex) {
            $this->resp->msg = $ex->getMessage();
            $this->jsonecho();
        }

        $this->resp->result = 1;
        $this->resp->account = (int)$Account->get("id");

        $this->resp->msg = __("Account and transaction related to this account has been deleted successfully");
        $this->jsonecho();
    }
}