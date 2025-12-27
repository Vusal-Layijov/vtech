<?php
header('Content-Type: application/json');

// Check if form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Configuration
$to_email = 'info@solaxay.com';
$from_email = 'noreply@solaxay.com';
$from_name = 'Solaxay Handyman Website';

// Sanitize and validate input data
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_phone($phone) {
    return preg_match('/^[\+]?[1-9][\d]{0,15}$/', preg_replace('/[^\d+]/', '', $phone));
}

// Get form data
$name = sanitize_input($_POST['name'] ?? '');
$phone = sanitize_input($_POST['phone'] ?? '');
$email = sanitize_input($_POST['email'] ?? '');
$service = sanitize_input($_POST['service'] ?? '');
$address = sanitize_input($_POST['address'] ?? '');
$preferred_date = sanitize_input($_POST['preferred_date'] ?? '');
$preferred_time = sanitize_input($_POST['preferred_time'] ?? '');
$description = sanitize_input($_POST['description'] ?? '');

// Validation
$errors = [];

if (empty($name)) {
    $errors[] = 'Name is required';
}

if (empty($phone)) {
    $errors[] = 'Phone number is required';
} elseif (!validate_phone($phone)) {
    $errors[] = 'Please provide a valid phone number';
}

if (empty($email)) {
    $errors[] = 'Email is required';
} elseif (!validate_email($email)) {
    $errors[] = 'Please provide a valid email address';
}

if (empty($service)) {
    $errors[] = 'Service type is required';
}

if (empty($address)) {
    $errors[] = 'Service address is required';
}

if (empty($preferred_date)) {
    $errors[] = 'Preferred date is required';
}

if (empty($preferred_time)) {
    $errors[] = 'Preferred time is required';
}

if (empty($description)) {
    $errors[] = 'Project description is required';
}

// If there are validation errors, return them
if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Handle file uploads
$uploaded_files = [];
$upload_dir = 'uploads/';

// Create uploads directory if it doesn't exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if (isset($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
    $file_count = count($_FILES['photos']['name']);
    
    for ($i = 0; $i < $file_count; $i++) {
        if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
            $file_name = $_FILES['photos']['name'][$i];
            $file_tmp = $_FILES['photos']['tmp_name'][$i];
            $file_size = $_FILES['photos']['size'][$i];
            $file_type = $_FILES['photos']['type'][$i];
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!in_array($file_type, $allowed_types)) {
                continue; // Skip non-image files
            }
            
            // Validate file size (5MB max)
            if ($file_size > 5 * 1024 * 1024) {
                continue; // Skip files larger than 5MB
            }
            
            // Generate unique filename
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_filename = date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $uploaded_files[] = [
                    'original_name' => $file_name,
                    'file_path' => $upload_path,
                    'file_size' => $file_size
                ];
            }
        }
    }
}

// Create email content
$subject = 'New Booking Request - Solaxay Handyman';

$message_body = "
<html>
<head>
    <title>New Booking Request</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header { background-color: #fde6bf; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .info-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .info-table td { padding: 10px; border-bottom: 1px solid #eee; }
        .label { font-weight: bold; color: #0b354e; }
        .files { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class='header'>
        <h2 style='color: #0b354e; margin: 0;'>🔧 New Booking Request</h2>
        <p style='color: #f19d2e; margin: 10px 0 0 0;'>Solaxay Handyman</p>
    </div>
    
    <div class='content'>
        <p>You have received a new booking request through your website. Here are the details:</p>
        
        <table class='info-table'>
            <tr>
                <td class='label'>Customer Name:</td>
                <td>$name</td>
            </tr>
            <tr>
                <td class='label'>Phone:</td>
                <td><a href='tel:$phone'>$phone</a></td>
            </tr>
            <tr>
                <td class='label'>Email:</td>
                <td><a href='mailto:$email'>$email</a></td>
            </tr>
            <tr>
                <td class='label'>Service Type:</td>
                <td>" . ucfirst($service) . "</td>
            </tr>
            <tr>
                <td class='label'>Service Address:</td>
                <td>$address</td>
            </tr>
            <tr>
                <td class='label'>Preferred Date:</td>
                <td>$preferred_date</td>
            </tr>
            <tr>
                <td class='label'>Preferred Time:</td>
                <td>$preferred_time</td>
            </tr>
            <tr>
                <td class='label'>Project Description:</td>
                <td>$description</td>
            </tr>
        </table>";

if (!empty($uploaded_files)) {
    $message_body .= "
        <div class='files'>
            <h3>📷 Uploaded Photos:</h3>
            <ul>";
    
    foreach ($uploaded_files as $file) {
        $file_size_kb = round($file['file_size'] / 1024, 2);
        $message_body .= "<li>{$file['original_name']} ({$file_size_kb} KB)</li>";
    }
    
    $message_body .= "
            </ul>
            <p><small>Files are saved on the server in the uploads folder.</small></p>
        </div>";
}

$message_body .= "
        <div style='background-color: #e7f3ff; padding: 15px; border-radius: 5px; margin-top: 20px;'>
            <h3>📋 Next Steps:</h3>
            <ol>
                <li>Contact the customer within 24 hours</li>
                <li>Provide a detailed estimate</li>
                <li>Schedule the appointment</li>
            </ol>
        </div>
        
        <p style='margin-top: 30px; color: #666; font-size: 14px;'>
            This email was sent from your website booking form at " . date('Y-m-d H:i:s') . "
        </p>
    </div>
</body>
</html>";

// Email headers
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= "From: $from_name <$from_email>" . "\r\n";
$headers .= "Reply-To: $email" . "\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// Send email
$mail_sent = mail($to_email, $subject, $message_body, $headers);

// Log the booking request (optional)
$log_entry = date('Y-m-d H:i:s') . " - New booking: $name ($email) - $service\n";
file_put_contents('booking_log.txt', $log_entry, FILE_APPEND | LOCK_EX);

// Return JSON response
if ($mail_sent) {
    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your booking request has been sent successfully. We will contact you within 24 hours with a quote and schedule.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Sorry, there was an error sending your request. Please call us directly at (412) 583-9593 or try again later.'
    ]);
}
?>