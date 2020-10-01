<?php

require ('../includes/governor_functions.php');

# As directed by helper_type :                                                       
#
# 'display_school_selector          '   -   return code to display a <select> element to pick
#                                           a school in order to display its governors webpage
#                                           

$page_title = 'lea_helpers';

# set headers to NOT cache the page
header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
header("Pragma: no-cache"); //HTTP 1.0
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

date_default_timezone_set('Europe/London');

// connect to the governors database

connect_to_database();

// get helper-request

$helper_type = $_POST['helper_type'];

#####################  display_school_selector ####################

if ($helper_type == "display_school_selector") {

    $sql = "SELECT
                school_id,
                school_name
            FROM
                schools";

    $result = sql_result_for_location($sql, 1);

    $return = "
        <label for='schoolsselector'>Display Governing Body Record for School : </label>
        <select id='schoolsselector' name='schoolsselector' onchange = 'launchSchoolPage();'>
            <option value = '0'>Select a school</option>";

    while ($row = mysqli_fetch_array($result)) {

        $school_id = $row['school_id'];
        $school_name = $row['school_name'];

        $return .= "
            <option value='$school_id'>$school_name</option>";
    }

    $return .= "
        </select>";

    echo $return;
}

disconnect_from_database();


