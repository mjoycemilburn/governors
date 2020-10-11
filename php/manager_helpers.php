<?php

require ('../includes/governor_functions.php');

# As directed by helper_type :
#
# 'build_governing_body_update_table'   -   return code to display a summary view of governing body
#                                           for given school as a base from which to launch updates
#
# 'build_governor_insert_screen'        -   return code to display code to insert a new governor
#
# 'insert_governor'                     -   insert a new governor
#
# 'get_governor_data'                   -   return the data for given governor_id                                                         '
#
# 'update_governor'                     -   update governor for given id
#
# 'delete_governor'                      -   delete governor for given id
#
# 'reorder_governors'                   -   reorder the sections_configuration.txt file from the screen Dom
#
# 'build_clerk_update_screen'           -   return code to display an update screen for the clerk for given
#                                           school. Note that the system takes care that there always be a
#                                           clerk record for each school (creating a blank one if necessary) and
#                                           provides no facility for creating a second!
#
# 'update_clerk'                        -   update the clerk for given school
#
# 'build_attendances_update_screen'     -   return code to display an update screen (update/insert) for
#                                           meeting attendancs for the given school and given meeting
#                                           date-range (most recent 4 meetings, if range not specified)
#
# 'build_meeting_insert_table'          -   return code to create a new meeting for given school and date
#                                           for the set of governors in office on that date
#
# 'insert_meeting'                     -   insert a new meeting for given school
#
# 'test_for_unique_meeting_date'        -   return "unique" if there is no existing meeting record for given
#                                           school_id and meeting_date
#
# 'update_meeting'                      -   update the attendance detail for given meeting and given school
#
# 'delete_meeting'                      -   delete meeting for given school_id and meeting_date
#
# 'build_documents_update_table'        -   return code to display a summary view of the documents table
#                                           for given school as a base from which to launch updates
#
# 'build_document_insert_screen'         -   return code to create a new document
#
# 'insert_document'                      -   insert a new document for given school  and document_title
#
# 'build_version_insert_screen'          -   return code to create a new version of a document
#
# 'insert_version'                       -   insert a new version for given school  and document_title
#
# 'reset_documents_update_table_row'     -   modify given row of the current documents_update table to respond to
#                                            the selection of a different version number
#
# 'build_document_review_screen'         -   display a screen to update the review_date on the latest vn of a given document
#
# 'update_document'                      -   update the database record for given document
#
# 'delete_document'                      -   delete document for given document

$page_title = 'manager_helpers';

# set headers to NOT cache the page
header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
header("Pragma: no-cache"); //HTTP 1.0
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

date_default_timezone_set('Europe/London');

// check logged_in

session_start();

if (!isset($_SESSION['governors_user_logged_in_for_school_id'])) {
    echo "%timed_out%";
    exit(0);
} else {
    $school_id = $_SESSION['governors_user_logged_in_for_school_id'];
}

// connect to the governors database

connect_to_database();

// get helper-request

$helper_type = $_POST['helper_type'];

#####################  build_governing_body_update_table ####################

if ($helper_type == "build_governing_body_update_table") {

// Generate tables to represent a summary of the current governing body membership and
// to act as launchpads for updating governor and clerk records. The governon table
// looks a bit more complicated  than might be expected since we're using sortableJS to
// provide a "drag and drop" mechanism for imposing a display sequence on the records'
// SortableJS is a javascript library downloaded from Github. See
// https://www.solodev.com/blog/web-design/how-to-create-sortable-lists-with-sortablejs.stml for example

    $sql = "SELECT
                school_name
            FROM
                schools
            WHERE
                school_id = '$school_id'";

    $result = sql_result_for_location($sql, 1);

    $row = mysqli_fetch_array($result);
    $school_name = $row['school_name'];

    $return = "
        <h2 style='text-align: center;'>Governing Body for $school_name</h2>
            <h4 style= 'text-align: center;'>(Click row for details)</h4>
        <p id = 'messagearea' style = 'text-align: center; padding-top: .5vh; padding-bottom: .5vh; margin-top: 0; margin-bottom: 0;'></p>
        <div style='text-align: center; margin-bottom:4vh;'>
            <button id = 'insertbutton'  type='button' class='btn-sm btn-primary mb-2'
                title='Create the record for a new Governor'
                onmousedown='displayGovernorInsertScreen();'>Add new Governor
            </button>
        </div>";

// get associative arrays for "decoded" governor types and governr roles

    $governor_types = get_governor_types();
    $governor_roles = get_governor_roles();

// now create a display row for each currently-defined governor

    $sql = "SELECT
                    governor_id,
                    governor_first_names,
                    governor_surname,
                    governor_type_code,
                    governor_role_code,
                    governor_appointment_date,
                    governor_term_of_office,
                    display_sequence
                FROM
                    governors
                WHERE
                    school_id = '$school_id'
                ORDER BY
                    display_sequence";

    $result = sql_result_for_location($sql, 2);

    $i = 0;

    $return .= "
        <div class='container'>
            <div class='row justify-content-center pt-0 pb-0 pt-1' style='background: white;'>
                <div class='list-group-item mb-0 pb-1'>
                    <p class= 'cell bold' style='width: 10rem;'>Name</p>
                    <p class= 'cell bold' style='width: 10rem;'>Governor Type</p>
                    <div class= 'cell bold center' style='width: 10rem;'><span>Governor Role</span></div>
                    <div class= 'cell bold center' style='width: 10rem;'><span>Term of Office<br>Ends</span></div>
                    <p class= 'cell bold' style='width: 5rem;'></p>
                    <p class= 'cell bold' style='width: 5rem;'></p>
                </div>
            </div>
            <div id='governorssortablelist' class='list-group'>";

    while ($row = mysqli_fetch_array($result)) {

        $governor_id = $row['governor_id'];
        $governor_first_names = $row['governor_first_names'];
        $governor_surname = $row['governor_surname'];
        $governor_name = "$governor_first_names $governor_surname";
        $governor_type_code = $row['governor_type_code'];
        $governor_role_code = $row['governor_role_code'];
        $governor_appointment_date = $row['governor_appointment_date'];
        $governor_term_of_office = $row['governor_term_of_office'];
        $governor_retirement_date = date('Y-m-d', strtotime(" + $governor_term_of_office years", strtotime($governor_appointment_date)));


        $i++;

        $governor_type = $governor_types[$governor_type_code];
        $governor_role = $governor_roles[$governor_role_code];


        if ($governor_type == 'n/a') {
            $governor_type = '';
        }
        if ($governor_role == 'n/a') {
            $governor_role = '';
        }

        $return .= "
                <div id = 'govrow$i' class='row justify-content-center pt-0 pb-0' style='background: white;'>
                    <div class='list-group-item mb-0 pb-0 pt-1'>

                        <span class = 'governorsentry' style = 'display: none;'>$governor_id</span>
                        <p class= 'cell' style='width: 10rem;' onmousedown='displayGovernorUpdateScreen($governor_id);'>$governor_name</p>
                        <p class= 'cell' style='width: 10rem;' onmousedown='displayGovernorUpdateScreen($governor_id);'>$governor_type</p>
                        <div class= 'cell center' style='width: 10rem;' onmousedown='displayGovernorUpdateScreen($governor_id);'><span>$governor_role</span></div>
                        <div class= 'cell center' style='width: 10rem;' onmousedown='displayGovernorUpdateScreen($governor_id);'><span>$governor_retirement_date</span></div>

                        <p class= 'cell' style='width: 5rem;'>
                            <button id = 'editbutton$i'  type='button' class='ml-2 mr-2 btn-sm btn-primary'
                                title='Edit this Governor record'
                                onmousedown='displayGovernorUpdateScreen($governor_id);'>Update
                            </button>
                        </p>
                        <p class= 'cell' style='width: 5rem;'>
                            <button id = 'deletebutton'  type='button' class='btn-sm btn-primary' style = 'margin-left: 2vw;'
                                title='Delete the record for this Governor'
                                onmousedown='deleteGovernor($governor_id, \"$governor_name\");'>Delete
                            </button>
                        </p>
                </div>
                </div>";
    }

// add a final button to allow the governors array to be re-ordered

    $return .= "
            </div>
            <div style = 'text-align: center;'>
                <span>To change the order of rows, click and drag in the left-hand panel and then click the </span>
                <button id = 'reorderbutton'  type='button' class='mt-3 mr-2 btn-sm btn-primary'
                    title='Re-order the Governors after \"drag and drop\"'
                    onclick='reorderGovernors();'>Reorder
                </button>
                <span>  button</span>
            </div>";

// display details for the clerk for this school. If no clerk record exists, create one

    $sql = "SELECT * FROM clerks
            WHERE
                school_id = '$school_id'";

    $result = sql_result_for_location($sql, 3);

    if (mysqli_num_rows($result) >= 1) {

        $row = mysqli_fetch_array($result);
        $clerk_first_names = $row['clerk_first_names'];
        $clerk_surname = $row['clerk_surname'];
        $clerk_email_address = $row['clerk_email_address'];
    } else {

        $clerk_first_names = '';
        $clerk_surname = '';
        $clerk_postal_address = '';
        $clerk_telephone_number = '';
        $clerk_email_address = '';

        $sql = "INSERT INTO clerks (
                school_id,
                clerk_first_names,
                clerk_surname,
                clerk_telephone_number,
                clerk_email_address,
                clerk_postal_address)
            VALUES (
                '$school_id',
                '$clerk_first_names',
                '$clerk_surname',
                '$clerk_telephone_number',
                '$clerk_email_address',
                '$clerk_postal_address');";

        $result = sql_result_for_location($sql, 4);
    }


    $return .= "
            <div class='row justify-content-center mt-4 pt-0 pb-0 pt-1' style='background: white;'>
                <div class='list-group-item mb-0 pb-1'>
                    <p class= 'cell bold' style='width: 10rem;' onmousedown='displayClerkUpdateScreen();'>Clerk : </p>
                    <p class= 'cell' style='width: 20rem;' onmousedown='displayClerkUpdateScreen();'>$clerk_first_names $clerk_surname : </p>
                    <p class= 'cell' style='width: 20em;' onmousedown='displayClerkUpdateScreen();'>$clerk_email_address</p>
                    <button id = 'editclerkbutton'  type='button' class='ml-2 mr-2 btn-sm btn-primary'
                        title='Edit the Clerk record'
                        onmousedown='displayClerkUpdateScreen();'>Update
                    </button>
                </div>
            </div>
        </div>";

    // add buttons to display links to useful website pages

    $return .= "
        <div>
            <button  type='button' class='ml-2 mt-4 mr-2 btn-sm btn-primary'
                title='Display the link to a web-page summary view of the Governing body'
                onmousedown='grabClipboardLink(\"pages.html?id=$school_id&target=governors\");'>Get Governors Summary Link
            </button>
            <button  type='button' class='ml-4 mt-4 mr-2 btn-sm btn-primary'
                title='Display the link to a web-page summary view of Governor Business Interests'
                onmousedown='grabClipboardLink(\"pages.html?id=$school_id&target=busints\");'>Get Governor Business Interests Link
            </button>";

    echo $return;
}

