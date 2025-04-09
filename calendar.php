<?php
// calendar.php – Enhanced Calendar Page with Full Monthly and Daily View
// Featuring an improved time slot table using semantic HTML
include("navbar.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Responsive design -->
  <title>Duques Hall Calendar</title>
  <!-- Consolidated site styles -->
  <link rel="stylesheet" href="css/style.css">
  <style>
    /* Monthly Calendar Styles */
    .monthly-calendar {
      max-width: 800px;
      margin: 20px auto 30px;
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 2px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .monthly-row {
      display: contents;
    }
    .monthly-cell {
      background: #fff;
      padding: 8px;
      text-align: center;
      border: 1px solid #ccc;
      min-height: 50px;
      font-size: 14px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    .monthly-header-cell {
      background: #f5f5f5;
      font-weight: bold;
      cursor: default;
    }
    .monthly-cell:hover:not(.monthly-header-cell) {
      background-color: #e6f4ea;
    }
    .selected-day {
      background-color: #bee5eb !important;
      font-weight: bold;
    }
    /* Daily Schedule Table Styles */
    #dailySchedule {
      width: 100%;
      border-collapse: collapse;
      margin: 20px auto;
      max-width: 800px;
    }
    #dailySchedule th, #dailySchedule td {
      border: 1px solid #ccc;
      padding: 8px;
      text-align: center;
    }
    #dailySchedule th {
      background-color: #f5f5f5;
    }
    #dailySchedule tbody tr:nth-child(even) {
      background-color: #fafafa;
    }
    #dailySchedule tbody tr:hover {
      background-color: #f1f1f1;
    }
    .action-btn {
      padding: 4px 8px;
      margin: 0 2px;
      cursor: pointer;
      font-size: 12px;
    }
    .available {
      background-color: #d4edda;
    }
    .reserved {
      background-color: #f8d7da;
    }
    /* Month Navigation Controls */
    .month-navigation {
      max-width: 800px;
      margin: 10px auto;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 10px;
    }
    .month-navigation button {
      padding: 6px 12px;
      cursor: pointer;
    }
    /* Modal Styles for Adding/Editing Events */
    .modal {
      display: none; 
      position: fixed; 
      z-index: 1000; 
      left: 0;
      top: 0;
      width: 100%; 
      height: 100%; 
      overflow: auto;
      background-color: rgba(0,0,0,0.4);
    }
    .modal-content {
      background-color: #fefefe;
      margin: 10% auto; 
      padding: 20px;
      border: 1px solid #888;
      width: 90%;
      max-width: 400px;
      border-radius: 4px;
    }
    .close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }
  </style>
