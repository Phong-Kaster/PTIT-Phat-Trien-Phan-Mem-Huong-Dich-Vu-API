<?php 
    /**
     * TransactionsStatistics Controller
     */
    class TransactionsStatisticsController extends Controller
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
            
            $page = isset($Route->params->page) ? $Route->params->page : "income";
            $action = isset($Route->params->action) ? $Route->params->action : "";

            $type = $page == "income" ? 1 : 2;

            if($action == "gettotal"){
              $this->getTotal($type);
            }
        }

        private function getTotal($type)
        {
            /**Step 1 */
            $AuthUser = $this->getVariable("AuthUser");
            /**Step 2 */
            $this->resp->result = 0;


            try 
            {
                /**Step 3 */

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

                // query to get sum(amount) overall
                $totalbalance = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
                                ->select(DB::raw('sum(amount) as totalbalance'))
                                ->where('type', '=', $type)
                                ->where('user_id', '=', $AuthUser->get("id"))
                                ->get();
                
                $year   = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
                            ->select(DB::raw('sum(amount) as totalyear'))
                            ->where('type', '=', $type)
                            ->where('user_id', '=', $AuthUser->get("id"))
                            ->where(DB::raw("YEAR(transactiondate) = ".date("Y")))
                            ->get();
        
                $month   = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
                            ->select(DB::raw('sum(amount) as totalmonth'))
                            ->where('type', '=', $type)
                            ->where('user_id', '=', $AuthUser->get("id"))
                            ->where(DB::raw("MONTH(".TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate) = ".date("m")))
                            ->where(DB::raw("YEAR(transactiondate) = ".date("Y")))
                            ->get();
        
                $week 	= DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
                            ->select(DB::raw('sum(amount) as totalweek'))
                            ->where('type', '=', $type)
                            ->where('user_id', '=', $AuthUser->get("id"))
                            ->whereBetween(TABLE_PREFIX.TABLE_TRANSACTIONS.".transactiondate", $fromdate->format("Y-m-d"), $todate->format("Y-m-d"))
                            ->get();
        
                $day   = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
                            ->select(DB::raw('sum(amount) as totalday'))
                            ->where('type', '=', $type)
                            ->where('user_id', '=', $AuthUser->get("id"))
                            ->where('transactiondate', "=", date('Y-m-d'))
                            ->get();

                /**Step 4 */

                $data["totalbalance"] = ($totalbalance && count($totalbalance) > 0) ? (double) $totalbalance[0]->totalbalance : 0;
                $data["month"] = ($month && count($month) > 0) ? (double) $month[0]->totalmonth : 0;
                $data["week"] = ($week && count($week) > 0) ? (double) $week[0]->totalweek : 0;
                $data["day"] = ($day && count($day) > 0) ? (double) $day[0]->totalday : 0;
                $data["year"] = ($year && count($year) > 0) ? (double) $year[0]->totalyear : 0;


                /**Step 5 */
                $this->resp->result = 1;
                $this->resp->data = $data;
            } 
            catch (\Exception $ex) 
            {
                $this->resp->msg = $ex->getMessage();
            }
            $this->jsonecho();
        }
    }
?>