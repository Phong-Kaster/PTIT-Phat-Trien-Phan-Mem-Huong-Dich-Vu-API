<?php
/**
 * Categories Controller
 */
class CategoriesController extends Controller
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

        $page = isset($Route->params->page) ? $Route->params->page : "incomecategories";
        $type = $page == "incomecategories" ? 1 : 2;

        $request_method = Input::method();
        if($request_method === 'POST'){
            $this->save($type);
        }else if($request_method === 'GET'){
            $this->getAll($type);
        }
    }


    

    /**
     * All category current User
     * @return array list 
     */
    private function getAll($type){
        
        $this->resp->result = 0;
        $AuthUser = $this->getVariable("AuthUser");

        $order = Input::get("order");
        $search = Input::get("search");
        $start = (int)Input::get("start");
        $draw = (int)Input::get("draw");
        $length = (int)Input::get("length");

        if($draw){
            $this->resp->draw = $draw; 
        }
           
        $data = [];
        
        try 
        {
            // Get categories
            $query = DB::table(TABLE_PREFIX.TABLE_CATEGORIES)
                    ->where("user_id", "=", $AuthUser->get("id"))
                    ->where("type", "=", $type);

            $search_query = trim((string)$search);
            if($search_query){
                $query->where(function($q) use($search_query)
                    {
                        $q->where(TABLE_PREFIX.TABLE_CATEGORIES.".name", 'LIKE', '%'.$search_query.'%')
                        ->orWhere(TABLE_PREFIX.TABLE_CATEGORIES.".description", 'LIKE', '%'.$search_query.'%');
                    });     
            }


            if($order && isset($order["column"]) && isset($order["dir"])){
                $sort =  in_array($order["dir"],["asc","desc"]) ? $order["dir"] : "desc";
                    $column_name = trim($order["column"]) != "" ? trim($order["column"]) : "id";
                    $query->orderBy($column_name, $sort);
            }  
            
            $res = $query->get();
            $count = count($res);
            $this->resp->summary = array(
                "total_count" => $count
            );

            $query->limit($length ? $length : 10)->offset($start ? $start : 0);
            $res = $query->get();
            
            foreach($res as $c){
                $data[] = array(
                    "id" =>  (int)$c->id,
                    "name" => $c->name,
                    "description" => $c->description,
                    "type" => (int)$c->type,
                    "color" => "#".strtoupper($c->color),
                );
            }
            $this->resp->data = $data;  
            $this->resp->result = 1;
        } 
        catch (\Exception $ex) {
            $this->resp->msg = $ex->getMessage();
        }

        $this->jsonecho();
    }

    /**
     * Save new category
     * @return void
     */
    private function save($type){
        $this->resp->result = 0;
        $AuthUser = $this->getVariable("AuthUser");
        
        
        $required_fields = ["name", "color"];
        foreach ($required_fields as $field) {
            if (!Input::post($field)) {
                $this->resp->msg = __("Missing some of required data.");
                $this->jsonecho();
            }
        }

        //check color is invalid
        $color = str_replace("#", "", Input::post("color"));
        if(!check_valid_colorhex($color)){
            $this->resp->msg = __("Color is not invalid.");
            $this->jsonecho();
        }

        $Category = Controller::model("Category");
        $Category->set("name", Input::post("name"))
                ->set("type", $type)
                ->set("color", strtoupper($color))
                ->set("description", Input::post("description"))
                ->set("user_id", $AuthUser->get("id"))
                ->set("updated_at", date("Y-m-d H:i:s"))
                ->set("created_at", date("Y-m-d H:i:s"))
                ->save();

        $this->resp->result = 1;
        $this->resp->category = (int) $Category->get("id");
        $this->resp->msg = __("Category added successfully!");
        $this->jsonecho();
    }
}