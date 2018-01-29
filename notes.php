<?php
include_once 'data.php';
include_once 'functions.php';

if (!empty($_GET['file']))
    $_GET['file'] = intval($_GET['file']);
if (!empty($_POST['file']))
    $_POST['file'] = intval($_POST['file']);

database_connect(IL_DATABASE_PATH, 'library');

if (!empty($_POST['file'])) {
    update_notes($_POST['file'], $_POST['notes'], $dbHandle, boolval($_POST['public']));
    die();
}

if (isset($_GET['file'])) {

    $query = $dbHandle->quote($_GET['file']);
    if(boolval($_GET['public']))
        $user_query = 1;
    else
        $user_query = $dbHandle->quote($_SESSION['user_id']);

    $result = $dbHandle->query("SELECT title FROM library WHERE id=$query");
    $title = $result->fetchColumn();
    $result = null;

    $result = $dbHandle->query("SELECT notes FROM notes WHERE fileID=$query AND userID=$user_query LIMIT 1");
    $notes = $result->fetchColumn();
    $result = null;
}

$dbHandle = null;

if (isset($_GET['editnotes'])) {
    ?>
    <div style="width: 100%;height: 100%">
        <form method="post" action="notes.php" id="form-notes">
            <input type="hidden" name="file" value="<?php echo $_GET['file'] ?>">
            <input type="hidden" name="public" value="<?php echo $_GET['public'] ?>">
            <textarea id="notes" name="notes" rows="15" cols="65"><?php echo $notes; ?></textarea>
        </form>
    </div>
    <?php
} else {
    ?>
    <table cellspacing="0" width="100%">
        <tr>
            <td class="items alternating_row" style="border: 0px">
                <span class="titles"><?php echo htmlspecialchars($title) ?></span>
            </td>
        </tr>
    </table>
    <div style="padding:8px">
    <?php
    print $notes;
    if (empty($notes)) {
        if(boolval($_GET['public']))
            print '&nbsp;No public notes for this record.';
        else
            print '&nbsp;No private notes for this record.';
    }
    ?>
    </div>
<?php
}
?>