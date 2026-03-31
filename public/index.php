<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Home - Public landing page.
 */

$pageTitle = 'Home';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <?= renderFlash() ?>

    <section class="hero">
        <h1>Simple CRM for Small Businesses</h1>
        <p>Manage your leads, contacts, companies, deals, and activities all in one place.
           Track every interaction, streamline your sales workflow, and close more deals.</p>
        <div class="hero-actions">
            <?php if (isLoggedIn()): ?>
                <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                <a href="leads.php" class="btn btn-secondary">Browse Leads</a>
            <?php else: ?>
                <a href="signup.php" class="btn btn-primary">Get Started Free</a>
                <a href="leads.php" class="btn btn-secondary">Browse Leads</a>
            <?php endif; ?>
        </div>
    </section>

    <section class="features-grid">
        <div class="feature-card">
            <h3>Lead Management</h3>
            <p>Track every lead from first contact to closed deal with a clear workflow pipeline.</p>
        </div>
        <div class="feature-card">
            <h3>Contact &amp; Company Database</h3>
            <p>Keep all your contacts and companies organized with detailed records and relationships.</p>
        </div>
        <div class="feature-card">
            <h3>Deal Tracking</h3>
            <p>Monitor deal stages, amounts, and progress to forecast revenue accurately.</p>
        </div>
        <div class="feature-card">
            <h3>Activity Planning</h3>
            <p>Schedule calls, meetings, emails, and tasks. Never miss a follow-up again.</p>
        </div>
        <div class="feature-card">
            <h3>Team Collaboration</h3>
            <p>Add notes and comments to any record. Keep your whole team on the same page.</p>
        </div>
        <div class="feature-card">
            <h3>Real-time Notifications</h3>
            <p>Get notified when leads are assigned, statuses change, or teammates leave comments.</p>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
