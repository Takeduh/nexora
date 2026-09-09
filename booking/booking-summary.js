(() => {
  const form = document.getElementById('bookingCheckoutForm');
  if (!form) return;

  const pickupLocation = document.getElementById('pickupLocation');
  const pickupDate = document.getElementById('pickupDate');
  const returnDate = document.getElementById('returnDate');
  const pickupDateDisplay = document.getElementById('pickupDateDisplay');
  const returnDateDisplay = document.getElementById('returnDateDisplay');
  const durationOutput = document.getElementById('summaryDuration');
  const totalOutput = document.getElementById('summaryTotal');
  const formulaOutput = document.getElementById('summaryFormula');
  const dateMessage = document.getElementById('dateMessage');
  const submitButton = document.getElementById('continueBookingButton');
  const returnQuery = document.getElementById('returnQuery');
  const availabilityMessage = document.getElementById('availabilityMessage');
  const variantId = form.querySelector('[name="car_variant_id"]').value;
  let availabilityReady = false;
  let availabilityAvailable = true;
  let availabilityTimer = null;
  let availabilityRequest = 0;

  const dailyRate = Number(form.dataset.dailyRate || 0);
  const oneDay = 24 * 60 * 60 * 1000;

  const supportedLocationKeywords = [
    'cebu', 'dumaguete', 'bohol', 'bacolod', 'iloilo',
    'manila', 'makati', 'pasay', 'taguig', 'quezon city',
    'davao', 'cagayan de oro', 'cdo', 'general santos', 'gensan',
    'puerto princesa', 'palawan', 'tagbilaran', 'panglao',
    'tacloban', 'ormoc', 'lapu-lapu', 'lapu lapu', 'mandaue',
    'siargao', 'surigao', 'boracay', 'aklan', 'negros', 'leyte'
  ];

  const hasSupportedLocation = (value) => {
    const location = value.trim().toLowerCase();
    return supportedLocationKeywords.some((keyword) => location.includes(keyword));
  };

  const parseISODate = (value) => {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return null;
    const [year, month, day] = value.split('-').map(Number);
    const date = new Date(year, month - 1, day);
    if (
      date.getFullYear() !== year ||
      date.getMonth() !== month - 1 ||
      date.getDate() !== day
    ) return null;
    return date;
  };

  const parseUSDate = (value) => {
    if (!/^\d{2}\/\d{2}\/\d{4}$/.test(value)) return null;
    const [month, day, year] = value.split('/').map(Number);
    const date = new Date(year, month - 1, day);
    if (
      date.getFullYear() !== year ||
      date.getMonth() !== month - 1 ||
      date.getDate() !== day
    ) return null;
    return date;
  };

  const toISODate = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  };

  const formatUSDate = (date) => {
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const year = date.getFullYear();
    return `${month}/${day}/${year}`;
  };

  const formatPeso = (amount) =>
    new Intl.NumberFormat('en-PH', {
      style: 'currency',
      currency: 'PHP',
      maximumFractionDigits: 0
    }).format(amount).replace('PHP', '₱').trim();

  const autoFormatDate = (input) => {
    const digits = input.value.replace(/\D/g, '').slice(0, 8);
    let value = digits;
    if (digits.length > 2) value = `${digits.slice(0, 2)}/${digits.slice(2)}`;
    if (digits.length > 4) value = `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
    input.value = value;
  };

  const syncHiddenDate = (displayInput, hiddenInput) => {
    const parsed = parseUSDate(displayInput.value.trim());
    hiddenInput.value = parsed ? toISODate(parsed) : '';
    return parsed;
  };

  const buildReturnQuery = () => {
    const params = new URLSearchParams({
      variant: form.querySelector('[name="car_variant_id"]').value,
      loc: pickupLocation.value.trim(),
      pickup: pickupDate.value,
      return: returnDate.value
    });
    returnQuery.value = params.toString();
  };

  const checkAvailability = (start, end) => {
    if (!start || !end || end <= start) {
      availabilityReady = false;
      availabilityAvailable = true;
      if (availabilityMessage) { availabilityMessage.textContent = ''; availabilityMessage.className = 'checkout-date-message'; }
      return;
    }

    clearTimeout(availabilityTimer);
    const requestId = ++availabilityRequest;
    availabilityReady = false;
    if (availabilityMessage) {
      availabilityMessage.textContent = 'Checking availability for these dates...';
      availabilityMessage.className = 'checkout-date-message';
    }

    availabilityTimer = setTimeout(async () => {
      try {
        const response = await fetch(`availability.php?pickup=${encodeURIComponent(toISODate(start))}&return=${encodeURIComponent(toISODate(end))}`, { headers: { 'Accept': 'application/json' } });
        const data = await response.json();
        if (requestId !== availabilityRequest) return;
        if (!response.ok || !data.ok) throw new Error(data.message || 'Availability check failed.');
        const info = data.variants?.[String(variantId)];
        availabilityReady = true;
        availabilityAvailable = Boolean(info?.available);
        if (availabilityMessage) {
          if (!info || !info.available) {
            availabilityMessage.textContent = 'Fully booked for these dates. Choose different dates or another vehicle.';
            availabilityMessage.className = 'checkout-date-message is-error';
          } else if (info.low_stock) {
            availabilityMessage.textContent = `Low availability: only ${info.remaining} left for these dates.`;
            availabilityMessage.className = 'checkout-date-message is-error';
          } else {
            availabilityMessage.textContent = `${info.remaining} available for these dates.`;
            availabilityMessage.className = 'checkout-date-message is-success';
          }
        }
        submitButton.disabled = !(availabilityAvailable && hasSupportedLocation(pickupLocation.value));
      } catch (error) {
        if (requestId !== availabilityRequest) return;
        availabilityReady = false;
        availabilityAvailable = false;
        if (availabilityMessage) {
          availabilityMessage.textContent = 'Could not verify availability. Please try again.';
          availabilityMessage.className = 'checkout-date-message is-error';
        }
        submitButton.disabled = true;
      }
    }, 250);
  };

  const updateSummary = () => {
    const start = syncHiddenDate(pickupDateDisplay, pickupDate);
    const end = syncHiddenDate(returnDateDisplay, returnDate);
    const locationReady = hasSupportedLocation(pickupLocation.value);

    pickupLocation.setCustomValidity(
      pickupLocation.value.trim() && !locationReady
        ? 'Please include a supported major area such as Cebu, Dumaguete, Bohol, Bacolod, Iloilo, or Manila.'
        : ''
    );

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    let validDates = false;
    let days = 0;
    let error = '';

    if (start && start < today) {
      error = 'Pick-up date cannot be in the past.';
    } else if (start && end) {
      days = Math.round((end - start) / oneDay);
      if (days <= 0) {
        error = 'Return date must be at least one day after the pick-up date.';
      } else {
        validDates = true;
      }
    }

    if (!pickupDateDisplay.value && !returnDateDisplay.value) {
      dateMessage.textContent = 'Enter your pick-up and return dates in MM/DD/YYYY format.';
      dateMessage.className = 'checkout-date-message';
    } else if (!pickupDateDisplay.value || !returnDateDisplay.value) {
      dateMessage.textContent = 'Enter both dates in MM/DD/YYYY format to calculate your rental duration.';
      dateMessage.className = 'checkout-date-message';
    } else if (!start || !end) {
      dateMessage.textContent = 'Use a valid date in MM/DD/YYYY format.';
      dateMessage.className = 'checkout-date-message is-error';
    } else if (error) {
      dateMessage.textContent = error;
      dateMessage.className = 'checkout-date-message is-error';
    } else {
      dateMessage.textContent = `${days}-day rental selected. Your estimate has been updated.`;
      dateMessage.className = 'checkout-date-message is-success';
    }

    if (validDates) {
      const total = days * dailyRate;
      durationOutput.textContent = `${days} ${days === 1 ? 'day' : 'days'}`;
      totalOutput.textContent = formatPeso(total);
      formulaOutput.textContent = `${days} × ${formatPeso(dailyRate)} per day`;
    } else {
      durationOutput.textContent = 'Select dates';
      totalOutput.textContent = '—';
      formulaOutput.textContent = 'Based on your rental duration';
    }

    if (validDates) {
      checkAvailability(start, end);
    } else {
      availabilityReady = false;
      availabilityAvailable = true;
      if (availabilityMessage) { availabilityMessage.textContent = ''; availabilityMessage.className = 'checkout-date-message'; }
    }
    submitButton.disabled = !(validDates && locationReady && availabilityReady && availabilityAvailable);
    buildReturnQuery();
  };

  [pickupDateDisplay, returnDateDisplay].forEach((input) => {
    input.addEventListener('input', () => {
      autoFormatDate(input);
      updateSummary();
    });
    input.addEventListener('blur', () => {
      const parsed = parseUSDate(input.value.trim());
      if (parsed) input.value = formatUSDate(parsed);
      updateSummary();
    });
  });

  pickupLocation.addEventListener('input', updateSummary);

  form.addEventListener('submit', (event) => {
    const start = syncHiddenDate(pickupDateDisplay, pickupDate);
    const end = syncHiddenDate(returnDateDisplay, returnDate);
    const locationReady = hasSupportedLocation(pickupLocation.value);

    const validDates = Boolean(start && end && end > start);
    const canProceed = validDates && locationReady && availabilityReady && availabilityAvailable;

    if (!canProceed || !form.checkValidity()) {
      event.preventDefault();
    }
  });

  
  const initialPickup = parseISODate(pickupDate.value);
  const initialReturn = parseISODate(returnDate.value);
  if (initialPickup) pickupDateDisplay.value = formatUSDate(initialPickup);
  if (initialReturn) returnDateDisplay.value = formatUSDate(initialReturn);

  updateSummary();
})();