#####################  get_governor_data ####################

if ($helper_type == "get_governor_data") {

    $governor_id = $_POST['governor_id'];

    $sql = "SELECT * FROM governors
    WHERE governor_id = '$governor_id';
    ";

    $result = sql_result_for_location($sql, 5);
    $row = mysqli_fetch_array($result);

// stick the data in array as preparation for returning it as a json

    $returns = array();

    $returns['governor_first_names'] = prepareStringforXMLandJSONParse($row['governor_first_names']);
    $returns['governor_surname'] = prepareStringforXMLandJSONParse($row['governor_surname']);
    $returns['governor_type_code'] = prepareStringforXMLandJSONParse($row['governor_type_code']);
    $returns['governor_role_code'] = prepareStringforXMLandJSONParse($row['governor_role_code']);
    $returns['governor_responsibilities'] = prepareStringforXMLandJSONParse($row['governor_responsibilities']);
    $returns['governor_telephone_number'] = prepareStringforXMLandJSONParse($row['governor_telephone_number']);
    $returns['governor_email_address'] = prepareStringforXMLandJSONParse($row['governor_email_address']);
    $returns['governor_postal_address'] = prepareStringforXMLandJSONParse($row['governor_postal_address']);
    $returns['governor_appointment_date'] = prepareStringforXMLandJSONParse($row['governor_appointment_date']);
    $returns['governor_term_of_office'] = prepareStringforXMLandJSONParse($row['governor_term_of_office']);
    $returns['governor_business_interests'] = prepareStringforXMLandJSONParse($row['governor_business_interests']);

    $return = json_encode($returns);

    header("Content-type: text/xml");
    echo "<?xml version = '1.0' encoding = 'UTF-8'
    ?>";
    echo "<returns>$return</returns>";
}

#####################  build_governor_insert_screen ####################

