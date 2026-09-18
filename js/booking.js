document.addEventListener('DOMContentLoaded', function () {

  const form = document.getElementById('bookingForm');
  const spaceSelect = document.getElementById('space');
  const dateInput = document.getElementById('date');
  const startTimeInput = document.getElementById('startTime');
  const endTimeInput = document.getElementById('endTime');
  const fullNameInput = document.getElementById('fullName');
  const emailInput = document.getElementById('email');
  const notesInput = document.getElementById('notes');
  const summaryBox = document.getElementById('bookingSummary');
  const summaryPrice = document.getElementById('summaryPrice');
  const statusAlert = document.getElementById('statusAlert');
  const submitBtn = form.querySelector('button[type="submit"]');

  // The minimum selectable date is today.
  const today = new Date().toISOString().split('T')[0];
  dateInput.setAttribute('min', today);

  function calculateHours(start, end) {
    const [h1, m1] = start.split(':').map(Number);
    const [h2, m2] = end.split(':').map(Number);
    const startMinutes = h1 * 60 + m1;
    const endMinutes = h2 * 60 + m2;
    return (endMinutes - startMinutes) / 60;
  }

  function updateSummary() {
    const selectedOption = spaceSelect.selectedOptions[0];
    const start = startTimeInput.value;
    const end = endTimeInput.value;

    if (!selectedOption || !selectedOption.dataset.price || !start || !end) {
      summaryBox.classList.add('d-none');
      return;
    }

    const pricePerHour = parseFloat(selectedOption.dataset.price);
    const hours = calculateHours(start, end);

    if (hours <= 0) {
      summaryBox.classList.add('d-none');
      return;
    }

    const total = (pricePerHour * hours).toFixed(2);
    summaryPrice.textContent = '€' + total;
    summaryBox.classList.remove('d-none');
  }

  spaceSelect.addEventListener('change', updateSummary);
  startTimeInput.addEventListener('change', updateSummary);
  endTimeInput.addEventListener('change', updateSummary);

  function markInvalid(field) {
    field.classList.add('is-invalid');
    field.classList.remove('is-valid');
  }

  function markValid(field) {
    field.classList.remove('is-invalid');
    field.classList.add('is-valid');
  }

  function showError(message) {
    statusAlert.classList.remove('d-none', 'alert-exito');
    statusAlert.classList.add('alert-error');
    statusAlert.innerHTML = `<i class="bi bi-exclamation-circle-fill"></i>
      <div><strong>We couldn't complete the booking</strong><p class="mb-0">${message}</p></div>`;
    statusAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function showSuccess(message) {
    statusAlert.classList.remove('d-none', 'alert-error');
    statusAlert.classList.add('alert-exito');
    statusAlert.innerHTML = `<i class="bi bi-check-circle-fill"></i>
      <div><strong>Booking confirmed!</strong><p class="mb-0">${message}</p></div>`;
    statusAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    statusAlert.classList.add('d-none');

    let isValid = true;

    // 1) Date can't be earlier than today
    if (!dateInput.value || dateInput.value < today) {
      markInvalid(dateInput);
      isValid = false;
    } else {
      markValid(dateInput);
    }

    // 2) End time must be after start time
    const hours = calculateHours(startTimeInput.value, endTimeInput.value);
    if (!startTimeInput.value || !endTimeInput.value || hours <= 0) {
      markInvalid(endTimeInput);
      isValid = false;
    } else {
      markValid(endTimeInput);
    }

    // 3) Remaining required fields (native browser validation)
    if (!form.checkValidity()) {
      isValid = false;
    }

    form.classList.add('was-validated');

    if (!isValid) {
      return;
    }

    // --- Client-side validation passed: send to the backend ---
    const bookingData = {
      space_id: spaceSelect.value,
      date: dateInput.value,
      start_time: startTimeInput.value,
      end_time: endTimeInput.value,
      name: fullNameInput.value.trim(),
      email: emailInput.value.trim(),
      notes: notesInput.value.trim(),
    };

    submitBtn.disabled = true;
    submitBtn.textContent = 'Sending...';

    try {
      const response = await fetch('php/create_booking.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(bookingData),
      });

      const result = await response.json();

      if (result.success) {
        showSuccess(`Space booked. Total: €${result.total_price}. See you soon.`);
        form.reset();
        form.classList.remove('was-validated');
        summaryBox.classList.add('d-none');
      } else {
        // E.g. the space is already booked for that time slot (overlap)
        showError(result.message || 'Please try again in a few minutes.');
      }
    } catch (error) {
      // This triggers if PHP isn't running on a real server
      // (for example, if you just double-click the HTML file to open it).
      showError('Could not connect to the server. Make sure you are running this through an active PHP server.');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Confirm booking';
    }
  });
});
