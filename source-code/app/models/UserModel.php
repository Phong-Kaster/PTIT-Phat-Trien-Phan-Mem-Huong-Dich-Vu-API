<?php 
	/**
	 * User Model
	 *
	 * @version 1.0
	 * @author Onelab <hello@onelab.co> 
	 * 
	 */
	
	class UserModel extends DataEntry
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
	    	} else if (filter_var($uniqid, FILTER_VALIDATE_EMAIL)) {
	    		$col = "email";
	    	} else {
					$col = "id";
				}

	    	if ($col) {
		    	$query = DB::table(TABLE_PREFIX.TABLE_USERS)
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
	    		"account_type" => "member",
	    		"email" => uniqid()."@gmail.com",
	    		"password" => uniqid(),
	    		"firstname" => "",
	    		"lastname" => "",
	    		"secret" => "",
	    		"avatar" => "6273a92488d5b.png",
	    		"is_active" => "0",
	    		"language" => "en-US",
	    		"date" => date("Y-m-d H:i:s"),
	    		"data" => '{}',
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

	    	$id = DB::table(TABLE_PREFIX.TABLE_USERS)
		    	->insert(array(
		    		"id" => null,
		    		"account_type" => $this->get("account_type"),
		    		"email" => $this->get("email"),
		    		"password" => $this->get("password"),
		    		"firstname" => $this->get("firstname"),
		    		"lastname" => $this->get("lastname"),
		    		"secret" => $this->get("secret"),
		    		"avatar" => $this->get("avatar"),
		    		"is_active" => $this->get("is_active"),
		    		"language" => $this->get("language"),
		    		"date" => $this->get("date"),
		    		"data" => $this->get("data"),
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

	    	$id = DB::table(TABLE_PREFIX.TABLE_USERS)
	    		->where("id", "=", $this->get("id"))
		    	->update(array(
		    		"account_type" => $this->get("account_type"),
		    		"email" => $this->get("email"),
		    		"password" => $this->get("password"),
		    		"firstname" => $this->get("firstname"),
		    		"lastname" => $this->get("lastname"),
		    		"secret" => $this->get("secret"),
		    		"avatar" => $this->get("avatar"),
		    		"is_active" => $this->get("is_active"),
		    		"language" => $this->get("language"),
		    		"date" => $this->get("date"),
		    		"data" => $this->get("data"),
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

	    	DB::table(TABLE_PREFIX.TABLE_USERS)->where("id", "=", $this->get("id"))->delete();
	    	$this->is_available = false;
	    	return true;
	    }


	    /**
	     * Check if account has administrative privilages
	     * @return boolean 
	     */
	    public function isAdmin()
	    {
	    	if ($this->isAvailable() && in_array($this->get("account_type"), array("developer", "admin"))) {
	    		return true;
	    	}

	    	return false;
	    }


	    /**
	     * Checks if this user can edit another user's data
	     * 
	     * @param  UserModel $User Another user
	     * @return boolean          
	     */
	    public function canEdit(UserModel $User)
	    {	
	    	if ($this->isAvailable()) {
	    		if ($this->get("account_type") == "developer" || $this->get("id") == $User->get("id")) {
    				return true;
    			}	

    			if (
    				$this->get("account_type") == "admin" && 
    				(
	    				in_array($User->get("account_type"), array("member", "admin")) ||
	    				!$User->isAvailable() // New User
    				)
    			) {
    				return true;    				
    			}
	    	}

	    	return false;
	    }
	}
?>