if ($helper_type == "build_governor_insert_screen") {

    $return = "

<h2 style='text-align: center;'>Governor Data Input Form</h2>

<div style = 'width: 90%; padding: 5vh; margin: 5vh auto 0 auto; background: white;'>";

    // position a "Back" button at top right

    $return .= "

    <div style='display: flex; justify-content: center; position: relative;'>
            <button id = 'backbutton'  type='button' class='btn-sm btn-primary' style='position: absolute; right: 0;'
            title='Return to the Governors screen'
            onmousedown='displayGovernorsScreen(\"\");'>Back
        </button>
    </div>

    <form id='governordata' method = 'POST'>

        <label for='governorfirstnames'>Governor First Names : </label>
        <input type='text' id ='governorfirstnames' name ='governorfirstnames' maxlength=20 size=20
               autocomplete='off' value=''
               title='Governor christian names'>
        <p id = 'governorfirstnameserror'></p>

        <label for='governorsurname'>Governor Surname : </label>
        <input type='text' id ='governorsurname' name ='governorsurname' maxlength=20 size=20
               autocomplete='off' value=''
               title='Governor surname'>
        <p id = 'governorsurnameerror'></p>

        <label for='governortypes'>Governor type : </label>
        <input type = radio name = 'governortypes' value = 1> Staff Governor&nbsp;
        <input type = radio name = 'governortypes' value = 2> LA Governor&nbsp;
        <input type = radio name = 'governortypes' value = 3 checked> Parent Governor&nbsp;
        <input type = radio name = 'governortypes' value = 4> Exec Head&nbsp;
        <input type = radio name = 'governortypes' value = 5> Co-opted Governor&nbsp;
        <input type = radio name = 'governortypes' value = 6> n/a
        <p id = 'governortypeserror'></p>

        <label for='governorroles'>Governor Role : </label>
        <input type = radio name = 'governorroles' value = 1> Chair&nbsp;
        <input type = radio name = 'governorroles' value = 2> Vice Chair&nbsp;
        <input type = radio name = 'governorroles' value = 3 checked> n/a
        <p id = 'governorroleserror'></p>

        <label for ='governorresponsibilities'>Governor Responsibilities : </label>
        <textarea id ='governorresponsibilities' name ='governorresponsibilities' rows = 3 cols = 60 maxlength = 200  wrap= 'virtual'
                  autocomplete='off' value=''
                  title='Details of areas of special reponsibily handled by this governor'></textarea>
        <p id = 'governorresponsibilitieserror'></p>

        <label for='governorpostaladdress'>Postal address : </label>
        <input type='text' id ='governorpostaladdress' name ='governorpostaladdress' maxlength=60 size=60
               autocomplete='off' value=''
               title='The postal address for this governor as a free-format string'>
        <p id = 'governorpostaladdresserror'></p>

        <label for='governortelephonenumber'>Telephone Number : </label>
        <input type='text' id ='governortelephonenumber' name ='governortelephonenumber' maxlength=40 size=40
               autocomplete='off' value=''
               title='Governor telephone number' maxlength=40 size=40>
        <p id = 'governortelephonenumbererror'></p>

        <label for='governoremailaddress'>Email address : </label>
        <input type='text' id ='governoremailaddress' name ='governoremailaddress' maxlength=40 size=40
               autocomplete='off' value=''
               title='Governor email address'>
        <p id = 'governoremailaddresserror'></p>

        <label for='governorappointmentdate'>Appointment date : </label>
        <input type='text' id ='governorappointmentdate' name ='governorappointmentdate' size=10
               autocomplete='off' value=''
               title='The start date for this appointment'
               onmousedown='applyDatepicker(\"governorappointmentdate\");'>
        <p id = 'appointmentdateerror'></p>

        <label for='governortermofoffice'>Term Of Office (years) : </label>
        <input type='text' id ='governortermofoffice' name ='governortermofoffice' maxlength=1 size=1
               autocomplete='off' value=''
               title='The number of years of tenure for this appointment'>
        <p id = 'termofofficeerror'></p>

        <label for='governorbusinessinterests'>Business interests : </label>
        <textarea id ='governorbusinessinterests' name ='governorbusinessinterests' rows = 3 cols = 60
                  autocomplete='off' value=''
                  title='Governor business interests'></textarea>
        <p id = 'governorbusinessinterestserror'></p>

    </form>

    <div id = 'buildgovernorbuttons' style = 'width: 90%; margin: 4vh auto 2vh auto; text-align: center;'>
        <button id = 'insertbutton'  type='button' class='btn-sm btn-primary' style = 'margin-left: 2vw;'
                title='Insert a record for this Governor'
                onmousedown='insertGovernor();'>Insert
        </button>
        <button id = 'cancelbutton'  type='button' class='btn-sm btn-primary' style = 'margin-left: 2vw;'
                title='Cancel the insert'
                onmousedown='displayGovernorsScreen(\"Insert cancelled\");'>Cancel
        </button>
    </div>

</div>";

    echo $return;
}

#####################  insert_governor ####################

if ($helper_type == "insert_governor") {

    $governor_first_names = $_POST['governorfirstnames'];
    $governor_surname = $_POST['governorsurname'];
    $governor_type_code = $_POST['governortypes'];
    $governor_role_code = $_POST['governorroles'];
    $governor_responsibilities = $_POST['governorresponsibilities'];
    $governor_telephone_number = $_POST['governortelephonenumber'];
    $governor_email_address = $_POST['governoremailaddress'];
    $governor_postal_address = $_POST['governorpostaladdress'];
    $governor_appointment_date = $_POST['governorappointmentdate'];
    $governor_term_of_office = $_POST['governortermofoffice'];
    $governor_business_interests = $_POST['governorbusinessinterests'];

// get the current governors total for this school as display_sequence

    $sql = "SELECT COUNT(governor_id) as display_sequence
            FROM governors
            WHERE school_id = '$school_id';";

    $result = sql_result_for_location($sql, 6);
    $row = mysqli_fetch_array($result);
    $display_sequence = $row['display_sequence'];

    $sql = "INSERT INTO governors (
                school_id,
                governor_first_names,
                governor_surname,
                governor_type_code,
                governor_role_code,
                governor_responsibilities,
                governor_telephone_number,
                governor_email_address,
                governor_postal_address,
                governor_appointment_date,
                governor_term_of_office,
                governor_business_interests,
                display_sequence)
            VALUES (
                '$school_id',
                '$governor_first_names',
                '$governor_surname',
                '$governor_type_code',
                '$governor_role_code',
                '$governor_responsibilities',
                '$governor_telephone_number',
                '$governor_email_address',
                '$governor_postal_address',
                '$governor_appointment_date',
                '$governor_term_of_office',
                '$governor_business_interests',
                '$display_sequence');";

    $result = sql_result_for_location($sql, 7);

    echo "Insert succeeded";
}

#####################  update_governor ####################

if ($helper_type == "update_governor") {

    $governor_id = $_POST['governor_id'];
    $governor_first_names = $_POST['governorfirstnames'];
    $governor_surname = $_POST['governorsurname'];
    $governor_type_code = $_POST['governortypes'];
    $governor_role_code = $_POST['governorroles'];
    $governor_responsibilities = $_POST['governorresponsibilities'];
    $governor_telephone_number = $_POST['governortelephonenumber'];
    $governor_email_address = $_POST['governoremailaddress'];
    $governor_postal_address = $_POST['governorpostaladdress'];
    $governor_appointment_date = $_POST['governorappointmentdate'];
    $governor_term_of_office = $_POST['governortermofoffice'];
    $governor_business_interests = $_POST['governorbusinessinterests'];

    $sql = "UPDATE governors
            SET
                governor_first_names = '$governor_first_names',
                governor_surname = '$governor_surname',
                governor_type_code = '$governor_type_code',
                governor_role_code = '$governor_role_code',
                governor_responsibilities = '$governor_responsibilities',
                governor_telephone_number = '$governor_telephone_number',
                governor_email_address = '$governor_email_address',
                governor_postal_address = '$governor_postal_address',
                governor_appointment_date = '$governor_appointment_date',
                governor_term_of_office = '$governor_term_of_office',
                governor_business_interests = '$governor_business_interests'
            WHERE governor_id = '$governor_id';";

    $result = sql_result_for_location($sql, 9);

    echo "Update succeeded";
}

#####################  delete_governor ####################

if ($helper_type == "delete_governor") {

    $governor_id = $_POST['governor_id'];

    $result = sql_result_for_location('START TRANSACTION', 10); // sql failure after this point will initiate rollback

    $sql = "DELETE FROM governors
            WHERE
                governor_id = '$governor_id';";

    $result = sql_result_for_location($sql, 11);

    $sql = "DELETE FROM governor_meeting_attendances
            WHERE
                governor_id = '$governor_id';";

    $result = sql_result_for_location($sql, 12);

    $result = sql_result_for_location('COMMIT', 13);

    echo "Deletion succeeded";
}

#####################  reorder_governors ####################

