// — Menü aç/kapa —
document.getElementById('menu-toggle').onclick = toggleMenu;
function toggleMenu(){
  let m = document.getElementById('popup-menu');
  m.style.display = m.style.display === 'flex' ? 'none' : 'flex';
}

// — Dil dropdown’u aç/kapa —
function toggleDropdown(){
  let d = document.getElementById('dropdown-content');
  d.style.display = d.style.display === 'block' ? 'none' : 'block';
}
window.addEventListener('click', function(e) {
  if (!event.target.matches('.dropbtn')) {
    document.querySelectorAll('.dropdown-content')
      .forEach(d => d.style.display = 'none');
  }
});


// — Leaflet harita başlangıcı —
let map;
window.addEventListener('load', () => {
  map = L.map('map').setView([39.0, 35.0], 5);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
  }).addTo(map);

  // İlk başta tüm pinleri göster
  displayPins(mapPins);
});

// — Tüm pinleri haritada gösteren fonksiyon —
function displayPins(pins) {
  // Önce eski pinleri temizle
  map.eachLayer(layer => {
    if (layer instanceof L.CircleMarker) {
      map.removeLayer(layer);
    }
  });

  // Yeni pinleri ekle
  pins.forEach(pin => {
    L.circleMarker([pin.latitude, pin.longitude], {
      radius: 6,
      fillColor: pin.pin_color === 'kirmizi' ? 'red' : 'green',
      color: '#000',
      weight: 1,
      opacity: 1,
      fillOpacity: 0.8
    }).addTo(map);
  });
}





// — Pinleri filtreleyen fonksiyon —
function filterPins(country, city) {
  const filtered = mapPins.filter(pin => {
    if (country && city) return pin.country === country && pin.city === city;
    if (country) return pin.country === country;
    return true;
  });
  displayPins(filtered);
}

// — Kazanç grafiği —
const ctx = document.getElementById('earningsChart').getContext('2d');
const chart = new Chart(ctx, {
  type: 'line',
  data: {
    labels: earningsData.map(e => e.date),
    datasets: [{
      label: 'Kazanç ($)',
      data: earningsData.map(e => e.earnings),
      fill: true,
      borderColor: 'crimson',
      backgroundColor: 'rgba(220, 53, 69, 0.2)',
      tension: 0.3,
      pointRadius: 3,
      pointBackgroundColor: 'white',
      pointBorderColor: 'crimson',
      pointBorderWidth: 2
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: {
        position: 'top',
        labels: {
          color: '#000',
          font: { size: 14, weight: 'bold' }
        }
      }
    },
    scales: {
      x: {
        title: { display: true, text: 'Tarih', color: '#333' },
        ticks: { color: '#333' },
        grid: { color: '#e5e5e5' }
      },
      y: {
        beginAtZero: true,
        title: { display: true, text: 'Kazanç ($)', color: '#333' },
        ticks: { color: '#333' },
        grid: { color: '#e5e5e5' }
      }
    },
    animation: {
      duration: 1500,
      easing: 'easeOutQuart'
    }
  }
});

function updateChart() {
  let s = document.getElementById('startDate').value;
  let e = document.getElementById('endDate').value;
  if (!s || !e) return;
  let flt = earningsData.filter(d => d.date >= s && d.date <= e);
  chart.data.labels = flt.map(d => d.date);
  chart.data.datasets[0].data = flt.map(d => d.earnings);
  chart.update();
}
document.getElementById('startDate').onchange = updateChart;
document.getElementById('endDate').onchange = updateChart;

// — Ödül kutusu aç/kapa —
function toggleReward() {
  const box = document.getElementById("rewardBox");
  box.style.display = (box.style.display === "block") ? "none" : "block";
}
window.addEventListener("click", function(e) {
  const btn = document.querySelector(".reward-btn");
  const box = document.getElementById("rewardBox");
  if (!btn.contains(e.target) && !box.contains(e.target)) {
    box.style.display = "none";
  }
});
let worldData = {};

fetch('countries.json')
  .then(res => res.json())
  .then(data => {
    worldData = data;

    // Tüm ülke dropdown'larını doldur
    ['Top', 'Bottom'].forEach(pos => {
      const countrySel = document.getElementById("countrySelect" + pos);
      countrySel.innerHTML = '<option>Ülke Seç</option>';
      Object.keys(worldData).forEach(country => {
        const opt = document.createElement("option");
        opt.value = country;
        opt.textContent = country;
        countrySel.appendChild(opt);
      });
    });
  });
  function updateCityDropdown(pos) {
    const country = document.getElementById("countrySelect" + pos).value;
    const citySel = document.getElementById("citySelect" + pos);
    citySel.innerHTML = '<option>Şehir Seç</option>';
  
    if (worldData[country]) {
      Object.keys(worldData[country]).forEach(city => {
        const opt = document.createElement("option");
        opt.value = city;
        opt.textContent = city;
        citySel.appendChild(opt);
      });
  
      // Sadece ülke seçildiyse ülke merkezine zoom
      const anyCity = Object.values(worldData[country])[0];
      if (anyCity) {
        map.setView([anyCity.lat, anyCity.lng], 5);
      }
    }
  }
  function zoomToSelected(pos) {
    const country = document.getElementById("countrySelect" + pos).value;
    const city    = document.getElementById("citySelect" + pos).value;
  
    // Zoom işlemi (mevcut)
    if (worldData[country] && worldData[country][city]) {
      const coord = worldData[country][city];
      map.setView([coord.lat, coord.lng], coord.zoom);
    }
  
    // Pinleri filtrele
    filterPins(country, city);
  }
  function sendNote(email) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'not.php';
  
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'targetEmail';
    input.value = email;
  
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
  }
  
  
