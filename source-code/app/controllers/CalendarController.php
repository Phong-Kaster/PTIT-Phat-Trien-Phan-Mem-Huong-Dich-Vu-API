<?php 
class CalendarController extends Controller
{
    public function process()
    {
        $Route = $this->getVariable("Route");
        $AuthUser = $this->getVariable("AuthUser");

        // Auth
        if (!$AuthUser){
            header("Location: ".APPURL."/login");
            exit;
        } 

        $page = isset($Route->params->page) ? $Route->params->page : "income";

        if($page == "income"){
          $this->getdatacalendar(1);
        }else if($page == "expense"){
          $this->getdatacalendar(2);
        }else if($page == "filterdate"){
          $this->filterdate();
        }
    }

    private function getdatacalendar($type)
    {
        /*Step 1*/
        $this->resp->result = 0;
        $AuthUser = $this->getVariable("AuthUser");

        /*Step 2*/
        $required_fields = ["start", "end"];
        foreach( $required_fields as $field)
        {
            if( !Input::get($field) || !isValidDate(Input::get($field), 'Y-m-d\TH:i:sP'))
            {
                $this->resp->msg = __("Missing some required field !");
                $this->jsonecho();
            }
        }

        try {
          $query = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
                    ->where("user_id", "=", $AuthUser->get("id"))
                    ->where("type", "=", $type)
                    ->select([
                      'name` as `title',
                      'transactiondate` as `start',
                      'amount'
                    ]);

          $result = $query->get();
          $this->resp = $result;
        } catch (\Exception $ex) {
          $this->resp->msg = $ex->getMessage();
        }
        $this->jsonecho();
    }



    private function filterdate()
    {

      /*Step 1*/
      $this->resp->result = 0;
      $AuthUser = $this->getVariable("AuthUser");

      /*Step 2*/
      $required_fields = ["date"];

      foreach( $required_fields as $field)
      {
          if( !Input::post($field) || !isValidDate(Input::post($field), 'Y-m-d\TH:i:s.000\Z'))
          {
              $this->resp->msg = __("Missing some required field !");
              $this->jsonecho();
          }
      }

      $date = Input::post("date");
      try {
        $monthincome   = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
                        ->select(DB::raw('sum(amount) as totalmonth'))
                        ->where(DB::raw("MONTH(transactiondate) = ".date('m', strtotime($date))))
                        ->where('type', '=', '1')
                        ->get();

        $monthexpense   = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
                        ->select(DB::raw('sum(amount) as totalmonth'))
                        ->where(DB::raw("MONTH(transactiondate) = ".date('m', strtotime($date))))
                        ->where('type', '=', '2')
                        ->get();

        
        $balance       = $monthincome[0]->totalmonth - $monthexpense[0]->totalmonth;

        $resp = [];
        $resp['monthname']      = date('F',strtotime($date));
        $resp['monthincome']    = number_format($monthincome[0]->totalmonth, 2);
        $resp['monthexpense']   = number_format($monthexpense[0]->totalmonth, 2);
        $resp['monthbalance']   = number_format($balance, 2);
        $resp['result'] = 1;
        $this->resp = $resp;
      } catch (\Exception $ex) {
        $this->resp->msg = $ex->getMessage();
      }
      $this->jsonecho();
    }
}
?>