if ($helper_type == "reorder_governors") {

    $sequenced_governor_ids_json = $_POST['sequenced_governor_ids_json'];

// turn the json back into an array of governor_ids

    $governor_ids = json_decode($sequenced_governor_ids_json, true);

    for ($i = 0; $i < count($governor_ids); $i++) {

        $sql = "UPDATE governors
                SET
                    display_sequence = '$i'
                WHERE
                    governor_id = '$governor_ids[$i]'";

        $result = sql_result_for_location($sql, 14);
    }

    echo "Reorder succeeded";
}

#####################  build_clerk_update_screen ####################

if ($helper_type == "build_clerk_update_screen") {

// The systme will have created a blank clerk record in none exists, so get whatever
// data is available and display it for update

    $sql = "SELECT * FROM clerks
            WHERE
                school_id = '$school_id'";

    $result = sql_result_for_location($sql, 15);

    $row = mysqli_fetch_array($result);
    $clerk_first_names = $row['clerk_first_names'];
    $clerk_surname = $row['clerk_surname'];
    $clerk_postal_address = $row['clerk_postal_address'];
    $clerk_telephone_number = $row['clerk_telephone_number'];
    $clerk_email_address = $row['clerk_email_address'];

    $return = "

<h2 style='text-align: center;'>Clerk Data Input Form</h2>
<div style = 'width: 90%; padding: 5vh; margin: 5vh auto 0 auto; background: white;'>

   <div style='display: flex; justify-content: center; position: relative;'>
            <button id = 'backbutton'  type='button' class='btn-sm btn-primary' style='position: absolute; right: 0;'
            title='Return to the Governors screen'
            onmousedown='displayGovernorsScreen(\"\");'>Back
        </button>
    </div>

    <form id='clerkdata' method = 'POST'>

        <label for='clerkfirstnames'>Clerk First Names : </label>
        <input type='text' id ='clerkfirstnames' name ='clerkfirstnames' maxlength=20 size=20
               autocomplete='off' value=$clerk_first_names
               title='Clerk christian names'>
        <p id = 'clerkfirstnameserror'></p>

        <label for='clerksurname'>Clerk Surname : </label>
        <input type='text' id ='clerksurname' name ='clerksurname' maxlength=20 size=20
               autocomplete='off' value='$clerk_surname'
               title='Clerk surname'>
        <p id = 'clerksurnameerror'></p>

        <label for='clerkpostaladdress'>Postal address : </label>
        <input type='text' id ='clerkpostaladdress' name ='clerkpostaladdress' maxlength=60 size=60
               autocomplete='off' value='$clerk_postal_address'
               title='The postal address for the clerk as a free-format string'>
        <p id = 'clerkpostaladdresserror'></p>

        <label for='clerktelephonenumber'>Telephone Number : </label>
        <input type='text' id ='clerktelephonenumber' name ='clerktelephonenumber' maxlength=40 size=40
               autocomplete='off' value='$clerk_telephone_number '
               title='Clerk telephone number' maxlength=40 size=40>
        <p id = 'clerktelephonenumbererror'></p>

        <label for='clerkemailaddress'>Email address : </label>
        <input type='text' id ='clerkemailaddress' name ='clerkemailaddress' maxlength=40 size=40
               autocomplete='off' value='$clerk_email_address'
               title='Clerk email address'>
        <p id = 'clerkemailaddresserror'></p>

    </form>

    <div id = 'clerkupdatebuttons' style = 'width: 90%; margin: 4vh auto 2vh auto; text-align: center;'>
        <button id = 'clerkupdatebutton'  type='button' class='btn-sm btn-primary' style = 'margin-left: 2vw;'
                title='Update the record for the Clerk for this School'
                onmousedown='updateClerk();'>Update
        </button>
        <button id = 'clerkcancelbutton'  type='button' class='btn-sm btn-primary' style = 'margin-left: 2vw;'
                title='Cancel the Update'
                onmousedown='displayGovernorsScreen(\"Update cancelled\");'>Cancel
        </button>
    </div>";

    echo $return;
}

#####################  update_clerk ####################

if ($helper_type == "update_clerk") {

    $clerk_first_names = $_POST['clerkfirstnames'];
    $clerk_surname = $_POST['clerksurname'];
    $clerk_postal_address = $_POST['clerkpostaladdress'];
    $clerk_telephone_number = $_POST['clerktelephonenumber'];
    $clerk_email_address = $_POST['clerkemailaddress'];

    $sql = "UPDATE clerks
            SET
                clerk_first_names = '$clerk_first_names',
                clerk_surname = '$clerk_surname',
                clerk_postal_address = '$clerk_postal_address',
                clerk_telephone_number = '$clerk_telephone_number',
                clerk_email_address = '$clerk_email_address'
            WHERE school_id = '$school_id';";

    $result = sql_result_for_location($sql, 16);

    echo "Update succeeded";
}


#####################  build_attendances_update_screen ####################

if ($helper_type == "build_attendances_update_screen") {

    $first_meeting_date = $_POST['first_meeting_date'];
    $last_meeting_date = $_POST['last_meeting_date'];

// get the dates for all meetings for this school as an array

    $sql = "SELECT meeting_date FROM meetings
            WHERE
                school_id = '$school_id'
            ORDER BY meeting_date ASC";

    $result = sql_result_for_location($sql, 17);

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

    $result = sql_result_for_location($sql, 18);

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

// Now get all governors who were eligible to attend meetings for this school during
// this meeting_date range. Note that it's not possible for a governor to appear at
// a meeting unless they were eligible and vice verso

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

    $result = sql_result_for_location($sql, 19);

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
        <form id = 'historic_meetings' method = 'POST'>
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
                    <span class = 'governor$meeting_date' style ='display: none;'>$governor_id</span>
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

    // add update/delete button rows at the bottom of the historicmeetingsblock

    $historic_meetings_update_block .= "<p style= 'display: inline-block; width: 15rem; text-align: center;'></p>";

    for ($i = $first_meeting_date_index; $i <= $last_meeting_date_index; $i++) {
        $historic_meetings_update_block .= "
                <p = 'historicmeetingupdatebuttons' style= 'display: inline-block; width: 10rem; text-align: center;'>
                    <button id = 'historicmeetingupdatebutton%$i'  type='button' class='btn-sm btn-primary'
                        title='Update this meeting record'
                        onmousedown='updateMeeting(\"$meeting_dates[$i]\");'>Update
                    </button>
                </p>";
    }
    $historic_meetings_update_block .= "
            </div>
            <div>";

    $historic_meetings_update_block .= "<p style= 'display: inline-block; width: 15rem; text-align: center;'></p>";

    for ($i = $first_meeting_date_index; $i <= $last_meeting_date_index; $i++) {
        $historic_meetings_update_block .= "
                <p style= 'display: inline-block; width: 10rem; text-align: center;'>
                    <button id = 'historicmeetingdeletebutton%$i'  type='button' class='btn-sm btn-primary'
                        title='Delete this meeting record'
                        onmousedown='deleteMeeting(\"$meeting_dates[$i]\");'>Delete
                    </button>
                </p>";
    }
    $historic_meetings_update_block .= "
            </div>";

    $historic_meetings_update_block .= "
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
            onclick='displayMeetingsScreen(\"$meeting_dates[$next_first_meeting_date_index]\", \"$meeting_dates[$next_last_meeting_date_index]\",\"\",\"\")'>
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
            onclick = 'displayMeetingsScreen(\"$meeting_dates[$next_first_meeting_date_index]\", \"$meeting_dates[$next_last_meeting_date_index]\",\"\",\"\")'>
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

    $sql = "SELECT
                school_name
            FROM
                schools
            WHERE
                school_id = '$school_id'";

    $result = sql_result_for_location($sql, 20);

    $row = mysqli_fetch_array($result);
    $school_name = $row['school_name'];

    $return = "
    <h2 style='text-align: center;'>Governor Meeting Attendances for $school_name</h2>
    <div style='text-align: center; margin-bottom:4vh;'>
        <button id = 'insertmeetingbutton'  type='button' class='btn-sm btn-primary mt-4 mb-4'
            title='Create a new meeting and associated attendance records'>Add new Meeting  for :&nbsp;
                    <input type='text' id ='insertmeetingdate' name ='insertmeetingdate' size=10rem
                           style=' text-align: center; font-weight: bold;'
                           autocomplete='off'
                           value = ''
                           title='The date currently recorded against this meeting'
                           onmousedown='applyDatepicker(\"insertmeetingdate\");'
                           onchange='displayMeetingInsertScreen();'>
        </button>
        <span id='messagearea'></span
    </div>";

    // The historic_meeting_update_block now needs to be displayed between its sidebars
    // Some sort of table arrangement seems the best way forward. Since we essentially only
    // have one row to display, flexbox is preferred

    $return .= "
    <div style='display:flex; justify-content: center;'>
        <div>$historic_meetings_left_sidebar</div>
        <div>$historic_meetings_update_block</div>
        <div>$historic_meetings_right_sidebar</div>
    </div>";

    // add a final button to display the website view of governor attendance

    $return .= "
        <div style='text-align: left;'>
            <button  type='button' class='ml-2 mt-4 mr-2 btn-sm btn-primary'
                title='Display the link to the web-page view of Governor attendance'
                onmousedown='grabClipboardLink(\"pages.html?id=$school_id&target=attendances\");'>Get Attendances Link
            </button>
        </div>";


    echo $return;
}