</head>
<body>
  <header>
    <h1 class="calendar-title">Duques Hall Calendar</h1>
  </header>
  
  <!-- Month Navigation Controls -->
  <div class="month-navigation">
    <button id="prevMonthBtn" aria-label="Previous Month">&laquo; Prev Month</button>
    <span id="monthDisplay"></span>
    <button id="nextMonthBtn" aria-label="Next Month">Next Month &raquo;</button>
  </div>
  
  <!-- Monthly Calendar View -->
  <div id="monthlyCalendar" class="monthly-calendar"></div>
  
  <!-- Daily Calendar Navigation -->
  <div class="calendar-header" style="max-width: 800px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center;">
    <button id="prevBtn" aria-label="Previous Day">&larr; Previous</button>
    <div id="dayDisplay"></div>
    <button id="nextBtn" aria-label="Next Day">Next &rarr;</button>
  </div>
  
  <!-- Add Event Button -->
  <div style="max-width: 800px; margin: 10px auto; text-align: right;">
    <button id="addEventBtn" aria-label="Add Event">Add Event</button>
  </div>
  
  <!-- Daily Schedule Table -->
  <table id="dailySchedule"></table>
  
  <!-- Event Modal -->
  <div id="eventModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal-content">
      <span class="close" id="modalClose" aria-label="Close Modal">&times;</span>
      <h2 id="modalTitle">Add/Edit Event</h2>
      <form id="eventForm">
        <label for="eventTime">Time Slot:</label>
        <select id="eventTime" name="eventTime" required></select>
        <br><br>
        <label for="eventTitle">Event Title:</label>
        <input type="text" id="eventTitle" name="eventTitle" required>
        <br><br>
        <button type="submit">Save Event</button>
      </form>
    </div>
  </div>
  
  <?php include("footer.php"); ?>
  
  <script>
    // Global variables for current date, events array, and editing state
    let currentDate = new Date();
    let events = []; // Each event: { date: "YYYY-MM-DD", time: "9:00 AM", title: "Event Title" }
    let editingEventSlot = null; // Holds the time slot being edited, if any
    const timeSlots = [
      '9:00 AM', '9:30 AM', '10:00 AM', '10:30 AM',
      '11:00 AM', '11:30 AM', '12:00 PM', '12:30 PM',
      '1:00 PM', '1:30 PM', '2:00 PM', '2:30 PM',
      '3:00 PM', '3:30 PM', '4:00 PM', '4:30 PM',
      '5:00 PM', '5:30 PM'
    ];

    // Helper: Format date as "YYYY-MM-DD"
    function formatDateKey(date) {
      return date.toISOString().split('T')[0];
    }

    // Helper: Format date for display (e.g., Monday, March 14)
    function getDateLabel(date) {
      return date.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
    }

    // Update the daily date display
    function updateDateDisplay() {
      document.getElementById("dayDisplay").textContent = getDateLabel(currentDate);
    }

    // Update the month display in navigation
    function updateMonthDisplay() {
      document.getElementById("monthDisplay").textContent = currentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    }

    // Render the daily schedule as a semantic table
    function renderCalendar() {
      const dailySchedule = document.getElementById("dailySchedule");
      dailySchedule.innerHTML = ""; // Clear previous table contents
      
      // Create table header
      const thead = document.createElement("thead");
      const headerRow = document.createElement("tr");
      ["Time Slot", "Status", "Action"].forEach(text => {
        const th = document.createElement("th");
        th.textContent = text;
        headerRow.appendChild(th);
      });
      thead.appendChild(headerRow);
      dailySchedule.appendChild(thead);
      
      // Create table body
      const tbody = document.createElement("tbody");
      timeSlots.forEach(slot => {
        const row = document.createElement("tr");
        // Time Slot Cell
        const tdTime = document.createElement("td");
        tdTime.textContent = slot;
        row.appendChild(tdTime);
        
        // Determine event for current slot
        const eventKey = formatDateKey(currentDate);
        const eventForSlot = events.find(event => event.date === eventKey && event.time === slot);
        const tdStatus = document.createElement("td");
        const tdAction = document.createElement("td");
        
        if (eventForSlot) {
          tdStatus.textContent = "Reserved: " + eventForSlot.title;
          tdStatus.classList.add("reserved");
          // Edit button
          const editBtn = document.createElement("button");
          editBtn.textContent = "Edit";
          editBtn.classList.add("action-btn");
          editBtn.setAttribute("aria-label", `Edit event at ${slot}`);
          editBtn.addEventListener("click", () => editEvent(slot));
          // Delete button
          const deleteBtn = document.createElement("button");
          deleteBtn.textContent = "Delete";
          deleteBtn.classList.add("action-btn");
          deleteBtn.setAttribute("aria-label", `Delete event at ${slot}`);
          deleteBtn.addEventListener("click", () => deleteEvent(slot));
          tdAction.appendChild(editBtn);
          tdAction.appendChild(deleteBtn);
        } else {
          tdStatus.textContent = "Available";
          tdStatus.classList.add("available");
          // Inline add button
          const addBtn = document.createElement("button");
          addBtn.textContent = "Add";
          addBtn.classList.add("action-btn");
          addBtn.setAttribute("aria-label", `Add event at ${slot}`);
          addBtn.addEventListener("click", () => openAddEventModal(slot));
          tdAction.appendChild(addBtn);
        }
        row.appendChild(tdStatus);
        row.appendChild(tdAction);
        tbody.appendChild(row);
      });
      dailySchedule.appendChild(tbody);
    }

    // Open modal for adding or editing an event
    function openAddEventModal(preselectSlot = null, prefillTitle = "") {
      populateTimeOptions(preselectSlot);
      document.getElementById("eventTitle").value = prefillTitle;
      editingEventSlot = preselectSlot;
      modal.style.display = "block";
    }

    // Edit an existing event for a given slot
    function editEvent(slot) {
      const eventKey = formatDateKey(currentDate);
      const eventForSlot = events.find(event => event.date === eventKey && event.time === slot);
      if (eventForSlot) {
        openAddEventModal(slot, eventForSlot.title);
      }
    }

    // Delete an event for a given slot
    function deleteEvent(slot) {
      const eventKey = formatDateKey(currentDate);
      events = events.filter(event => !(event.date === eventKey && event.time === slot));
      renderCalendar();
    }

    // Populate the time slot options in the modal select box
    function populateTimeOptions(preselectSlot = null) {
      const eventTimeSelect = document.getElementById("eventTime");
      eventTimeSelect.innerHTML = "";
      timeSlots.forEach(slot => {
        const option = document.createElement("option");
        option.value = slot;
        option.textContent = slot;
        if (preselectSlot && slot === preselectSlot) {
          option.selected = true;
        }
        eventTimeSelect.appendChild(option);
      });
    }

    // Modal functionality for adding/editing events
    const modal = document.getElementById("eventModal");
    const addEventBtn = document.getElementById("addEventBtn");
    const modalClose = document.getElementById("modalClose");
    const eventForm = document.getElementById("eventForm");

    addEventBtn.addEventListener("click", () => openAddEventModal());
    modalClose.addEventListener("click", () => {
      modal.style.display = "none";
      eventForm.reset();
      editingEventSlot = null;
    });
    window.addEventListener("click", (event) => {
      if (event.target == modal) {
        modal.style.display = "none";
        eventForm.reset();
        editingEventSlot = null;
      }
    });

    eventForm.addEventListener("submit", (e) => {
      e.preventDefault();
      const selectedTime = document.getElementById("eventTime").value;
      const eventTitle = document.getElementById("eventTitle").value.trim();
      if (eventTitle !== "") {
        const eventKey = formatDateKey(currentDate);
        if (editingEventSlot) {
          // Update existing event
          const existingEvent = events.find(event => event.date === eventKey && event.time === editingEventSlot);
          if (existingEvent) {
            existingEvent.title = eventTitle;
          }
        } else {
          // Add new event if one doesn't already exist
          const existingEvent = events.find(event => event.date === eventKey && event.time === selectedTime);
          if (!existingEvent) {
            events.push({ date: eventKey, time: selectedTime, title: eventTitle });
          }
        }
        renderCalendar();
        modal.style.display = "none";
        eventForm.reset();
        editingEventSlot = null;
      }
    });

    // Render the monthly calendar view
    function renderMonthlyCalendar() {
      const monthlyCalendar = document.getElementById("monthlyCalendar");
      monthlyCalendar.innerHTML = ""; // Clear previous content
      
      const year = currentDate.getFullYear();
      const month = currentDate.getMonth();
      const dayNames = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
      
      // Header row for day names
      const headerRow = document.createElement("div");
      headerRow.classList.add("monthly-row", "monthly-header");
      dayNames.forEach(day => {
        const cell = document.createElement("div");
        cell.classList.add("monthly-cell", "monthly-header-cell");
        cell.textContent = day;
        headerRow.appendChild(cell);
      });
      monthlyCalendar.appendChild(headerRow);
      
      // Calculate the first day and days in the month
      const firstDay = new Date(year, month, 1);
      const startingDay = firstDay.getDay();
      const daysInMonth = new Date(year, month + 1, 0).getDate();
      
      let row = document.createElement("div");
      row.classList.add("monthly-row");
      
      // Fill in blank cells for days before month start
      for (let i = 0; i < startingDay; i++) {
        const cell = document.createElement("div");
        cell.classList.add("monthly-cell");
        row.appendChild(cell);
      }
      
      // Fill in day numbers
      for (let d = 1; d <= daysInMonth; d++) {
        if ((startingDay + d - 1) % 7 === 0 && d !== 1) {
          monthlyCalendar.appendChild(row);
          row = document.createElement("div");
          row.classList.add("monthly-row");
        }
        const cell = document.createElement("div");
        cell.classList.add("monthly-cell");
        cell.textContent = d;
        if (currentDate.getDate() === d && currentDate.getMonth() === month && currentDate.getFullYear() === year) {
          cell.classList.add("selected-day");
        }
        cell.addEventListener("click", () => {
          currentDate = new Date(year, month, d);
          updateDateDisplay();
          renderCalendar();
          renderMonthlyCalendar();
        });
        row.appendChild(cell);
      }
      
      // Fill in remaining cells to complete the row
      while (row.children.length < 7) {
        const cell = document.createElement("div");
        cell.classList.add("monthly-cell");
        row.appendChild(cell);
      }
      monthlyCalendar.appendChild(row);
    }

    // Daily navigation event listeners
    document.getElementById("prevBtn").addEventListener("click", () => {
      currentDate.setDate(currentDate.getDate() - 1);
      updateDateDisplay();
      renderCalendar();
      renderMonthlyCalendar();
    });
    document.getElementById("nextBtn").addEventListener("click", () => {
      currentDate.setDate(currentDate.getDate() + 1);
      updateDateDisplay();
      renderCalendar();
      renderMonthlyCalendar();
    });

    // Month navigation event listeners
    document.getElementById("prevMonthBtn").addEventListener("click", () => {
      currentDate.setMonth(currentDate.getMonth() - 1);
      updateMonthDisplay();
      updateDateDisplay();
      renderCalendar();
      renderMonthlyCalendar();
    });
    document.getElementById("nextMonthBtn").addEventListener("click", () => {
      currentDate.setMonth(currentDate.getMonth() + 1);
      updateMonthDisplay();
      updateDateDisplay();
      renderCalendar();
      renderMonthlyCalendar();
    });

    // Initialize calendar views on page load
    document.addEventListener("DOMContentLoaded", () => {
      updateDateDisplay();
      updateMonthDisplay();
      renderCalendar();
      renderMonthlyCalendar();
    });
  </script>
</body>
</html>
