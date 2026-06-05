<?php
/**
 * BantayPurrPaws — Adoption Submit API
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/adoption.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/sensitive-data.php';

header('Content-Type: application/json');
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$user  = currentUser();
$petId = (int) ($_POST['pet_id'] ?? 0);

// ← CHANGED: removed $db arguments
if (!$petId || !petCanReceiveApplications($petId)) {
    echo json_encode(['success' => false, 'message' => 'This pet is not available for adoption.']);
    exit;
}

if (userHasPendingApplication((int) $user['id'], $petId)) {
    echo json_encode(['success' => false, 'message' => 'You already have a pending application for this pet.']);
    exit;
}

// Sanitize inputs
$fullName   = sanitize(trim($_POST['full_name']           ?? ''));
$contactRaw = trim($_POST['contact_number']               ?? '');
$email      = sanitize(trim($_POST['email']               ?? ''));
$address    = sanitize(trim($_POST['address']             ?? ''));
$occupation = sanitize(trim($_POST['occupation']          ?? ''));
$reason     = sanitize(trim($_POST['reason_for_adoption'] ?? ''));
$homeType   = sanitize(trim($_POST['home_type']           ?? ''));
$existing   = strtolower(sanitize(trim($_POST['existing_pets'] ?? '')));
$agreement  = isset($_POST['agreement']);

// Validate
$errors = [];
if ($fullName === '')   $errors[] = 'Full name is required.';

$phoneCheck = validatePhoneNumber($contactRaw);
if (!$phoneCheck['ok']) {
    $errors[] = $phoneCheck['error'];
} else {
    $contact = $phoneCheck['value'];
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
if ($address === '')    $errors[] = 'Complete address is required.';
if ($occupation === '') $errors[] = 'Occupation is required.';
if ($reason === '')     $errors[] = 'Reason for adoption is required.';
if ($homeType === '')   $errors[] = 'Type of home is required.';
if (!in_array($existing, ['yes', 'no'], true)) $errors[] = 'Please specify if you have existing pets.';
if (!$agreement)        $errors[] = 'You must agree to the adoption terms.';

if ($errors) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// ← CHANGED: removed $db argument
$pet = getPetById($petId);

try {
    // ← CHANGED: replaced PDO beginTransaction + prepare + execute + lastInsertId
    //            with a single db_insert() call
    $application = db_insert('adoption_applications', [
        'pet_id'              => $petId,
        'user_id'             => $user['id'],
        'full_name'           => $fullName,
        'contact_number'      => protectSubmissionPhone($contact),
        'email'               => protectSubmissionEmail($email),
        'address'             => $address,
        'occupation'          => $occupation,
        'reason_for_adoption' => $reason,
        'home_type'           => $homeType,
        'existing_pets'       => $existing,
        'agreement'           => true,
        'status'              => 'pending',
    ]);

    if (!$application) {
        throw new RuntimeException('Insert returned null.');
    }

    $applicationId = (int) $application['id'];

    // ← CHANGED: removed $db argument
    createAdoptionNotification($applicationId, $fullName, $pet['name']);

    echo json_encode([
        'success' => true,
        'message' => 'Your adoption application has been submitted! Our team will review it shortly.',
    ]);

} catch (Throwable $e) {
    require_once __DIR__ . '/../includes/logger.php';
    bpp_log('adoption', 'error', 'Application submit failed.', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not submit application. Please try again.']);
}
