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

        <button type="button" class="btn btn-primary btn-block mt-7" id="continueBtn">Continue Booking</button>
        <p class="text-sm text-gray-500 text-center mt-2.5 hidden" id="continueNote">Customer details and booking confirmation are coming in the next phase — this is as far as the booking flow goes for now.</p>

        <a href="fleet.php" class="block text-center mt-3.5 text-sm font-bold text-blue-500 hover:underline">← Choose a different car</a>
      </div>
    </div>
  `;

  const continueBtn = document.getElementById("continueBtn");
  const continueNote = document.getElementById("continueNote");
  if (continueBtn && continueNote) {
    continueBtn.addEventListener("click", () => continueNote.classList.remove("hidden"));
  }
}

renderSummary();
