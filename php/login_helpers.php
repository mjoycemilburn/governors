<?php

require ('../includes/governor_functions.php');

# As directed by helper_type :
# 
# 'login            '       -   check the supplied user_id and password against the 
#                               member_passwords table and return the associated 
#                               school_id
#                                                         
# 'change_password'         -   change the password  
#                                       
# 'keep_alive'              -   give the session a stir                                                           
# 

$page_title = 'login_helpers';

# set headers to NOT cache the page
header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
header("Pragma: no-cache"); //HTTP 1.0
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

date_default_timezone_set('Europe/London');

session_start();

// connect to the governors database

connect_to_database();

// get helper-request

$helper_type = $_POST['helper_type'];

#####################  login ####################

if ($helper_type == "login") {

    $user_id = $_POST['userid'];
    $password = $_POST['password'];

    $return = "invalid";

    $sql = "SELECT 
                school_id
            FROM users
            WHERE 
                user_id = '$user_id'
            AND
                password = '$password';";

    $result = sql_result_for_location($sql, 1);

    if (mysqli_num_rows($result) >= 1) {

        $row = mysqli_fetch_array($result);
        $school_id = $row['school_id'];
        
    // OK - valid login. star session and set $_SESSION['governors_user_logged_in_for_school_id'] to $school_id 
        
        session_start();
        
        $_SESSION['governors_user_logged_in_for_school_id'] = $school_id;
        $return = "valid";
    }

    echo $return;
}

#####################  keep_alive ####################

if ($helper_type == "keep_alive") {

    # dummy helper - have already called session_start() so session should now be refreshed
    # error_log("keep alive called");
}    