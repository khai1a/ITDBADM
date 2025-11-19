<?php

$dbpath = dirname(__DIR__) . "/db_connect.php";
include($dbpath);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $redirectPage");
    exit;
}

$action = $_POST['action'] ?? '';
$entity = $_POST['entity'] ?? '';
$message = '';
$status = 'danger';

try {
    if ($entity === 'note' && $action === 'add') {
        $note_name = trim($_POST['note_name'] ?? '');

        if ($note_name === '') {
            $message = 'Note name is required.';
            $status  = 'danger';
        } else {
            $conn->query("SET @id = ''");
            $conn->query("CALL getLastNoteID(@id)");
            $note_ID = $conn->query("SELECT @id")->fetch_assoc()['@id'];

            $sql  = "INSERT INTO notes (note_ID, note_name) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $note_ID, $note_name);

            if ($stmt->execute()) {
                $message = "Note \"$note_name\" added successfully!";
                $status  = 'success';
            } else {
                $message = "Error adding note: " . $stmt->error;
                $status  = 'danger';
            }
            $stmt->close();
        }

    } elseif ($entity === 'note' && $action === 'delete') {
        $note_ID = $_POST['note_ID'] ?? '';

        if ($note_ID === '') {
            $message = 'No note ID specified.';
            $status  = 'danger';
        } else {
            $stmt = $conn->prepare("DELETE FROM notes WHERE note_ID = ?");
            $stmt->bind_param("s", $note_ID);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $message = "Note $note_ID deleted.";
                    $status  = 'success';
                } else {
                    $message = "Note $note_ID not found.";
                    $status  = 'warning';
                }
            } else {
                $message = "Error deleting note: " . $stmt->error;
                $status  = 'danger';
            }
            $stmt->close();
        }

    } elseif ($entity === 'accord' && ($action === 'create' || $action === 'add')) {
        $accord_name = trim($_POST['accord_name'] ?? '');

        if ($accord_name === '') {
            $message = 'Accord name is required.';
            $status  = 'danger';
        } else {
            $conn->query("SET @id = ''");
            $conn->query("CALL getLastAccordID(@id)");
            $note_ID = $conn->query("SELECT @id")->fetch_assoc()['@id'];

            $sql  = "INSERT INTO accords (accord_ID, accord_name) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $accord_ID, $accord_name);

            if ($stmt->execute()) {
                $message = "Accord \"$accord_name\" added successfully.";
                $status  = 'success';
            } else {
                $message = "Error adding accord: " . $stmt->error;
                $status  = 'danger';
            }
            $stmt->close();
        }

    } elseif ($entity === 'accord' && $action === 'delete') {
        $accord_ID = $_POST['accord_ID'] ?? '';

        if ($accord_ID === '') {
            $message = 'No accord ID specified.';
            $status  = 'danger';
        } else {
            $stmt = $conn->prepare("DELETE FROM accords WHERE accord_ID = ?");
            $stmt->bind_param("s", $accord_ID);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $message = "Accord $accord_ID deleted.";
                    $status  = 'success';
                } else {
                    $message = "Accord $accord_ID not found.";
                    $status  = 'warning';
                }
            } else {
                $message = "Error deleting accord: " . $stmt->error;
                $status  = 'danger';
            }
            $stmt->close();
        }

    } else {
        $message = 'Invalid action or entity.';
        $status = 'danger';
    }

} catch (Exception $e) {
    $message = "Unexpected error: " . $e->getMessage();
    $status = 'danger';
}

header("Location: admin_notes_accords.php?message=" . $message . "&status=" . $status);
exit;
