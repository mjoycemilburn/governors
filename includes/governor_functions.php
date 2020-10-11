<?php

function connect_to_database() {
    global $con, $url_root;

    if (($_SERVER['REMOTE_ADDR'] == '127.0.0.1' or $_SERVER['REMOTE_ADDR'] == '::1')) {
        $url_root = '../../';
    } else {
        $current_directory_root = $_SERVER['DOCUMENT_ROOT']; // one level above current directory
        // remove everything after and including "public_html"

        $pieces = explode('public_html', $current_directory_root);
        $url_root = $pieces[0];
    }

    require ($url_root . "connect_governors_db.php");
}

function disconnect_from_database() {
    global $con, $url_root;

    require ($url_root . "disconnect_governors_db.php");
}

function sql_result_for_location($sql, $location) {
    global $con, $url_root, $page_title;

    $result = mysqli_query($con, $sql);

    if (!$result) {
        echo "Oops - database access %failed%. in $page_title location $location. Error details follow : " . mysqli_error($con);
        
        $sql = "ROLLBACK";
        $result = mysqli_query($con, $sql);
        
        disconnect_from_database();
        exit(1);
    }

    return $result;
}

function get_governor_types() {
    global $con;

    $sql = "SELECT
                    governor_type_code,
                    governor_type
                FROM
                    governor_types";

    $result = mysqli_query($con, $sql);

    if (!$result) {
        echo "Oops - database access %failed%. in get_governor_types function. Error details follow : " . mysqli_error($con);
                
        $sql = "ROLLBACK";
        $result = mysqli_query($con, $sql);
        
        disconnect_from_database();
        exit(1);
    }

    $governor_types = array();

    while ($row = mysqli_fetch_array($result)) {

        $governor_type_code = $row['governor_type_code'];
        $governor_type = $row['governor_type'];

        if ($governor_type_code != '') {
            $governor_types[$governor_type_code] = $governor_type;
        }
    }
    return $governor_types;
}

function get_governor_roles() {
    global $con;

    $sql = "SELECT
                    governor_role_code,
                    governor_role
                FROM
                    governor_roles";

    $result = mysqli_query($con, $sql);

    if (!$result) {
        echo "Oops - database access %failed%. in get_governor_roles function. Error details follow : " . mysqli_error($con);
                
        $sql = "ROLLBACK";
        $result = mysqli_query($con, $sql);
        
        disconnect_from_database();
        exit(1);
    }

    $governor_roles = array();

    while ($row = mysqli_fetch_array($result)) {

        $governor_role_code = $row['governor_role_code'];
        $governor_role = $row['governor_role'];

        if ($governor_role_code != '') {
            $governor_roles[$governor_role_code] = $governor_role;
        }
    }
    return $governor_roles;
}

function prepareStringforXMLandJSONParse($input) {

    # < , > and & must be turned into &lt; , &gt; and &amp; to get them through an XML return
    # " and line feeds (\n) must be turned into \\" and \\n to make them acceptable to JSON.Parse
    # &nbsp; must be turned in " "
    # &quot; must be turned into "'"
    # 
    # maybe should consider encodeURIComponent  see https://stackoverflow.com/questions/20960582/html-string-nbsp-breaking-json
    # For JSON syntax see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/JSON
    # Not clear from this why &nbsp; is breaking the return of the JSON - basically empty  - for 
    # further info on URL encoding see https://www.urlencoder.io/learn/
    # 
    # You might have thought we would do this at the outset before these characters reached "helpers"
    # but the problem is that escaped strings get unescaped when they're stored on the database. The
    # "tag" characters < and > could probably have been dealt with at the outset, but it seems better
    # to keep things together

    $output = $input;

    $output = str_replace('&', '&amp;', $output); ## haha - best do this first eh!!
    $output = str_replace('<', '&lt;', $output);
    $output = str_replace('>', '&gt;', $output);


    $output = str_replace('"', '\\"', $output);
    $output = str_replace('&nbsp;', ' ', $output);
    $output = str_replace('&quot;', "'", $output);

    return $output;
}

function build_documents_update_table_row(
        $row_number, $school_id, $document_title, $version_number, $max_version_number, $document_author, $document_issue_date, $version_last_review_date) {

    // Build a documents_update_table row for $school_id, $document_title and $version_number in $row_number
    // $max_version_number is the highest version number currently defined for $school_id and $document_title

    if ($version_number == $max_version_number) {
        $pdf_weblink = "school_$school_id/$document_title" . "_LATEST.pdf";
    } else {
        $pdf_weblink = "school_$school_id/$document_title" . "_VERSION_$version_number.pdf";
    }

    $src_weblink = '';

    // Get the address of the source for this document version. Since we're not recording its
    // extension in the database it seems better just to look at what's actually on the server - 
    // ie return whatever comes up first with required title and version that's not a pdf file

    if ($files = scandir("../school_$school_id/")) {
        for ($i = 0; $i < count($files); $i++) {

            if ($files[$i] != "." && $files[$i] != "..") {
                $pieces = explode(".", $files[$i]);
                if ($pieces[0] == "$document_title" . "_VERSION_$version_number" &&
                        $pieces[1] != "pdf") {
                    $src_weblink = "school_$school_id/$document_title" . "_VERSION_$version_number.$pieces[1]";
                    break;
                }
            }
        }
    } else {
        echo "Oops! scandir %%failed%% in build_documents_update_table_row function.";
    }

    $return = "
        <td>
            <button type='button' class='btn-sm btn-primary'
                title='Display the pdf file for this version of the document'
                onmousedown='grabClipboardLink(\"$pdf_weblink\");'>Get pdf
            </button>
        </td>
        <td>
            <button type='button' class='btn-sm btn-primary'
                title='Download a copy of the source file for this version of the document'
                onmousedown='downloadDocument(\"$src_weblink\");'>Get source
            </button>
        </td>                

        <td id = 'title$row_number'>$document_title</td>  
        <td style='text-align: center;'>$document_author</td>
        <td style='text-align: center;'>
            <select name='versions$row_number' id='versions$row_number'
                onchange = 'resetDocumentsUpdateTableRow($row_number, $max_version_number);'>";

    for ($i = $max_version_number; $i >= 1; $i--) {
        if ($i == $version_number) {
            $return .= "<option value='$i' selected>$i</option>";
        } else {
            $return .= "<option value='$i'>$i</option>";
        }
    }
    $return .= "
            </select>
        </td>

        <td style='text-align: center;'>$document_issue_date</td>  
        <td style='text-align: center;'>$version_last_review_date</td>
            
         <td>
            <button type='button' class='ml-2 btn-sm btn-primary'
                title='Add a new version for this document to the store'
                onmousedown='displayVersionInsertScreen(\"$document_title\");'>Add New Vn
            </button>
        </td>";
    ;

    if ($version_number == $max_version_number) {
        $return .= "
        <td>
            <button type='button' class='ml-2 btn-sm btn-primary'
                title='Amend the author, issue date and review date fields for the latest version of this document'
                onmousedown='displayDocumentReviewScreen($school_id, \"$document_title\", $max_version_number);'>Amend
            </button>
        </td>";
    } else {
        $return .= "
        <td>
            <button type='button' class='btn-sm btn-primary' style='opacity: 0.5;'>Amend
            </button>
        </td>";
    }
    $return .= "
        <td>
            <button type='button' class='btn-sm btn-primary'
                title='delete this document'
                onmousedown='deleteDocument($school_id, \"$document_title\", $max_version_number);'>Delete
            </button>
        </td>";

    return $return;
}
