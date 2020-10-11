<?php

require ('../includes/governor_functions.php');

# As directed by helper_type :
#
# 'build_governing_body_display'            -  return code to display a summary view of governing body
#                                              for given school as a page for use on the schools website
#
# 'build_governing_body_interests_display'  -  return code to display a summary view of governor business
#                                              interests for given school as a page for use on the school's
#                                              website
#
# 'build_goveror_attendance_display'        -  return code to display a summary view of goveror attendance
#                                              for given school as a page for the schools website

$page_title = 'page_helpers';

# set headers to NOT cache the page
header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
header("Pragma: no-cache"); //HTTP 1.0
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

date_default_timezone_set('Europe/London');

// connect to the governors database

connect_to_database();

// get helper-request

$helper_type = $_POST['helper_type'];
$school_id = $_POST['school_id'];

$sql = "SELECT
                school_name
            FROM
                schools
            WHERE
                school_id = '$school_id'";

$result = sql_result_for_location($sql, 1);

$row = mysqli_fetch_array($result);
$school_name = $row['school_name'];

#####################  build_governing_body_display ####################

if ($helper_type == "build_governing_body_display") {

    $return = "
        <h2 style='padding-top: 3vh; text-align: center;'>Governing Body for $school_name</h2>";

// get associative arrays for "decoded" governor types and governor roles

    $governor_types = get_governor_types();
    $governor_roles = get_governor_roles();

// now create a display row for each currently-defined governor

    $sql = "SELECT *
            FROM
                governors
            WHERE
                school_id = '$school_id'
            ORDER BY
                display_sequence";

    $result = sql_result_for_location($sql, 2);

    $return .= "
        <style>

        th {
            padding: 1vw;
            text-align: left;
            font-weight: bold;
        }

        td {
            padding: 1vw;
            vertical-align:top;
        }

        </style>

        <table style='margin-left: auto; margin-right: auto;'>
            <tr>
                <th>Role</th>
                <th>Name</th>
                <th>Contact Address</th>
                <th>Governor Type</th>
                <th>Governor Responsibilities</th>
                <th style='text-align: center;'>Term of Office<br>Ends</th>
            </tr>";

    // create a table row for each governor

    while ($row = mysqli_fetch_array($result)) {

        $governor_first_names = $row['governor_first_names'];
        $governor_surname = $row['governor_surname'];
        $governor_postal_address = $row['governor_postal_address'];
        $governor_telephone_number = $row['governor_telephone_number'];
        $governor_email_address = $row['governor_email_address'];

    // priority for contact-address display is email -> postal -> telephone

        if ($governor_email_address != '') {
            $governor_contact_address = $governor_email_address;
        } else {
            if ($governor_postal_address != '') {
                $governor_contact_address = $governor_postal_address;
            } else {
                 $governor_contact_address = $governor_telephone_number;
            }
        }

        $governor_name = "$governor_first_names $governor_surname";
        $governor_type = $governor_types[$row['governor_type_code']];
        $governor_role = $governor_roles[$row['governor_role_code']];

        if ($governor_role == "n/a")
            $governor_role = '';

        $governor_responsibilities = $row['governor_responsibilities'];
        $governor_appointment_date = $row['governor_appointment_date'];
        $governor_term_of_office = $row['governor_term_of_office'];
        $governor_retirement_date = date('Y-m-d', strtotime(" + $governor_term_of_office years", strtotime($governor_appointment_date)));

        $return .= "
            <tr>
                <td>$governor_role</td>
                <td>$governor_first_names $governor_surname</td>
                <td style='max-width: 25rem;'>$governor_contact_address</td>
                <td>$governor_type</td>
                <td style='min-width: 15rem;'>$governor_responsibilities</td>
                <td style='text-align: center;'>$governor_retirement_date</td>
            </tr>";
    }

    $return .= "
         </table>";

    // Now create a row for the clerk

    $sql = "SELECT *
            FROM
                clerks
            WHERE
                school_id = '$school_id';";

    $result = sql_result_for_location($sql, 3);

    $clerk_first_names = '';
    $clerk_surname = '';
    $clerk_email_address = '';

    $row = mysqli_fetch_array($result);

    if (mysqli_num_rows($result) >= 1) {
        $clerk_first_names = $row['clerk_first_names'];
        $clerk_surname = $row['clerk_surname'];
        $clerk_email_address = $row['clerk_email_address'];
    }

    $return .= "
        <div style = 'text-align: center; margin: 2vh 0 2vh 0; font-weight: bold;'>
            <span>Clerk : </span>
            <span>$clerk_first_names $clerk_surname : </span>
            <span>$clerk_email_address</span>
        </div>";

    echo $return;
}

#####################  build_governing_body_interests_display ####################

