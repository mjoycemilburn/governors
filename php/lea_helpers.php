<?php

require ('../includes/governor_functions.php');

# As directed by helper_type :
#
# 'display_school_selector          '       -   return code to display a <select> element to pick
#                                               a school in order to display its governors webpage
#
# 'build_schools_management_screen'         -   return code to display an update screen to permit the lea
#                                               to manage schools and users tables
#
# 'validate_lea_password'                   -   check the supplied lea password
#
# 'get_school_data'                         -   get the school_name for given school, together with associated
#                                               user credentials
#
# 'ínsert_school'                           -   insert a school record for given school_id and also create a
#                                               user record for its clerk
#
# 'update_school'                           -   update the school record for given school and also update the user
#                                               record for its clerk
#
# 'delete_school'                           -   delete the school record for given school and also remove the user
#                                               record for its clerk
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
    <div style='text-align: center;'>
        Display Records for School :&nbsp;
        <select id='schoolsselector' name='schoolsselector' onchange = 'launchSchoolPage();'>
            <option value = '0'>Select a school</option>";

    while ($row = mysqli_fetch_array($result)) {

        $school_id = $row['school_id'];
        $school_name = $row['school_name'];

        $return .= "
            <option value='$school_id'>$school_name</option>";
    }

    $return .= "
        </select>
    </div>";

    $return .= "
    <div style='text-align: center; margin-top: 6vh;'>
        <button id = 'lealoginbutton'  type='button' class='btn-sm btn-primary mb-2'
              title='Display fields to permit registration/amendment of participating schools/administrators'
              onmousedown='displaySchoolsManagementScreen(\"\");'>Login for Schools Admin
        </button>
    </div>";

    echo $return;
}

#####################  build_schools_management_screen ####################

