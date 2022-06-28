<?php 
    class TransactionsModel extends DataList
    {
        /**
         * Initialize
         */
        public function __construct()
        {
            $this->setQuery(DB::table(TABLE_PREFIX.TABLE_TRANSACTIONS));
        }
    }
?>