<?php
// Root entry point: Redirect to built offline frontend or serve directly
if (file_exists(__DIR__ . '/frontend/dist/index.html')) {
    header('Location: frontend/dist/');
    exit;
} else {
    echo "<h1>SIA High School Admission & Enrollment Portal</h1>";
    echo "<p>Please ensure you build the frontend using <code>npm run build</code> or access the development server on <code>http://localhost:5173</code>.</p>";
}