if ($helper_type == "build_schools_management_screen") {

// Build "toggling" divs (revealed as permitted/required) to display :
//      - an lea login field
//      - a "Create School button" that in turn reveals a screen to display school-name and school-administrator id/password
//      - a pull-down school selector to permit the above fields to be edited

    $return = "
    <div id = 'lealogindiv' style='text-align: center;'>
        Password :
        <input type='text' id ='leapassword' name ='leapassword' maxlength=20 size=20
               value=''
               title=\"The lea governors system administrator's password\">

        <button id = 'loginbutton'  type='button' class='btn-sm btn-primary mb-2'
            title='Login for schools data maintenance'
            onmousedown='validateLEAPassword();'>Login
        </button>
        <p id = 'leapassworderror'></p>
    </div>";

    $return .= "
    <div id = 'leaschoolmanagementdiv' style='display: none; text-align: center;'>
        <p id = 'messagearea'></p>
        <div id = 'leaschoolinsertdiv'
            style = 'margin-left: auto; margin-right: auto;
                     text-align: center; border: 1px solid black;'>
            <h4 style= 'margin-top: 2vh;'>Insert New School</h4>

            <div style='text-align: left; margin-top: 4vh;'>
                <label for='schoolname'>School name : </label>
                <input type='text' id ='schoolname' name ='schoolname' maxlength=40 size=30
                    autocomplete='off' value=''
                    title='The name of the school - needs to be unique'
                    onkeyup = 'clearAllErrors();'>
                <p id = 'schoolnameerror' class = 'formerrormessage'></p>

                <label for='clerkid'>Clerk User-id : </label>
                <input type='text' id ='clerkid' name ='clerkid' maxlength=40 size=20
                    autocomplete='off' value=''
                    title='A user-id to identify the school clerk (typically an email address)'
                    onkeyup = 'clearAllErrors();'>
                <p id = 'clerkiderror' class = 'formerrormessage'></p>

                <label for='clerkpassword'>Clerk Password : </label>
                <input type='text' id ='clerkpassword' name ='clerkpassword' maxlength=40 size=20
                    autocomplete='off' value=''
                    title='A governors system password for the school clerk'
                    onkeyup = 'clearAllErrors();'>
                <p id = 'clerkpassworderror' class = 'formerrormessage'></p>
             </div>

            <button id = 'insertbutton'  type='button' class='btn-sm btn-primary mb-2'
                title='Insert a record for this school'
                onmousedown='insertSchool();'>Insert
            </button>
        </div>
        <div id = 'leaschoolupdatetdiv'
            style = 'margin-top: 4vh; margin-left: auto; margin-right: auto;
                     text-align: center; border: 1px solid black;'>
            <h4 style= 'margin-top: 2vh; margin-bottom: 2vh;'>Update Existing School</h4>


            <label for='schoolsselector'>Update School Record for : </label>
            <select id='schoolsselector' name='schoolsselector' onchange = 'displayOldSchoolParameters();'>
                <option value = '0'>Select a school</option>";

    // We need t get a list of school names for the pull-down list. Seems a good idea to build a string to return this
    // to lea.html for use in checking uniqueness in subsequent updates - avoid problems with asynch code if we try to
    // do this later. Do the same thing with iser-ids

    $sql = "SELECT
              school_id,
              school_name
          FROM
              schools";

    $result = sql_result_for_location($sql, 2);

    $school_names = '';
    while ($row = mysqli_fetch_array($result)) {

        $school_id = $row['school_id'];
        $school_name = $row['school_name'];
        $school_names .= $school_name . ",";

        $return .= "
             <option value='$school_id'>$school_name</option>";
    }

    $sql = "SELECT
                user_id
            FROM
                users";

    $result = sql_result_for_location($sql, '2a');

    $user_ids = '';
    while ($row = mysqli_fetch_array($result)) {

        $user_id = $row['user_id'];
        $user_ids .= $user_id . ",";
    }

    $return .= "
            </select>

            <div id='oldschoolparameters' style='display: none; margin-top: 4vh;'>

                <div style='text-align: left;'>
                    <label for='oldschoolname'>School name : </label>
                    <input type='text' id ='oldschoolname' name ='oldschoolname' maxlength=40 size=30
                        autocomplete='off' value=''
                        title='The name of the school - needs to be unique'
                        onkeyup = 'clearAllErrors();'>
                    <p id = 'oldschoolnameerror' class = 'formerrormessage'></p>

                    <label for='olduserid'>Clerk User-id : </label>
                    <input type='text' id ='olduserid' name ='olduserid' maxlength=40 size=20
                        autocomplete='off' value=''
                        title='A user-id to identify the school clerk (typically an email address)'
                        onkeyup = 'clearAllErrors();'>
                    <p id = 'olduseriderror' class = 'formerrormessage'></p>

                    <label for='oldpassword'>Clerk Password : </label>
                    <input type='text' id ='oldpassword' name ='oldpassword' maxlength=40 size=20
                        autocomplete='off' value=''
                        title='A governors system password for the school clerk'
                        onkeyup = 'clearAllErrors();'>
                    <p id = 'oldpassworderror' class = 'formerrormessage'></p>
                </div>

                <button id = 'updatebutton'  type='button' class='btn-sm btn-primary mb-2'
                    title='Update the record for this school'
                    onmousedown='updateSchool();'>Update
                </button>

                <button id = 'deletebutton'  type='button' class='btn-sm btn-primary mb-2'
                    title='Delete the record for this school'
                    onmousedown='deleteSchool();'>Delete
                </button>
            </div>
        </div>
        <button id = 'logoutbutton'  type='button' class='btn-sm btn-primary mt-3 mb-2'
            title='Return to LEA home screen'
            onmousedown='displaySchoolSelector();'>Logout
        </button>
    </div>";
    $returns = array();

    $returns['return'] = prepareStringforXMLandJSONParse($return);
    $returns['school_names'] = prepareStringforXMLandJSONParse($school_names);
    $returns['user_ids'] = prepareStringforXMLandJSONParse($user_ids);

    $return = json_encode($returns);

    header("Content-type: text/xml");
    echo "<?xml version = '1.0' encoding = 'UTF-8'?>";
    echo "<returns>$return</returns>";
}