#####################  build_meeting_insert_table ####################

if ($helper_type == "build_meeting_insert_table") {

    $meeting_date = $_POST['meeting_date'];

    // get the governors in office at $meeting_date

    $sql = "SELECT
                governor_id,
                governor_first_names,
                governor_surname
            FROM
                governors
            WHERE
                school_id = '$school_id' AND
                governor_appointment_date <= 'meeting_date' AND
                DATE_ADD(governor_appointment_date, INTERVAL governor_term_of_office YEAR) >='$meeting_date'
                ORDER BY governor_id ASC;";

    $result = sql_result_for_location($sql, 21);

    $governors = array();

    while ($row = mysqli_fetch_array($result)) {

        $governor_id = $row['governor_id'];
        $governor_first_names = $row['governor_first_names'];
        $governor_surname = $row['governor_surname'];
        $governor_name = "$governor_first_names $governor_surname";
        $governors[] = ['governor_id' => $governor_id, 'governor_name' => $governor_name];
    }

// Build the update table as pairs of governor names and associated attendance checkboxes


    $return = "

<h2 style='text-align: center;'>Meeting and Attendance Insert Form</h2>

<div style = 'width: 50%; padding: 5vh; margin: 5vh auto 0 auto; background: white;'>
    <form id='meetinginsertdata' method = 'POST'>
        <div style = 'margin-top: 2vh; text-align: center;'>
                <p style= 'display: inline-block; width: 15rem; text-align: center;'></p>
                <p style= 'display: inline-block; width: 10rem; text-align: center; font-weight: bold;'>$meeting_date</p>
        </div>";

    for ($i = 0; $i < count($governors); $i++) {

        $governor_id = $governors[$i]['governor_id'];
        $governor_name = $governors[$i]['governor_name'];

        $return .= "
            <div style= 'text-align: center;'>
                <p style= 'display: inline-block; width: 15rem; text-align: center;'>$governor_name</p>
                <span class = 'governor' style ='display: none;'>$governor_id</span>
                <p style= 'display: inline-block; width: 10rem; text-align: center;'>
                    <input class = 'attendancecheckbox' type='checkbox' id='newmeetingcheckbox$governor_id' name='newmeetingcheckbox%$governor_id%$meeting_date'
                        title = 'Check this box to signify attendance'>
                </p>
            </div>";
    }

    // add an insert button

    $return .= "
    </form>
    <div style= 'text-align: center;'>
        <p style= 'display: inline-block; width: 15rem; text-align: center;'></p>
        <p style= 'display: inline-block; width: 10rem; text-align: center;'>
            <button type='button' class='btn-sm btn-primary'
                title='Insert this meeting record'
                onmousedown='insertMeeting(\"$meeting_date\");'>Insert
            </button>
            <button type='button' class='btn-sm btn-primary ml-4'
                title='Cancel the insert'
                onmousedown='displayMeetingsScreen(\"\", \"\", \"\", \"\");'>Cancel
            </button>
        </p>
    </div>
</div>";

    echo $return;
}

#####################  insert_meeting ####################

if ($helper_type == "insert_meeting") {

    $meeting_date = $_POST['meeting_date'];
    $attendances_count = $_POST['attendances_count'];

    $attendances = array();
    $governors = array();
    $checkbox_valuees = array();

    for ($i = 0; $i < $attendances_count; $i++) {
        $attendances[$i] = $_POST["attendance_$i"];
        $pieces = explode("%", $attendances[$i]);
        $governors[$i] = $pieces[0];
        $checkbox_values[$i] = $pieces[1]; // checkbox has value "Y" if governor present, "N" otherwise
    }

    // first create the meeting record and then create governor_attendances

    $result = sql_result_for_location('START TRANSACTION', 22); // sql failure after this point will initiate rollback

    $sql = "INSERT INTO meetings (
                school_id,
                meeting_date,
                meeting_type)
            VALUES (
                '$school_id',
                '$meeting_date',
                '');";

    $result = sql_result_for_location($sql, 23);

    // and now the governor_attendances

    for ($i = 0; $i < $attendances_count; $i++) {

        $sql = "INSERT INTO governor_meeting_attendances (
                school_id,
                meeting_date,
                governor_id,
                governor_present)
            VALUES (
                '$school_id',
                '$meeting_date',
                '$governors[$i]',
                '$checkbox_values[$i]');";

        $result = sql_result_for_location($sql, 24);
    }

    $result = sql_result_for_location('COMMIT', 25);

    echo "Meeting inserted successfully";
}

#####################  test_for_unique_meeting_date ####################

if ($helper_type == "test_for_unique_meeting_date") {

    $meeting_date = $_POST['meeting_date'];

    $sql = "SELECT * FROM meetings
            WHERE
                school_id = '$school_id' AND
                meeting_date = '$meeting_date';";

    $result = sql_result_for_location($sql, 26);

    $row = mysqli_fetch_assoc($result);
    if (mysqli_num_rows($result) >= 1) {
        echo "duplicate";
    } else {
        echo "unique";
    }
}

#####################  update_meeting ####################

if ($helper_type == "update_meeting") {

    $meeting_date = $_POST['meeting_date'];
    $attendances_count = $_POST['attendances_count'];

    $attendances = array();
    $governors = array();
    $checkbox_valuees = array();

    for ($i = 0; $i < $attendances_count; $i++) {
        $attendances[$i] = $_POST["attendance_$i"];
        $pieces = explode("%", $attendances[$i]);
        $governors[$i] = $pieces[0];
        $checkbox_values[$i] = $pieces[1]; // checkbox has value "Y" if governor present, "N" otherwise
    }

    // modify governor_attendances

    $result = sql_result_for_location('START TRANSACTION', 27); // sql failure after this point will initiate rollback

    for ($i = 0; $i < $attendances_count; $i++) {

        $sql = "UPDATE governor_meeting_attendances
                SET
                    governor_present = '$checkbox_values[$i]'
                WHERE
                    school_id = '$school_id' AND
                    meeting_date = '$meeting_date'AND
                    governor_id = '$governors[$i]';";

        $result = sql_result_for_location($sql, 28);
    }

    $result = sql_result_for_location('COMMIT', 29);

    echo "Meeting updated successfully";
}

