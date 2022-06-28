<?php 
	/**
	 * Budgets Model
	 *
	 * @version 1.0
	 * @author Onelab <hello@onelab.co> 
	 * 
	 */
	
	class BudgetModel extends DataEntry
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
		    	$query = DB::table(TABLE_PREFIX.TABLE_BUDGETS)
										->join(TABLE_PREFIX.TABLE_CATEGORIES,
													TABLE_PREFIX.TABLE_CATEGORIES.".id",
													"=",
													TABLE_PREFIX.TABLE_BUDGETS.".category_id"
									)
									->leftJoin(
											TABLE_PREFIX.TABLE_USERS,
											TABLE_PREFIX.TABLE_BUDGETS.".user_id",
											"=",
											TABLE_PREFIX.TABLE_USERS.".id"
									)
			    	      ->where(TABLE_PREFIX.TABLE_BUDGETS.".".$col, "=", $uniqid)
			    	      ->limit(1)
			    	      ->select([
										TABLE_PREFIX.TABLE_BUDGETS.".*",
											DB::raw(TABLE_PREFIX.TABLE_CATEGORIES.".name as category_name" ),
											DB::raw(TABLE_PREFIX.TABLE_CATEGORIES.".id as category_id" ),
											DB::raw(TABLE_PREFIX.TABLE_CATEGORIES.".type as category_type"),
											DB::raw(TABLE_PREFIX.TABLE_CATEGORIES.".description as category_description"),
											DB::raw(TABLE_PREFIX.TABLE_CATEGORIES.".color as category_color"),
											DB::raw(TABLE_PREFIX.TABLE_USERS.".id as user_id" ),
									]);
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
	    		"category_id" => 1,
	    		"amount" => 0,
	    		"fromdate" => date("Y-m-d H:i:s"),
	    		"todate" => date("Y-m-d H:i:s"),
				"description" => ""
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

	    	$id = DB::table(TABLE_PREFIX.TABLE_BUDGETS)
		    	->insert(array(
		    		"id" => null,
						"user_id" => $this->get("user_id"),
						"category_id" => $this->get("category_id"),
		    		"amount" => $this->get("amount"),
		    		"fromdate" => $this->get("fromdate"),
		    		"todate" => $this->get("todate"),
		    		"description" => $this->get("description")
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

	    	$id = DB::table(TABLE_PREFIX.TABLE_BUDGETS)
	    		->where("id", "=", $this->get("id"))
		    	->update(array(
		    		"user_id" => $this->get("user_id"),
		    		"category_id" => $this->get("category_id"),
		    		"amount" => $this->get("amount"),
		    		"fromdate" => $this->get("fromdate"),
		    		"todate" => $this->get("todate"),
		    		"description" => $this->get("description")
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

	    	DB::table(TABLE_PREFIX.TABLE_BUDGETS)->where("id", "=", $this->get("id"))->delete();
	    	$this->is_available = false;
	    	return true;
	    }
	}
?>