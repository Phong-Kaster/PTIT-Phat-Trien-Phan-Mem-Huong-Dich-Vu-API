<?php 
    /**
     * BudgetsStatistics Controller
     */
    class BudgetsStatisticsController extends Controller
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
            
            $action = isset($Route->params->action) ? $Route->params->action : "";
            if($action == "gettransactionbydate"){
              $this->gettransactionbydate();
            }
        }

        private function gettransactionbydate(){
            $this->resp->result = 0;
            $AuthUser = $this->getVariable("AuthUser");

            $required_fields = [ "date","category_id"];
            foreach( $required_fields as $field)
            {
                if( !Input::get($field) )
                {
                    $this->resp->msg = __("Missing some required field !");
                    $this->jsonecho();
                }
            }


            $date = Input::get("date");
            $category_id = Input::get("category_id");

            if(!isValidDate($date, 'Y-m')){
                $this->resp->msg = __("Date is invalid !");
                $this->jsonecho();
            }

            $date = new \Moment\Moment($date."-01", date_default_timezone_get());

            try 
            {
                $query = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
                    ->where(TABLE_PREFIX.TABLE_TRANSACTIONS.".user_id", "=", $AuthUser->get("id") )
                    ->where(TABLE_PREFIX.TABLE_TRANSACTIONS.".category_id", "=", $category_id)
                    ->whereBetween(TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate", $date->startOf("month")->format("Y-m-d"), $date->endOf("month")->format("Y-m-d"))
                    ->select([
                        DB::raw("SUM(".TABLE_PREFIX.TABLE_TRANSACTIONS.'.amount) as totalamount')
                    ]);

                $res = $query->get();
                
                $this->resp->result = 1;
                $totalamount = count($res) > 0 ? (double)$res[0]->totalamount : 0;
                $this->resp->totalamount = $totalamount;
            } 
            catch (Exception $ex) {
                $this->resp->msg = __("Oops! Something went wrong. Please try again later!!");
            }
            $this->jsonecho();
        }
    }
?>