#####################  delete_meeting ####################

if ($helper_type == "delete_meeting") {

    $meeting_date = $_POST['meeting_date'];

    $result = sql_result_for_location('START TRANSACTION', 30); // sql failure after this point will initiate rollback

    $sql = "DELETE FROM meetings
            WHERE
                school_id = '$school_id' AND
                meeting_date = '$meeting_date';";

    $result = sql_result_for_location($sql, 31);


    $sql = "DELETE FROM governor_meeting_attendances
            WHERE
                meeting_date = '$meeting_date';";

    $result = sql_result_for_location($sql, 32);

    $result = sql_result_for_location('COMMIT', 33);

    echo "Deletion succeeded";
}

#####################  build_documents_update_table ####################

if ($helper_type == "build_documents_update_table") {

    $sql = "SELECT
                school_name
            FROM
                schools
            WHERE
                school_id = '$school_id'";

    $result = sql_result_for_location($sql, 34);

    $row = mysqli_fetch_array($result);
    $school_name = $row['school_name'];

    // curious feature of "html injection" is that descendant selector for table elements don't seem
    // to work unless the style rules are in the code you're injecting - clearer this way, anyway

    $return = "
    <style>

        tr.striped:nth-child(even) { /* used to stripe and space membership table rows */
            background-color: gainsboro;
        }

        th, td { /* th and td cells */
            padding: .5rem;
        }

    </style>

    <h2 style='text-align: center;'>Documents Store for $school_name</h2>
    <p id = 'messagearea' style = 'text-align: center; padding-top: .5vh; padding-bottom: .5vh; margin-top: 0; margin-bottom: 0;'></p>
    <div style='text-align: center; margin-bottom:4vh;'>
        <button id = 'insertbutton'  type='button' class='btn-sm btn-primary mb-2'
            title='Create a completely new Document'
            onmousedown='displayDocumentInsertScreen();'>Insert new Document
        </button>
    </div>";


    $return .= "
    <div style = 'width: 98%; padding: 1vh; margin-left:auto; margin-right: auto; background: white;'>
        <table style='width: 100%; padding: .5vh; margin-left:auto; margin-right: auto; background: white;'>
            <tr>
                <th style = 'width: 4%;'></th>
                <th style = 'width: 4%;'></th>
                <th style = 'width: 10%;'>Document Title</th>
                <th style='width: 5%; text-align: center; max-width: 5rem;'>Author</th>
                <th style='width: 3%; text-align: center;'>Version Number</th>
                <th style='width: 5%; text-align: center;'>Issue<br>Date</th>
                <th style='width: 5%; text-align: center;'>Last Review<br>Date</th>
                <th style='width: 7%;'></th>
                <th style='width: 3%;'></th>
                <th style='width: 3%;'></th>
            </tr>";

    // Tricky select here - we need to get the rows for the documents containing the highest version number for each
    // school_id/document_title combination. This is a classic sql problem - see
    // https://stackoverflow.com/questions/7745609/sql-select-only-rows-with-max-value-on-a-column

    $sql = "SELECT
                a.school_id,
                a.document_title,
                a.version_number,
                a.document_author,
                a.document_issue_date,
                a.version_last_review_date
            FROM documents a
            INNER JOIN (
            SELECT
		school_id,
                document_title,
                MAX(version_number) as version_number
            FROM documents
            WHERE school_id = '$school_id'
            GROUP BY school_id, document_title) b ON
                a.school_id = b.school_id AND
                a.document_title = b.document_title AND
                a.version_number = b.version_number
            ORDER BY a.document_title ASC";

    $result = sql_result_for_location($sql, 35);

    $i = 0;

    while ($row = mysqli_fetch_array($result)) {

        $i++;

        $document_title = $row['document_title'];
        $document_author = $row['document_author'];
        $max_version_number = $row['version_number'];
        $document_issue_date = $row['document_issue_date'];
        $version_last_review_date = $row['version_last_review_date'];

        $return .= "
                <tr id = 'row$i' class = 'striped'>";

        $return .= build_documents_update_table_row(
                $i, $school_id, $document_title, $max_version_number, $max_version_number, $document_author, $document_issue_date, $version_last_review_date);

        $return .= "
                </tr>";
    }

    $return .= "
        </table>
    </div>";

    echo $return;
}

#####################  build_document_insert_screen ####################

if ($helper_type == "build_document_insert_screen") {

    $return = "

<h2 style='text-align: center;'>New Document Input Form</h2>

<div style = 'width: 90%; padding: 5vh; margin: 5vh auto 0 auto; background: white;'>";

    // position a "Back" button at top right

    $return .= "

    <div style='display: flex; justify-content: center; position: relative;'>
            <button id = 'backbutton'  type='button' class='btn-sm btn-primary' style='position: absolute; right: 0;'
            title='Return to the Documents screen'
            onmousedown='displayDocumentsScreen(\"\");'>Back
        </button>
    </div>

    <form id='documentdata' method = 'POST'>

        <label for='documenttitle'>Document Title : </label>
        <input type='text' id ='documenttitle' name ='documenttitle' maxlength=40 size=40
               autocomplete='off' value=''
               title='Enter a title for the document'>
        <p id = 'documenttitleerror'></p>

        <label for='documentauthor'>Author : </label>
        <input type='text' id ='documentauthor' name ='documentauthor' maxlength=20 size=20
               autocomplete='off' value=''
               title='Enter the name of the author of the document'>
        <p id = 'documentauthorerror'></p>

        <label for='documentissuedate'>Document Issue Date : </label>
        <input type='text' id ='documentissuedate' name ='documentissuedate' size=10
               autocomplete='off' value=''
               title='Enter the issue date for the document'
               onmousedown='applyDatepicker(\"documentissuedate\");'>
        <p id = 'documentissuedateeerror'></p>

        <div>
            <label for='documentsourcefilename'>Source filename : </label>
            <input type='file' id='documentsourcefilename' name='documentsourcefilename'
                    accept='.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    title = 'Select a Word-processor source filename'>
            <p id = 'documentsourcefilenameerror'></p>
        </divp>

        <div>
            <label for='documentpdffilename'>Accomanying pdf filename : </label>
            <input type='file' id='documentpdffilename' name='documentpdffilename'
                    accept='.pdf'
                    title = 'Select a pdf copy of source filename'>
            <p id = 'documentpdffilenameerror'></p>
        </div>

        </form>

    <div id = 'builddocumentbuttons' style = 'width: 90%; margin: 4vh auto 2vh auto; text-align: center;'>
        <button id = 'insertbutton'  type='button' class='btn-sm btn-primary' style = 'margin-left: 2vw;'
                title='Insert a record for this Document'
                onmousedown='insertDocument();'>Insert
        </button>
        <button id = 'cancelbutton'  type='button' class='btn-sm btn-primary' style = 'margin-left: 2vw;'
                title='Cancel the insert'
                onmousedown='displayDocumentsScreen(\"Insert cancelled\");'>Cancel
        </button>
    </div>

</div>";

    echo $return;
}

