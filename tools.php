<?php
include_once 'data.php';
?>
<div class="leftindex" style="float:left;width:240px;height:100%;overflow:auto;margin:0px;padding:0px;border-right:1px solid #b5b6b8" id="tools-left">
    <button id="rtfscanlink">Citation Scan</button>
    <button id="citationstyleslink">Citation Styles</button>
    <?php
    if ($_SESSION['auth'] && $_SESSION['permissions'] == 'A') {
        ?>
    <button id="duplicateslink">Find Duplicates</button>
    <?php
    }
    ?>
    <button id="fontslink">Fonts & Colors</button>
    <?php
    if ((!isset($ini_array['remotesign']) || $ini_array['remotesign'] != 1) || ($_SESSION['auth'] && $_SESSION['permissions'] == 'A')) {
    ?>
    <button id="userslink">User Management</button>
    <?php
    }
    if ($_SESSION['auth'] && $_SESSION['permissions'] == 'A' && $hosted == false) {
        ?>
    <button id="backuplink">Backup / Restore</button>
    <button id="synclink">Synchronize</button>
    <?php
    }
    if ($_SESSION['auth'] && $_SESSION['permissions'] == 'A') {
    ?>
    <button id="renamejournallink">Manage Journals</button>
    <button id="renamecategorylink">Manage Categories</button>
    <?php
    }
    if ($_SESSION['auth'] && $_SESSION['permissions'] == 'A') {
        ?>
    <button id="detailslink">Installation Details</button>
    <?php
    }
    ?>
    <button id="settingslink">Settings</button>
    <?php
    if ($_SESSION['auth'] && $_SESSION['permissions'] == 'A') {
        ?>
    <button id="reindexlink">Batch PDF indexing</button>
    <?php
    }
    ?>
    <button id="aboutlink">About I, Librarian</button>
</div>
<div style="width:auto;height:100%;overflow:auto" id="right-panel"></div>