if ($helper_type == "build_governing_body_interests_display") {

    $return = "
        <h2 style='padding-top: 3vh; text-align: center;'>Governor Business Interests for $school_name</h2>";

// get associative arrays for "decoded" governor types and governor roles

    $governor_types = get_governor_types();
    $governor_roles = get_governor_roles();

// now create a display row for each currently-defined governor

    $sql = "SELECT *
            FROM
                governors
            WHERE
                school_id = '$school_id'
            ORDER BY
                display_sequence";

    $result = sql_result_for_location($sql, 2);

    $return .= "
        <style>

        th {
            padding: 1vw;
            text-align: left;
            font-weight: bold;
        }

        td {
            padding: 1vw;
            vertical-align:top;
        }

        </style>

        <table style='margin-left: auto; margin-right: auto; margin-top: 3vh;'>
            <tr>
                <th>Governor Name</th>
                <th style='text-align: left;'>Governor Type</th>
                <th style='text-align: left;'>Governor Interests</th>
            </tr>";

    // create a table row for each governor

    while ($row = mysqli_fetch_array($result)) {

        $governor_first_names = $row['governor_first_names'];
        $governor_surname = $row['governor_surname'];
        $governor_type = $governor_types[$row['governor_type_code']];
        $governor_business_interests = $row['governor_business_interests'];

        $return .= "
            <tr>
                <td>$governor_first_names $governor_surname</td>
                <td>$governor_type</td>
                <td style='min-width: 15rem;'>$governor_business_interests</td>
            </tr>";
    }

    $return .= "
         </table>";

    echo $return;
}

#####################  build_goveror_attendance_display ####################