#####################  insert_document ####################

if ($helper_type == "insert_document") {

    // if you find you've already got a document with the given title, just create a new
    // version for it - note there's a specific button for creating a new version

    $document_title = $_POST['documenttitle'];
    $document_author = $_POST['documentauthor'];
    $document_issue_date = $_POST['documentissuedate'];
    $version_creation_date = date("Y-m-d");
    $version_last_review_date = $version_creation_date;

    // get the current highest version number for school_id and document_title

    $sql = "SELECT
                max(version_number)
            FROM documents
            WHERE
                school_id = '$school_id' AND
                document_title = '$document_title';";

    $result = sql_result_for_location($sql, 36);
    $row = mysqli_fetch_assoc($result);
    if (mysqli_num_rows($result) >= 1) {
        $version_number = $row['max(version_number)'] + 1;
    } else {
        $version_number = 1;
    }

    $result = sql_result_for_location('START TRANSACTION', 37); // sql failure after this point will initiate rollback

    $sql = "INSERT INTO documents (
                school_id,
                document_title,
                version_number,
                document_author,
                document_issue_date,
                version_creation_date,
                version_last_review_date)
            VALUES (
                '$school_id',
                '$document_title',
                '$version_number',
                '$document_author',
                '$document_issue_date',
                '$version_creation_date',
                '$version_last_review_date');";

    $result = sql_result_for_location($sql, 38);

    // get the file names

    $document_source_name = $_FILES["documentsourcefilename"]["name"];
    $source_pieces = explode(".", $document_source_name);
    $document_pdf_filename = $_FILES["documentpdffilename"]["name"];
    $pdf_pieces = explode(".", $document_pdf_filename);

    // upload the pair - not too much to worry about if anything fails as the database
    // is the ultimate source for what's what and if we find ourselves writing to a
    // location that is already occupied, the old content will just get overwritten.
    // Also, since this is, by definition, the latest version of the document, create
    // a "LATEST" copy of it also, overwriting anything that might be there alredy

    $upload_source_target = "../school_$school_id/$document_title" . "_VERSION_$version_number.$source_pieces[1]";
    $upload_pdf_target_1 = "../school_$school_id/$document_title" . "_VERSION_$version_number.pdf";
    $upload_pdf_target_2 = "../school_$school_id/$document_title" . "_LATEST.pdf";

    if (!move_uploaded_file($_FILES['documentsourcefilename'] ['tmp_name'], $upload_source_target) ||
            !move_uploaded_file($_FILES['documentpdffilename'] ['tmp_name'], $upload_pdf_target_1)) {
        echo "Oops - Document upload %failed% in 'insert_document' of manager_helpers";
        $result = sql_result_for_location('ROLLBACK', 39);
        exit(1);
    }

    // copy to latest (can't use move_uploaded again - you've 'used tmp_name up')

    if (!copy($upload_pdf_target_1, $upload_pdf_target_2)) {
        echo "Oops - Document copy %failed% in 'insert_document' of manager_helpers";
        $result = sql_result_for_location('ROLLBACK', 40);
        exit(1);
    }

    $result = sql_result_for_location('COMMIT', 41);
}

#####################  build_version_insert_screen ####################

if ($helper_type == "build_version_insert_screen") {

    $document_title = $_POST['document_title'];

    $return = "

<h2 style='text-align: center;'>New Version Input Form<br>For Document : $document_title </h2>
<div style = 'width: 90%; padding: 5vh; margin: 5vh auto 0 auto; background: white;'>";

    // position a "Back" button at top right

    $return .= "

    <div style='display: flex; justify-content: center; position: relative;'>
            <button id = 'backbutton'  type='button' class='btn-sm btn-primary' style='position: absolute; right: 0;'
            title='Return to the Documents screen'
            onmousedown='displayDocumentsScreen(\"\");'>Back
        </button>
    </div>

    <form id='versiondata' method = 'POST'>

        <label for='documentauthor'>Author : </label>
        <input type='text' id ='documentauthor' name ='documentauthor' maxlength=20 size=20
               autocomplete='off' value=''
               title='Enter the name of the author of the document'>
        <p id = 'documentauthorerror'></p>

        <label for='documentissuedate'>Document Issue Date : </label>
        <input type='text' id ='documentissuedate' name ='documentissuedate' size=10
               autocomplete='off' value=''
               title='Enter the issue date for the document'
               onmousedown='applyDatepicker(\"documentissuedate\");'>
        <p id = 'documentissuedateeerror'></p>

        <div>
            <label for='documentsourcefilename'>Source filename : </label>
            <input type='file' id='documentsourcefilename' name='documentsourcefilename'
                    accept='.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    title = 'Select a Word-processor source filename'>
            <p id = 'documentsourcefilenameerror'></p>
        </divp>

        <div>
            <label for='documentpdffilename'>Accomanying pdf filename : </label>
            <input type='file' id='documentpdffilename' name='documentpdffilename'
                    accept='.pdf'
                    title = 'Select a pdf copy of source filename'>
            <p id = 'documentpdffilenameerror'></p>
        </div>

        </form>

    <div id = 'builddocumentbuttons' style = 'width: 90%; margin: 4vh auto 2vh auto; text-align: center;'>
        <button id = 'insertbutton'  type='button' class='btn-sm btn-primary' style = 'margin-left: 2vw;'
                title='Insert a record for this Document'
                onmousedown='insertVersion(\"$document_title\");'>Insert
        </button>
        <button id = 'cancelbutton'  type='button' class='btn-sm btn-primary' style = 'margin-left: 2vw;'
                title='Cancel the insert'
                onmousedown='displayDocumentsScreen(\"Insert cancelled\");'>Cancel
        </button>
    </div>

</div>";

    echo $return;
}

#####################  insert_version ####################

if ($helper_type == "insert_version") {

    $document_title = $_POST['document_title'];
    $document_author = $_POST['documentauthor'];
    $document_issue_date = $_POST['documentissuedate'];
    $version_creation_date = date("Y-m-d");
    $version_last_review_date = $version_creation_date;

    // get the current highest version number for school_id and document_title

    $sql = "SELECT
                max(version_number)
            FROM documents
            WHERE
                school_id = '$school_id' AND
                document_title = '$document_title'
            GROUP BY document_title;";

    $result = sql_result_for_location($sql, 42);
    $row = mysqli_fetch_assoc($result);
    $version_number = $row['max(version_number)'] + 1;


    $result = sql_result_for_location('START TRANSACTION', 43); // sql failure after this point will initiate rollback

    $sql = "INSERT INTO documents (
                school_id,
                document_title,
                version_number,
                document_author,
                document_issue_date,
                version_creation_date,
                version_last_review_date)
            VALUES (
                '$school_id',
                '$document_title',
                '$version_number',
                '$document_author',
                '$document_issue_date',
                '$version_creation_date',
                '$version_last_review_date');";

    $result = sql_result_for_location($sql, 44);

    // get the file names

    $document_source_name = $_FILES["documentsourcefilename"]["name"];
    $source_pieces = explode(".", $document_source_name);
    $document_pdf_filename = $_FILES["documentpdffilename"]["name"];
    $pdf_pieces = explode(".", $document_pdf_filename);

    // upload the pair - not too much to worry about if anything fails as the database
    // is the ultimate source for what's what and if we find ourselves writing to a
    // location that is already occupied, the old content will just get overwritten.
    // Also, since this is, by definition, the latest version of the document, create
    // a "LATEST" copy of it also, overwriting anything that might be there alredy

    $upload_source_target = "../school_$school_id/$document_title" . "_VERSION_$version_number.$source_pieces[1]";
    $upload_pdf_target_1 = "../school_$school_id/$document_title" . "_VERSION_$version_number.pdf";
    $upload_pdf_target_2 = "../school_$school_id/$document_title" . "_LATEST.pdf";


    if (!move_uploaded_file($_FILES['documentsourcefilename'] ['tmp_name'], $upload_source_target) ||
            !move_uploaded_file($_FILES['documentpdffilename'] ['tmp_name'], $upload_pdf_target_1)) {
        echo "Oops - Document upload %failed% in 'insert_version' of manager_helpers";
        $result = sql_result_for_location('ROLLBACK', 45);
        exit(1);
    }

    // copy to latest (can't use move_uploaded again - you've 'used tmp_name up')

    if (!copy($upload_pdf_target_1, $upload_pdf_target_2)) {
        echo "Oops - Document copy %failed% in 'insert_version' of manager_helpers";
        $result = sql_result_for_location('ROLLBACK', 46);
        exit(1);
    }

    $result = sql_result_for_location('COMMIT', 47);

    echo "upload succeedeed";
}

