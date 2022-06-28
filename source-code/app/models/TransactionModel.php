<?php 
    /**
     * Transaction Model
     */
    class TransactionModel extends DataEntry
    {
        public function __construct($uniqid=0)
        {
            parent::__construct();
            $this->select($uniqid);
        }


        /*********************************************
	     * Select entry with unique id
	     * @param  int|string $unique id Value of the any unique field
	     * @return self       
	     *********************************************/
        public function select($uniqid)
	    {
	    	if (is_int($uniqid) || ctype_digit($uniqid)) 
            {
	    		$col = $uniqid > 0 ? "id" : null;
	    	} 
            else 
            {
	    		$col = null;
	    	}

	    	if ($col) 
            {
		    	$query = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
			    	      ->where($col, "=", $uniqid)
			    	      ->limit(1)
			    	      ->select("*");
		    	if ($query->count() == 1) 
                {
		    		$resp = $query->get();
		    		$r = $resp[0];

		    		foreach ($r as $field => $value)
		    			$this->set($field, $value);

		    		$this->is_available = true;
		    	}
                else 
                {
		    		$this->data = array();
		    		$this->is_available = false;
		    	}
	    	}

	    	return $this;
	    }



        /*********************************************
         * set default value if any field has no input data
         * @return self
         *********************************************/
        public function extendDefaults()
        {
            $defaults = array(
                "user_id" => "",
                "category_id" => "",
                "account_id" => "",
                "name" => "",
                "amount" => 0,
                "reference" => "",
                "transactiondate" => date("Y-m-d H:i:s"),
                "type" => "",
                "description" => "",
            );



            foreach( $defaults as $field => $value)
            {
                if( is_null( $this->get($field) ) )
                {
                    $this->set($field, $value);
                }
            }
        }


        /***************************************
         * @author Phong-Kaster
         * $this->resp->result = 0 means fail
         * $this->resp->result = 1 means successful
         ***************************************/
        public function insert()
        {
            if( $this->isAvailable() )
            {
                return false;
            }

            $this->extendDefaults();

            $id = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
                ->insert( array(
                    "id"        => null,
                    "user_id"   => $this->get("user_id"),
                    "category_id"=> $this->get("category_id"),
                    "account_id" => $this->get("account_id"),
                    "name"       => $this->get("name"),
                    "amount"     => $this->get("amount"),
                    "reference"  => $this->get("reference"),
                    "transactiondate" => $this->get("transactiondate"),
                    "type"       => $this->get("type"),
                    "description" => $this->get("description"),
                ) );
            
            $this->set("id", $id);
            $this->markAsAvailable();
            return $this->get("id");
        }



        /***************************************
         * @author Phong-Kaster
         * update selected record with data
         ***************************************/
	    public function update()
	    {
	    	if (!$this->isAvailable())
	    		return false;

	    	$this->extendDefaults();

	    	$id = DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)
	    		->where("id", "=", $this->get("id"))
		    	->update(array(
                    "user_id"   => $this->get("user_id"),
                    "category_id"=> $this->get("category_id"),
                    "account_id" => $this->get("account_id"),
                    "name"       => $this->get("name"),
                    "amount"     => $this->get("amount"),
                    "reference"  => $this->get("reference"),
                    "transactiondate" => $this->get("transactiondate"),
                    "type"       => $this->get("type"),
                    "description" => $this->get("description"),
		    	));

	    	return $this;
	    }


        /**
		 * Remove selected entry from database
		 */
	    public function delete()
	    {
	    	if(!$this->isAvailable())
	    		return false;

	    	DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS)->where("id", "=", $this->get("id"))->delete();
	    	$this->is_available = false;
	    	return true;
	    }
    }
?>