/* =========================================================
   NEXORA — SHARED VEHICLE DATA
   Single source of truth for every vehicle on the site.
   Used by: script.js (homepage), fleet.js (full fleet page),
   booking-summary.js (selected vehicle lookup).

   NOTE: placeholder pricing/specs — replace with real fleet
   data whenever you have it. Structure is what matters for
   now (id is used in the URL when a car is selected).
   ========================================================= */

const cars = [
  { id: "vios-gr",        name: "Toyota Vios GR-S",              cat: "sedan", label: "Sedan",           price: 1799, seats: 5,  transmission: "Automatic", fuel: "Gasoline",         image: "Images/fleet/vios-gr.jpg" },
  { id: "civic-rs",       name: "Honda Civic RS",                 cat: "sedan", label: "Sedan",           price: 2799, seats: 5,  transmission: "Automatic", fuel: "Gasoline",         image: "Images/fleet/civic-rs.jpg" },
  { id: "mazda3",         name: "Mazda 3",                        cat: "sedan", label: "Sedan",           price: 2399, seats: 5,  transmission: "Automatic", fuel: "Gasoline",         image: "Images/fleet/mazda3.jpg" },
  { id: "3series",        name: "BMW 3 Series",                   cat: "sedan", label: "Premium",         price: 6499, seats: 5,  transmission: "Automatic", fuel: "Gasoline",         image: "Images/fleet/3series.avif" },
  { id: "mg5",            name: "MG 5",                            cat: "sedan", label: "Sedan",           price: 1799, seats: 5,  transmission: "Automatic", fuel: "Gasoline",         image: "Images/fleet/mg5.jpg" },
  { id: "fortuner",       name: "Toyota Fortuner",                 cat: "suv",   label: "SUV",             price: 3499, seats: 7,  transmission: "Automatic", fuel: "Diesel",           image: "Images/fleet/fortuner.jpg" },
  { id: "montero-sport",  name: "Mitsubishi Montero Sport",        cat: "suv",   label: "SUV",             price: 3599, seats: 7,  transmission: "Automatic", fuel: "Diesel",           image: "Images/fleet/montero-sport.jpg" },
  { id: "rav4",           name: "Toyota RAV4",                     cat: "suv",   label: "SUV",             price: 3899, seats: 5,  transmission: "Automatic", fuel: "Gasoline",         image: "Images/fleet/rav4.avif" },
  { id: "kona",           name: "Hyundai Kona Hybrid",              cat: "suv",   label: "Hybrid",          price: 2799, seats: 5,  transmission: "Automatic", fuel: "Hybrid",           image: "Images/fleet/kona.avif" },
  { id: "sonet",          name: "Kia Sonet",                        cat: "suv",   label: "Crossover",       price: 1999, seats: 5,  transmission: "Automatic", fuel: "Gasoline",         image: "Images/fleet/sonet.jpg" },
  { id: "sealion6",       name: "BYD Sealion 6 DM-i",               cat: "suv",   label: "Plug-in Hybrid",  price: 3299, seats: 5,  transmission: "Automatic", fuel: "Plug-in Hybrid",  image: "Images/fleet/sealion6.jpg" },
  { id: "avanza",         name: "Toyota Avanza",                    cat: "mpv",   label: "MPV",             price: 2199, seats: 7,  transmission: "Manual",    fuel: "Gasoline",         image: "Images/fleet/avanza.jpg" },
  { id: "innova",         name: "Toyota Innova",                    cat: "mpv",   label: "MPV",             price: 2599, seats: 7,  transmission: "Automatic", fuel: "Diesel",           image: "Images/fleet/innova.jpg" },
  { id: "veloz",          name: "Toyota Veloz",                     cat: "mpv",   label: "MPV",             price: 2499, seats: 7,  transmission: "Automatic", fuel: "Gasoline",         image: "Images/fleet/veloz.jpg" },
  { id: "urvan",          name: "Nissan Urvan",                     cat: "van",   label: "Van",             price: 3499, seats: 15, transmission: "Manual",    fuel: "Diesel",           image: "Images/fleet/urvan.jpg" },
  { id: "hiace",          name: "Toyota Hiace",                     cat: "van",   label: "Van",             price: 3999, seats: 15, transmission: "Manual",    fuel: "Diesel",           image: "Images/fleet/hiace.jpg" },
  { id: "tamaraw",        name: "Toyota Tamaraw Utility Van",       cat: "van",   label: "Utility Van",     price: 3499, seats: 12, transmission: "Manual",    fuel: "Diesel",           image: "Images/fleet/tamaraw.jpg" },
  { id: "dmax",           name: "Isuzu D-Max 3.0 LS-E",             cat: "pickup",label: "Pickup",          price: 3499, seats: 5,  transmission: "Automatic", fuel: "Diesel",           image: "Images/fleet/dmax.jpg" },
  { id: "shark6",         name: "BYD Shark 6 DMO",                  cat: "pickup",label: "Hybrid Pickup",   price: 4999, seats: 5,  transmission: "Automatic", fuel: "Plug-in Hybrid",  image: "Images/fleet/shark6.jpg" },
  { id: "gr86",           name: "Toyota GR86",                      cat: "sport", label: "Sport",           price: 5999, seats: 4,  transmission: "Manual",    fuel: "Gasoline",         image: "Images/fleet/gr86.avif" },
  { id: "gr-yaris",       name: "Toyota GR Yaris",                  cat: "sport", label: "Premium",         price: 5999, seats: 4,  transmission: "Manual",    fuel: "Gasoline",         image: "Images/fleet/gr-yaris.jpg" },
  { id: "nissan-z",       name: "Nissan Z",                         cat: "sport", label: "Premium",         price: 8999, seats: 4,  transmission: "Automatic", fuel: "Gasoline",         image: "Images/fleet/nissan-z.avif" },
  { id: "camaro",         name: "Chevrolet Camaro",                 cat: "sport", label: "Premium",         price: 7999, seats: 4,  transmission: "Automatic", fuel: "Gasoline",         image: "Images/fleet/camaro.avif" },
  { id: "gr-supra",       name: "Toyota GR Supra",                  cat: "sport", label: "Premium",         price: 8999, seats: 4,  transmission: "Automatic", fuel: "Gasoline",         image: "Images/fleet/gr-supra.jpg" },
  { id: "mustang",        name: "Ford Mustang",                     cat: "sport", label: "Premium",         price: 7999, seats: 4,  transmission: "Automatic", fuel: "Gasoline",         image: "Images/fleet/mustang.jpg" }
];

const carIcon = `
<svg viewBox="0 0 64 34" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M4 24 L8 12 Q10 8 16 8 H44 Q50 8 52 12 L58 24" stroke="#0A1730" stroke-width="2" fill="rgba(10,23,48,0.06)"/>
  <rect x="2" y="22" width="60" height="7" rx="3.5" fill="#0A1730"/>
  <rect x="20" y="10" width="20" height="9" rx="1.5" fill="rgba(255,255,255,.7)"/>
  <circle cx="15" cy="29" r="5" fill="#0A1730" stroke="white" stroke-width="1.4"/>
  <circle cx="49" cy="29" r="5" fill="#0A1730" stroke="white" stroke-width="1.4"/>
</svg>`;

/* Helper other scripts can rely on */
function getCarById(id) {
  return cars.find(c => c.id === id) || null;
}
