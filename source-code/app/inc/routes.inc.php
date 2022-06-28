<?php 
// Language slug
// 
// Will be used theme routes
$langs = [];
foreach (Config::get("applangs") as $l) {
    if (!in_array($l["code"], $langs)) {
        $langs[] = $l["code"];
    }

    if (!in_array($l["shortcode"], $langs)) {
        $langs[] = $l["shortcode"];
    }
}
$langslug = $langs ? "[".implode("|", $langs).":lang]" : "";


/**
 * Theme Routes
 */

// Index (Landing Page)
// 
// Replace "Index" with "Login" to completely disable Landing page 
// After this change, Login page will be your default landing page
// 
// This is useful in case of self use, or having different 
// landing page in different address. For ex: you can install the script
// to subdirectory or subdomain of your wordpress website.
App::addRoute("GET", "/", "Index");
App::addRoute("GET", "/".$langslug."?/?", "Index");

//Login with google
App::addRoute("POST", "/".$langslug."?/login/google/?", "AuthGoogle");
App::addRoute("POST", "/".$langslug."?/login/facebook/?", "AuthFacebook");

// Login
App::addRoute("POST", "/".$langslug."?/login/?", "Login");



// Signup
// 
//  Remove or comment following line to completely 
//  disable signup page. This might be useful in case 
//  of self use of the script
App::addRoute("POST", "/".$langslug."?/signup/?", "Signup");

// Recovery
App::addRoute("POST", "/".$langslug."?/recovery/?", "Recovery");
App::addRoute("POST", "/".$langslug."?/reset/?", "PasswordReset");

/**
 * App Routes
 */
$types = [
    "income", "expense"
];

// Settings
$settings_pages = [
  "site", "integrations", "smtp"
];
App::addRoute("GET|POST", "/settings/[".implode("|", $settings_pages).":page]?/?", "Settings");


// Accounts - New Account
App::addRoute("GET|POST", "/accounts/?", "Accounts");
// Read | Edit | Delete Account
App::addRoute("GET|PUT|DELETE", "/accounts/[i:id]/?", "Account");
// Statistics for Account
App::addRoute("GET", "/accounts/[a:action]/[i:id]/?", "AccountStatistics");


$categories_pages = [
    "incomecategories", "expensecategories"
];
// Categories - New Category
App::addRoute("GET|POST", "/[".implode("|", $categories_pages).":page]/?", "Categories");
// Read | Edit | Delete Category
App::addRoute("GET|PUT|DELETE", "/[".implode("|", $categories_pages).":page]/[i:id]/?", "Category");


/***********************************************/
/******************* PHONG API********************/
//Budgets - New Budget
App::addRoute("GET|POST", "/budgets/?", "Budgets");
// Read | Edit | Delete Budget
App::addRoute("GET|PUT|DELETE", "/budgets/[i:id]/?", "Budget");
// Statistics for Budgets
App::addRoute("GET", "/budgets/[a:action]/?", "BudgetsStatistics");


//Transactions - New Transaction
App::addRoute("GET|POST", "/transactions/[".implode("|", $types).":page]/?", "Transactions");
// Read | Edit | Delete Transaction
App::addRoute("GET|PUT|DELETE", "/transactions/[i:id]/?", "Transaction");
// Statistics for Budgets
App::addRoute("GET", "/transactions/[".implode("|", $types).":page]/[a:action]/?", "TransactionsStatistics");

//Goals - New Goal
App::addRoute("GET|POST", "/goals/?", "Goals");
// Read | Edit | Delete Goal
App::addRoute("GET|POST|PUT|DELETE", "/goals/[i:id]/?", "Goal");


//Notifications - read_all Notification
App::addRoute("GET|POST", "/notifications/?", "Notifications");
App::addRoute("GET", "/notifications/[i:id]/?", "Notification");


// Dashboard
$dashboard_pages = [
    "category", "latest", 
    "accountbalance", "incomevsexpense", "categoryyearly",
    "latestall"
];

App::addRoute("GET", "/home/[".implode("|", $dashboard_pages).":page]/[".implode("|", $types).":type]?/?", "Dashboard");

// Report
$report_pages = [
    "totalBalance", "income", "transactions",
    "categorymonthly", "accounttransactions"
];
App::addRoute("GET", "/report/[".implode("|", $report_pages).":page]/?", "Report");


// Users
App::addRoute("GET|POST", "/users/?", "Users");

// Get By Id | Delete | Edit
App::addRoute("GET|DELETE|PUT|PATCH", "/users/[i:id]/?", "User");
/******************* END PHONG API********************/
/***********************************************/

App::addRoute("GET|POST|PUT", "/profile/?", "Profile");
App::addRoute("POST", "/change-password/?", "ChangePassword");

// Calendar
App::addRoute("GET", "/calendar/?", "Calendar");

// Calendar Day
$calendar_pages = [
    "income", "expense", "filterdate"
];
App::addRoute("GET|POST", "/calendar/[".implode("|", $calendar_pages).":page]/?", "Calendar");

// Email verification
App::addRoute("GET|POST", "/verification/email/[i:id].[a:hash]?/?", "EmailVerification");
