<?php 
	/**
	 * Budgets Model
	 *
	 * @version 1.0
	 * @author Onelab <hello@onelab.co> 
	 * 
	 */
	
	class GoalModel extends DataEntry
	{	
		/**
		 * Extend parents constructor and select entry
		 * @param mixed $uniqid Value of the unique identifier
		 */
	    public function __construct($uniqid=0)
	    {
	        parent::__construct();
	        $this->select($uniqid);
	    }



	    /**
	     * Select entry with uniqid
	     * @param  int|string $uniqid Value of the any unique field
	     * @return self       
	     */
	    public function select($uniqid)
	    {
	    	if (is_int($uniqid) || ctype_digit($uniqid)) {
	    		$col = $uniqid > 0 ? "id" : null;
	    	} else {
	    		$col = "accountnumber";
	    	}

	    	if ($col) {
		    	$query = DB::table(TABLE_PREFIX.TABLE_GOALS)
			    	      ->where($col, "=", $uniqid)
			    	      ->limit(1)
			    	      ->select("*");
		    	if ($query->count() == 1) {
		    		$resp = $query->get();
		    		$r = $resp[0];

		    		foreach ($r as $field => $value)
		    			$this->set($field, $value);

		    		$this->is_available = true;
		    	} else {
		    		$this->data = array();
		    		$this->is_available = false;
		    	}
	    	}

	    	return $this;
	    }


	    /**
	     * Extend default values
	     * @return self
	     */
	    public function extendDefaults()
	    {
	    	$defaults = array(
	    		"user_id" => "",
	    		"name" => "",
	    		"balance" => 0,
	    		"amount" => 0,
					"deposit" => 0,
					"status" => 0,
          "deadline" => date("Y-m-d")
	    	);


	    	foreach ($defaults as $field => $value) {
	    		if (is_null($this->get($field)))
	    			$this->set($field, $value);
	    	}
	    }


	    /**
	     * Insert Data as new entry
	     */
	    public function insert()
	    {
	    	if ($this->isAvailable())
	    		return false;

	    	$this->extendDefaults();

	    	$id = DB::table(TABLE_PREFIX.TABLE_GOALS)
		    	->insert(array(
		    		"id" => null,
						"user_id" => $this->get("user_id"),
		    		"name" => $this->get("name"),
		    		"balance" => $this->get("balance"),
		    		"amount" => $this->get("amount"),
		    		"deposit" => $this->get("deposit"),
		    		"status" => $this->get("status"),
            "deadline" => $this->get("deadline")
		    	));

	    	$this->set("id", $id);
	    	$this->markAsAvailable();
	    	return $this->get("id");
	    }


	    /**
	     * Update selected entry with Data
	     */
	    public function update()
	    {
	    	if (!$this->isAvailable())
	    		return false;

	    	$this->extendDefaults();

	    	$id = DB::table(TABLE_PREFIX.TABLE_GOALS)
	    		->where("id", "=", $this->get("id"))
		    	->update(array(
						"user_id" => $this->get("user_id"),
		    		"name" => $this->get("name"),
		    		"balance" => $this->get("balance"),
		    		"amount" => $this->get("amount"),
		    		"deposit" => $this->get("deposit"),
		    		"status" => $this->get("status"),
            "deadline" => $this->get("deadline")
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

	    	DB::table(TABLE_PREFIX.TABLE_GOALS)->where("id", "=", $this->get("id"))->delete();
	    	$this->is_available = false;
	    	return true;
	    }
	}
?>