#####################  reset_documents_update_table_row ####################

if ($helper_type == "reset_documents_update_table_row") {

    $row_number = $_POST['row_number'];
    $document_title = $_POST['document_title'];
    $version_number = $_POST['version_number'];
    $max_version_number = $_POST['max_version_number'];

    $sql = "SELECT
                document_author,
                document_issue_date,
                version_last_review_date
            FROM documents
            WHERE
                school_id = '$school_id' AND
                document_title = '$document_title' AND
                version_number = '$version_number';";

    $result = sql_result_for_location($sql, 48);
    $row = mysqli_fetch_assoc($result);

    $document_author = $row['document_author'];
    $document_issue_date = $row['document_issue_date'];
    $version_last_review_date = $row['version_last_review_date'];

    $return = build_documents_update_table_row(
            $row_number, $school_id, $document_title, $version_number, $max_version_number, $document_author, $document_issue_date, $version_last_review_date);

    echo $return;
}

#####################  build_document_review_screen ####################

if ($helper_type == "build_document_review_screen") {

    $document_title = $_POST['document_title'];
    $max_version_number = $_POST['max_version_number'];

    // get the title and latest version of the document and display a screen to allow you to change the
    // author, issue_date and review date

    $sql = "SELECT
                document_author,
                document_issue_date,
                version_last_review_date
            FROM documents
            WHERE
                school_id = '$school_id' AND
                document_title = '$document_title' AND
                version_number = '$max_version_number';";

    $result = sql_result_for_location($sql, 49);

    $row = mysqli_fetch_assoc($result);
    $document_author = $row['document_author'];
    $document_issue_date = $row['document_issue_date'];
    $version_last_review_date = $row['version_last_review_date'];


    $return = "

<h2 style='text-align: center;'>Update for latest version of<br>Document : $document_title</h2>

<div style = 'width: 90%; padding: 5vh; margin: 5vh auto 0 auto; background: white;'>";

    // position a "Back" button at top right

    $return .= "

    <div style='display: flex; justify-content: center; position: relative;'>
            <button id = 'backbutton'  type='button' class='btn-sm btn-primary' style='position: absolute; right: 0;'
            title='Return to the Documents screen'
            onmousedown='displayDocumentsScreen(\"\");'>Back
        </button>
    </div>

    <form id='documentdata' method = 'POST'>

        <label for='documentauthor'>Author : </label>
        <input type='text' id ='documentauthor' name ='documentauthor' maxlength=20 size=20
               placeholder = '$document_author'
               value = '$document_author'
               autocomplete='off' value=''
               title='Enter the name of the author of the document'>
        <p id = 'documentauthorerror'></p>

        <label for='documentissuedate'>Document Issue Date : </label>
        <input type='text' id ='documentissuedate' name ='documentissuedate' size=10
               placeholder = '$document_issue_date'
               value = '$document_issue_date'
               autocomplete='off' value=''
               title='Enter the issue date for the document'
               onmousedown='applyDatepicker(\"documentissuedate\");'>
        <p id = 'documentissuedateeerror'></p>

        <label for='versionlastreviewdate'>Last Review Date : </label>
        <input type='text' id ='versionlastreviewdate' name ='versionlastreviewdate' size=10
               placeholder = '$version_last_review_date'
               value = '$version_last_review_date'
               autocomplete='off' value=''
               title='The date on which this document was last reviewed'
               onmousedown='applyDatepicker(\"versionlastreviewdate\");'>
        <p id = 'versionlastreviewdateerror'></p>

    </form>

    <div id = 'builddocumentbuttons' style = 'width: 90%; margin: 4vh auto 2vh auto; text-align: center;'>
        <button id = 'updatebutton'  type='button' class='btn-sm btn-primary' style = 'margin-left: 2vw;'
                title='Insert a record for this Document'
                onmousedown='updateDocument($school_id, \"$document_title\", $max_version_number);'>Update
        </button>
        <button id = 'cancelbutton'  type='button' class='btn-sm btn-primary' style = 'margin-left: 2vw;'
                title='Cancel the insert'
                onmousedown='displayDocumentsScreen(\"Insert cancelled\");'>Cancel
        </button>
    </div>

</div>";

    echo $return;
}

#####################  update_document ####################

if ($helper_type == "update_document") {

    $document_title = $_POST['document_title'];
    $document_author = $_POST['document_author'];
    $document_issue_date = $_POST['document_issue_date'];
    $max_version_number = $_POST['max_version_number'];

    $version_last_review_date = $_POST['version_last_review_date'];

    $sql = "UPDATE documents
            SET
                document_author = '$document_author',
                document_issue_date = '$document_issue_date',
                version_last_review_date = '$version_last_review_date'
            WHERE
                school_id = '$school_id' AND
                document_title = '$document_title' AND
                version_number = '$max_version_number';";

    $result = sql_result_for_location($sql, 50);

    echo 'document updated';
}

#####################  delete_document ####################

if ($helper_type == "delete_document") {

    $document_title = $_POST['document_title'];
    $max_version_number = $_POST['max_version_number'];

    $result = sql_result_for_location('START TRANSACTION', 61); // sql failure after this point will initiate rollback
    // get the title, latest version_number and owner school for this document (seems safer than
    // passing as parameters)

    $sql = "DELETE FROM documents
            WHERE
                school_id = '$school_id' AND
                document_title = '$document_title';";

    $result = sql_result_for_location($sql, 52);

// now delete all versions of the document from the folder for school $school_id. Note
// deleted files will still be in server trash for a while

    for ($i = 1; $i <= $max_version_number; $i++) { // grr - can't just delete ".*"
        $files = glob("../documents/school_$i/" . $document_title . "_VERSION_$i.*"); // same as scandir but doesn't get the "." and .."
        foreach ($files as $file) {
            if (!unlink($file)) {
                echo "Oops! delete %%failed%% in delete_document.";
                $result = sql_result_for_location('ROLLBACK', 53);
                exit(1);
            }
        }
    }

    $result = sql_result_for_location('COMMIT', 54);

    echo "Deletion succeeded";
}

disconnect_from_database();
