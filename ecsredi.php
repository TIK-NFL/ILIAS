<?php

$iliasini = parse_ini_file("ilias.ini.php", true);
#print_r($iliasini);
$iliasID = $iliasini['clients']['default'];
$iliasbaseurl = $iliasini['server']['http_path'];

if (null !== ($cms = filter_input(INPUT_GET, 'cmsid', FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE))) {

    $clientini = parse_ini_file("data/".$iliasID."/client.ini.php", true);
    $mysqli = new mysqli($clientini['db']['host'], $clientini['db']['user'], $clientini['db']['pass'], $clientini['db']['name']);
    if ($mysqli->connect_errno) {
        echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    }

    if (!($stmt = $mysqli->prepare("select ref_id from ecs_course_assignments as ecs, object_reference as objref ".  
                                   "where ecs.cms_id = (?) and objref.obj_id = ecs.obj_id "))) {
        echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
    }
    $id = $_GET['cmsid'];
    if (!$stmt->bind_param("i", $id)) {
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    if (!$stmt->execute()) {
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    }
    /* bind result variables */
    $stmt->bind_result($crs_obj);

    /* fetch value */
    $stmt->fetch();

    if( $crs_obj ) {
        header('Location: '.$iliasbaseurl.'/goto.php?target=crs_'.$crs_obj.'&client_id='.$iliasID  , true,  301);
    } else {
        header('Location: '.$iliasbaseurl , true,  301);
    }
    /* close statement */
    $stmt->close();
} else {
    header('Location: '.$iliasbaseurl, true,  301);
}