#####################  validate_lea_password ####################

if ($helper_type == "validate_lea_password") {

    $lea_password = $_POST['lea_password'];

    // get authorsied password from private storage in website root

    if (($_SERVER['REMOTE_ADDR'] == '127.0.0.1' or $_SERVER['REMOTE_ADDR'] == '::1')) {
        require '../lea_credentials.php';
    } else {
        $current_directory_root = $_SERVER['DOCUMENT_ROOT']; // one level above current directory
        // remove everything after and including /public_html

        $pieces = explode('/public_html', $current_directory_root);
        $root = $pieces[0];

        require "$root/lea_credentials.php";
    }

    if ($lea_password == $stored_password_from_lea_credentials) {
        echo "valid";
    } else {
        echo "invalid";
    }
}

#####################  get_school_data ####################

if ($helper_type == "get_school_data") {

    $school_id = $_POST['school_id'];

    $sql = "SELECT
              school_name
            FROM
                schools
            WHERE
                school_id = '$school_id';";

    $result = sql_result_for_location($sql, 3);

    $row = mysqli_fetch_array($result);
    $school_name = $row['school_name'];

    $sql = "SELECT
              user_id,
              password
            FROM
                users
            WHERE
                school_id = '$school_id';";

    $result = sql_result_for_location($sql, 4);

    $row = mysqli_fetch_array($result);
    $user_id = $row['user_id'];
    $password = $row['password'];

// stick the data in array as preparation for returning it as a json

    $returns = array();

    $returns['school_name'] = prepareStringforXMLandJSONParse($school_name);
    $returns['user_id'] = prepareStringforXMLandJSONParse($user_id);
    $returns['password'] = prepareStringforXMLandJSONParse($password);

    $return = json_encode($returns);

    header("Content-type: text/xml");
    echo "<?xml version = '1.0' encoding = 'UTF-8'?>";
    echo "<returns>$return</returns>";
}


#####################  ínsert_school ####################

if ($helper_type == "insert_school") {

    $school_name = $_POST['school_name'];
    $user_id = $_POST['user_id'];
    $password = $_POST['password'];

    $result = sql_result_for_location('START TRANSACTION', 5);

    $sql = "INSERT INTO schools (
                school_name)
            VALUES (
                '$school_name')";

    $result = sql_result_for_location($sql, 6);

    // get the school id that's just been allocated

    $school_id = mysqli_insert_id($con);

    $sql = "INSERT INTO users (
                user_id,
                password,
                school_id)
            VALUES (
                '$user_id',
                '$password',
                '$school_id')";

    $result = sql_result_for_location($sql, 7);

    $result = sql_result_for_location('COMMIT', 8);
}

#####################  update_school ####################

if ($helper_type == "update_school") {

    $school_id = $_POST['school_id'];
    $school_name = $_POST['school_name'];
    $user_id = $_POST['user_id'];
    $password = $_POST['password'];

    $result = sql_result_for_location('START TRANSACTION', 9);

    $sql = "UPDATE schools
            SET
                school_name = '$school_name'
            WHERE school_id = '$school_id';";

    $result = sql_result_for_location($sql, 10);

    $sql = "UPDATE users
            SET
                user_id = '$user_id',
                password = '$password'
            WHERE school_id= '$school_id';";


    $result = sql_result_for_location($sql, 11);

    $result = sql_result_for_location('COMMIT', 12);
}

#####################  delete_school ####################

if ($helper_type == "delete_school") {

    $school_id = $_POST['school_id'];

    $result = sql_result_for_location('START TRANSACTION', 13);

    $sql = "DELETE FROM schools
            WHERE
                school_id = '$school_id';";

    $result = sql_result_for_location($sql, 14);

    $sql = "DELETE FROM users
            WHERE
                school_id = '$school_id';";

    $result = sql_result_for_location($sql, 15);

    $result = sql_result_for_location('COMMIT', 16);
}

disconnect_from_database();
