<?php
/**
 * Category Controller
 */
class CategoryController extends Controller
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

        $Route = $this->getVariable("Route");

        if (!isset($Route->params->id)) {
            $this->resp->msg = __("ID is required!");
            $this->jsonecho();
        }

        $Category = Controller::model("Category", $Route->params->id);
        if (!$Category->isAvailable()) {
            $this->resp->msg = __("Category doesn't exist!");
            $this->jsonecho();
        }

        $this->setVariable("Category", $Category);

        $request_method = Input::method();
        if($request_method === 'PUT'){
            $this->save();
        }else if($request_method === 'GET'){
            $this->getById();
        }else if($request_method === 'DELETE'){
            $this->remove();
        }
    }


    /**
     * Save edit category
     * @return void 
     */
    private function save()
    {
        $this->resp->result = 0;
        $AuthUser = $this->getVariable("AuthUser");
        $Category = $this->getVariable("Category");
        

        $required_fields = ["name", "color"];
        foreach ($required_fields as $field) {
            if (!Input::put($field)) {
                $this->resp->msg = __("Missing some of required data.");
                $this->jsonecho();
            }
        }

        //check color is invalid
        $color = str_replace("#", "", Input::put("color"));
        if(!check_valid_colorhex($color)){
            $this->resp->msg = __("Color is not invalid.");
            $this->jsonecho();
        }

        $Category->set("name", Input::put("name"))
                 ->set("color", $color)
                 ->set("description", Input::put("description"))
                 ->set("updated_at", date("Y-m-d H:i:s"))
                 ->save();


        $this->resp->result = 1;
        $this->resp->category = (int) $Category->get("id");
        $this->resp->msg = __("Catergory has been updated successfully!");
        $this->jsonecho();
    }

    /**
     * get category by id
     * @return void 
     */
    private function getById()
    {
        
        $this->resp->result = 0;
        $AuthUser = $this->getVariable("AuthUser");
        $Category = $this->getVariable("Category");

        $this->resp->result = 1;
        $this->resp->data = array(
            "id" => (int) $Category->get("id"),
            "type" => (int) $Category->get("type"),
            "name" => $Category->get("name"),
            "description" => $Category->get("description"),
            "color" => "#".$Category->get("color")
        );
        $this->jsonecho();
        
        
    }


    /**
     * Remove Category
     * @return void 
     */
    private function remove()
    {
        /**Step 1 */
        $this->resp->result = 0;        
        $AuthUser = $this->getVariable("AuthUser");
        $Category = $this->getVariable("Category");

        /**Step 4 */
        // check transactions
        $Transactions = Controller::model("Transactions");
        $Transactions->where("category_id", "=", $Category->get("id"))
                    ->where("user_id", "=", $AuthUser->get("id"))
                    ->fetchData();

        if( $Transactions->getTotalCount() > 0 ){
            $this->resp->msg = __("There are transactions with this category ID!");
            $this->jsonecho();
        }


        /**Step 5 */
        // check budgets
        $Budgets = Controller::model("Budgets");
        $Budgets->where("category_id","=", $Category->get("id") )
                ->where("user_id", "=", $AuthUser->get("id"))
                ->fetchData();

        if( $Budgets->getTotalCount() > 0 )
        {
            $this->resp->msg = __("There are budgets with this category ID!");
            $this->jsonecho();
        }

        $Category->delete();

        /**Step 6 */
        $this->resp->result = 1;
        $this->resp->category = (int) $Category->get("id");
        $this->resp->msg = __("Category has been deleted successfully");
        $this->jsonecho();
    }
}