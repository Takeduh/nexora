/* =========================================================
   BOOKING SUMMARY — reads ?car=&loc=&pickup=&return= from the
   URL (set when a car is selected on the homepage or fleet
   page) and renders a summary against the shared `cars` data.

   No server-side session yet — that's Phase 2. For now the
   selection travels entirely through the URL, which is also
   why refreshing or sharing this link keeps working.
   ========================================================= */

const params = new URLSearchParams(window.location.search);
const carId = params.get("car");
const loc = params.get("loc");
const pickup = params.get("pickup");
const returnDate = params.get("return");

const summaryContent = document.getElementById("summaryContent");
const summaryNotFound = document.getElementById("summaryNotFound");

function formatDate(dateStr) {
  if (!dateStr) return null;
  const d = new Date(dateStr + "T00:00:00");
  if (isNaN(d)) return null;
  return d.toLocaleDateString("en-PH", { year: "numeric", month: "short", day: "numeric" });
}

function nightsBetween(pickupStr, returnStr) {
  if (!pickupStr || !returnStr) return null;
  const start = new Date(pickupStr + "T00:00:00");
  const end = new Date(returnStr + "T00:00:00");
  if (isNaN(start) || isNaN(end) || end <= start) return null;
  return Math.round((end - start) / 86400000);
}

function renderSummary() {
  const car = carId ? getCarById(carId) : null;

  if (!car) {
    if (summaryContent) summaryContent.classList.add("hidden");
    if (summaryNotFound) summaryNotFound.classList.remove("hidden");
    return;
  }

  const nights = nightsBetween(pickup, returnDate);
  const total = nights ? nights * car.price : null;

  summaryContent.innerHTML = `
    <div class="bg-white rounded-[14px] shadow-card-lg overflow-hidden mb-5">
      <div class="car-media media-${car.cat}" style="aspect-ratio:16/8">
        <span class="car-tag cat-${car.cat}">${car.label}</span>
        <img src="${car.image}" alt="${car.name}" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='block'">
        <div style="display:none">${carIcon}</div>
      </div>
      <div class="p-[22px]">
        <h2 class="text-2xl font-extrabold mb-2">${car.name}</h2>
        <p class="text-gray-500 text-sm mb-5">${car.seats} seats &middot; ${car.transmission} &middot; ${car.fuel}</p>

        <div class="summary-row"><span>Pick-up Location</span><b>${loc || "Not selected yet"}</b></div>
        <div class="summary-row"><span>Pick-up Date</span><b>${formatDate(pickup) || "Not selected yet"}</b></div>
        <div class="summary-row"><span>Return Date</span><b>${formatDate(returnDate) || "Not selected yet"}</b></div>
        <div class="summary-row"><span>Duration</span><b>${nights ? nights + (nights === 1 ? " night" : " nights") : "—"}</b></div>
        <div class="summary-row"><span>Rate</span><b>PHP ${car.price.toLocaleString()} /day</b></div>
        <div class="summary-row summary-total"><span>Estimated Total</span><b>${total ? "PHP " + total.toLocaleString() : "Select both dates to see a total"}</b></div>

        <form method="post" action="process-booking.php" class="mt-7">
          <input type="hidden" name="vehicle_code" value="${car.id}">
          <input type="hidden" name="vehicle_name" value="${car.name.replace(/&/g, '&amp;').replace(/"/g, '&quot;')}">
          <input type="hidden" name="pickup_location" value="${(loc || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;')}">
          <input type="hidden" name="pickup_date" value="${pickup || ''}">
          <input type="hidden" name="return_date" value="${returnDate || ''}">
          <input type="hidden" name="daily_rate" value="${car.price}">
          <input type="hidden" name="return_query" value="${params.toString().replace(/&/g, '&amp;').replace(/"/g, '&quot;')}">
          <label class="block text-sm font-semibold mb-2" for="specialRequests">Special requests <span class="text-gray-500 font-normal">(optional)</span></label>
          <textarea id="specialRequests" name="special_requests" rows="3" class="w-full border-[1.5px] border-gray-200 rounded-[9px] px-3 py-2.5 text-sm outline-none focus:border-blue-500" placeholder="Anything we should know about your booking?"></textarea>
          <button type="submit" class="btn btn-primary btn-block mt-4" ${(!nights || !loc) ? 'disabled' : ''}>Continue Booking</button>
        </form>

        <a href="fleet.php" class="block text-center mt-3.5 text-sm font-bold text-blue-500 hover:underline">← Choose a different car</a>
      </div>
    </div>
  `;
}

renderSummary();
