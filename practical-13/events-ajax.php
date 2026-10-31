<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
requireRole('admin');
$csrfToken = sh_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= sh_escape($csrfToken) ?>">
<title>Practical 13 | StudentHub</title>
<link rel="stylesheet" href="style.css"><link rel="stylesheet" href="auth.css"><link rel="stylesheet" href="events-ajax.css">
<script src="events-ajax.js" defer></script>
</head>
<body class="portal-page practical13-page">
<header class="p13-header"><p class="p13-kicker">STUDENTHUB · WEB DEVELOPMENT FRAMEWORKS</p><h1>Event Control Center</h1><p>Practical 13 · PHP JSON API and Fetch-Based CRUD</p></header>
<main class="p13-main">
<div class="p13-topbar"><div><a class="p13-back" href="admin_dashboard.php">← Admin Dashboard</a><h2>Manage Events</h2><p>Add, view, update and delete events without reloading this page.</p></div>
<form method="POST" action="logout.php" class="p13-logout"><input type="hidden" name="csrf_token" value="<?= sh_escape($csrfToken) ?>"><button type="submit">Logout</button></form></div>
<div id="apiMessage" class="p13-message" role="status" aria-live="polite" hidden></div>
<section class="p13-layout" aria-label="Event management">
<section class="p13-panel"><div class="p13-panel-heading"><span class="p13-step">01</span><div><h3 id="formHeading">Add a new event</h3><p>Enter the event information below.</p></div></div>
<form id="eventForm" class="p13-form"><input type="hidden" id="eventId" name="event_id">
<label for="title">Event title</label><input id="title" name="title" maxlength="150" required placeholder="Example: Tech Innovation Day">
<label for="description">Description</label><textarea id="description" name="description" rows="4" maxlength="5000" required placeholder="Describe the event..."></textarea>
<div class="p13-form-row"><div><label for="category">Category</label><input id="category" name="category" maxlength="80" required placeholder="Workshop"></div><div><label for="status">Status</label><select id="status" name="status"><option value="open">Open</option><option value="closed">Closed</option></select></div></div>
<label for="event_date">Date and time</label><input type="datetime-local" id="event_date" name="event_date" required>
<label for="location">Location</label><input id="location" name="location" maxlength="180" required placeholder="Event venue">
<div class="p13-form-actions"><button type="submit" id="saveButton" class="p13-primary">Add event</button><button type="button" id="cancelEdit" class="p13-secondary" hidden>Cancel edit</button></div>
<p class="p13-form-note">Changes are sent to the PHP API using Fetch. The page stays open.</p></form></section>
<section class="p13-panel p13-record-panel"><div class="p13-panel-heading"><span class="p13-step">02</span><div><h3>Event records</h3><p id="recordSummary">Loading records...</p></div><button type="button" id="refreshButton" class="p13-refresh" aria-label="Refresh event list">↻</button></div>
<div class="p13-filters"><label class="visually-hidden" for="searchEvents">Search events</label><input type="search" id="searchEvents" placeholder="Search title, description or location"><label class="visually-hidden" for="filterCategory">Filter by category</label><select id="filterCategory"><option value="">All categories</option></select><label class="visually-hidden" for="filterStatus">Filter by status</label><select id="filterStatus"><option value="">All statuses</option><option value="open">Open</option><option value="closed">Closed</option></select></div>
<div id="loadingState" class="p13-loading">Loading events from the database...</div><div id="eventList" class="p13-event-list" aria-live="polite"></div>
<div id="emptyState" class="p13-empty" hidden><span>✦</span><h4>No events found</h4><p>Add an event or change your search filters.</p></div></section></section>
<section class="p13-api-info"><div><span class="p13-api-dot"></span><strong>API connected</strong><span>PHP · MySQL · Fetch API</span></div><p>GET lists events · POST adds an event · PUT updates an event · DELETE removes an event</p></section>
</main><footer><p>&copy; 2026 StudentHub Portal · Practical 13</p></footer>
</body></html>
