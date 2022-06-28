<?php 
	/**
	 * Notification Model
	 *
	 * @version 1.0
	 * @author Onelab <hello@onelab.co> 
	 * 
	 */
	
	class NotificationModel extends DataEntry
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
	    		$col = null;
	    	}

	    	if ($col) {
		    	$query = DB::table(TABLE_PREFIX.TABLE_NOTIFICATIONS)
			    	      ->where(TABLE_PREFIX.TABLE_NOTIFICATIONS.".".$col, "=", $uniqid)
			    	      ->limit(1)
			    	      ->select([
										TABLE_PREFIX.TABLE_NOTIFICATIONS.".*",
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
	    		"title" => "",
	    		"content" => "",
	    		"is_read" => 0,
	    		"created_at" => date("Y-m-d H:i:s"),
	    		"updated_at" => date("Y-m-d H:i:s"),
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

	    	$id = DB::table(TABLE_PREFIX.TABLE_NOTIFICATIONS)
		    	->insert(array(
		    		"id" => null,
						"user_id" => $this->get("user_id"),
						"title" => $this->get("title"),
		    		"content" => $this->get("content"),
		    		"is_read" => $this->get("is_read"),
		    		"created_at" => $this->get("created_at"),
		    		"updated_at" => $this->get("updated_at")
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

	    	$id = DB::table(TABLE_PREFIX.TABLE_NOTIFICATIONS)
	    		->where("id", "=", $this->get("id"))
		    	->update(array(
		    		"user_id" => $this->get("user_id"),
		    		"title" => $this->get("title"),
		    		"content" => $this->get("content"),
		    		"is_read" => $this->get("is_read"),
		    		"created_at" => $this->get("created_at"),
		    		"updated_at" => $this->get("updated_at")
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

	    	DB::table(TABLE_PREFIX.TABLE_NOTIFICATIONS)->where("id", "=", $this->get("id"))->delete();
	    	$this->is_available = false;
	    	return true;
	    }
	}
?>