if ($helper_type == "build_goveror_attendance_display") {

    $first_meeting_date = $_POST['first_meeting_date'];
    $last_meeting_date = $_POST['last_meeting_date'];

// get the dates for all meetings for this school as an array

    $sql = "SELECT meeting_date FROM meetings
            WHERE
                school_id = '$school_id'
            ORDER BY meeting_date ASC";

    $result = sql_result_for_location($sql, 4);

    $meeting_dates = array();

    $i = 0;
    while ($row = mysqli_fetch_array($result)) {
        $meeting_dates[$i] = $row['meeting_date'];
        $i++;
    }

    // get all the governors names, we'll need them later

    $sql = "SELECT
                governor_id,
                governor_first_names,
                governor_surname
            FROM governors
            WHERE
                school_id = '$school_id'";

    $result = sql_result_for_location($sql, 5);

    $governors = array();

    while ($row = mysqli_fetch_array($result)) {
        $governor_id = $row['governor_id'];
        $governors[$governor_id]['governor_first_names'] = $row['governor_first_names'];
        $governors[$governor_id]['governor_surname'] = $row['governor_surname'];
        $governor_name = $governors[$governor_id]['governor_first_names'] . " " . $governors[$governor_id]['governor_surname'];
    }

// if meeting date-range not specified, select the last 4 meetings

    if ($first_meeting_date == '') {
        $first_meeting_date_index = max(0, count($meeting_dates) - 4);
        $last_meeting_date_index = count($meeting_dates) - 1;
        $first_meeting_date = $meeting_dates[$first_meeting_date_index];
        $last_meeting_date = $meeting_dates[$last_meeting_date_index];
    } else {
        $first_meeting_date_index = array_search($first_meeting_date, $meeting_dates, true);
        $last_meeting_date_index = array_search($last_meeting_date, $meeting_dates, true);
    }

// now get all governors who were eligible to attend meetings for this school during
// this meeting_date range

    $sql = "SELECT
                governor_id,
                meeting_date,
                governor_present
            FROM governor_meeting_attendances
            WHERE
                school_id = '$school_id' AND
                meeting_date >= '$first_meeting_date' AND
                meeting_date <= '$last_meeting_date'
            ORDER BY governor_id,meeting_date ASC";

    $result = sql_result_for_location($sql, 6);

// format the results in a two-dimensional associative array :
// $governor_attendance[governor_id][meeting_date] = governor_present. The first index is just
// a number, but the second is a string and so gets treated as an associative value, giving us
// structures like
//
//      [1]=>
//           array(4) {
//             ["2017-01-24"]=>
//             string(1) "P"
//             ["2017-03-07"]=>
//             string(1) "P"
//             ["2017-05-02"]=>
//             string(1) "P"
//             ["2017-05-23"]=>
//              string(1) "P"
//            }
//
// A further complication is that governors who have started or finished their tenure /during/
// the period will have a reduced number of entries - so when using this array to build the
// display-screen we should anticipate gaps

    $governor_attendances = array();

    while ($row = mysqli_fetch_array($result)) {

        $governor_id = $row['governor_id'];
        $meeting_date = $row['meeting_date'];
        $governor_present = $row['governor_present'];
        $governor_attendances[$governor_id][$meeting_date] = $governor_present;
    }

    // OK - now use $governor_attendances to build the central section of the display - a block
    // of edit/delete columns for the (historic) meetings in the date-range first_meeting_date to
    // last meeting_date (max 4 - not that it matters exactly how many as the three blocks will
    // eventually be displayed alongside each other). The final display will contain the following
    // blocks:
    //
    // historic_meetings_left_sidebar
    // historic_meetings_update_block
    // historic_meetings_right_sidebar
    //

    $historic_meetings_update_block = "

    <div id='historicmeetingsblock' style = 'background: white; padding-top: 2vh;'>
        <form>
            <div>";


    // header dates. Use inline-block paragraphs as a way of creating fixed-width "table" cells
    // reserve a  column to leave space for governor name

    $historic_meetings_update_block .= "<p style= 'display: inline-block; width: 15rem; text-align: center;'></p>";

    for ($i = $first_meeting_date_index; $i <= $last_meeting_date_index; $i++) {

        $historic_meetings_update_block .= "
                <p style= 'display: inline-block; width: 10rem; text-align: center; font-weight: bold;'>$meeting_dates[$i]</p>";
    }
    $historic_meetings_update_block .= "
            </div>
            <div>";

    // governor rows

    foreach ($governor_attendances as $key => $list) {

        $governor_id = $key;
        $governor_name = $governors[$governor_id]['governor_first_names'] . " " . $governors[$governor_id]['governor_surname'];
        $historic_meetings_update_block .= "
                <p style= 'display: inline-block; width: 15rem; text-align: left; padding-left:2rem;'>
                    $governor_name
                </p>";


        for ($i = $first_meeting_date_index; $i <= $last_meeting_date_index; $i++) {
            $meeting_date = $meeting_dates[$i];
            if (array_key_exists($meeting_date, $list)) {

                // If key is present, we can be assured that this governor should have been present
                // at this meeting and can thus offer an update checkbox with an appropriate setting.
                // If key is absent, just display a blank here

                $historic_meetings_update_block .= "
                <p style= 'display: inline-block; width: 10rem; text-align: center;'>
                    <input class = 'attendancecheckbox$meeting_date' type='checkbox' id='historicmeetingcheckbox%$governor_id%$meeting_date' name='historicmeetingcheckbox%$governor_id%$meeting_date'";

                $governor_present = $governor_attendances[$governor_id][$meeting_date];
                if ($governor_present == "Y") {
                    $historic_meetings_update_block .= "
                        value = 'Y' checked>";
                } else {
                    $historic_meetings_update_block .= "
                    value = 'N'>";
                }
                $historic_meetings_update_block .= "
                    </p>";
            } else {
                $historic_meetings_update_block .= "
                <p style= 'display: inline-block; width: 10rem; text-align: center;'></p>";
            }
        }
        $historic_meetings_update_block .= "
            </div>
            <div>";
    }

    $historic_meetings_update_block .= "
            </div>
        </form>
    </div>";

// now build sidebars for the historic_meetings_update_block to move the display window up
// and down the historical sequence

    $historic_meetings_left_sidebar = "
    <p style= 'display: inline-block; width: 2rem; text-align: center;'>";

// only display a button if there's something for it to do!

    if ($first_meeting_date_index != 0) {
        $next_first_meeting_date_index = $first_meeting_date_index - 1;
        $next_last_meeting_date_index = $last_meeting_date_index - 1;

        $historic_meetings_left_sidebar .= "
        <button style = 'border: none;'
            title = 'Display earlier meetings'
            onclick='displayAttendances($school_id, \"$meeting_dates[$next_first_meeting_date_index]\", \"$meeting_dates[$next_last_meeting_date_index]\");'>
            <span class = 'oi oi-caret-left'></span>
        </button>";
    } else {
        $historic_meetings_left_sidebar .= "";
    }
    $historic_meetings_left_sidebar .= "
    </p>";

    $historic_meetings_right_sidebar = "
    <p style= 'display: inline-block; width: 2rem; text-align: center;'>";

// only display a button if there's something for it to do!

    if ($last_meeting_date_index != count($meeting_dates) - 1) {
        $next_first_meeting_date_index = $first_meeting_date_index + 1;
        $next_last_meeting_date_index = $last_meeting_date_index + 1;

        $historic_meetings_right_sidebar .= "
        <button style = 'border: none;'
            title = 'Display later meetings'
            onclick='displayAttendances($school_id, \"$meeting_dates[$next_first_meeting_date_index]\", \"$meeting_dates[$next_last_meeting_date_index]\");'>
            <span class = 'oi oi-caret-right'></span>
        </button>";
    } else {
        $historic_meetings_right_sidebar .= "";
    }
    $historic_meetings_right_sidebar .= "
    </p>";

    // OK - ready to put this lot together beneath a heading record and a button to add a new
    // meeting. Note that it might seem, at first sight, to have been possible to provide the
    // "insert meeting attendance" fields alongside the update columns for historic meetings
    // but, strictly, we don't know what governors are eligible for this meeting and they might
    // not be visible in the set selected for historic display - consider for instance, the case
    // of a governor who has is attending his/her first meeting

    $return = "
    <h2 style='text-align: center;'>Governor Meeting Attendances for $school_name</h2>";

    // The historic_meeting_update_block now needs to be displayed between its sidebars
    // Some sort of table arrangement seems the best way forward. Since we essentially only
    // have one row to display, flexbox is preferred

    $return .= "
    <div style='display:flex; justify-content: center;'>
        <div>$historic_meetings_left_sidebar</div>
        <div>$historic_meetings_update_block</div>
        <div>$historic_meetings_right_sidebar</div>
    </div>";

    echo $return;
}


disconnect_from_database();
