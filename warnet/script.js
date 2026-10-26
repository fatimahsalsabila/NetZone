// Search filter on the home page.
(() => {
  const input = document.querySelector('.search input');
  const items = document.querySelectorAll('.quick__item');
  if (!input) return;
  input.addEventListener('input', () => {
    const q = input.value.trim().toLowerCase();
    items.forEach(i => { i.style.display = !q || i.textContent.toLowerCase().includes(q) ? '' : 'none'; });
  });
})();

function jakartaDateTimeParts(){
  const parts = new Intl.DateTimeFormat('en-GB', {
    timeZone: 'Asia/Jakarta',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23'
  }).formatToParts(new Date());
  const values = Object.fromEntries(parts.map(part => [part.type, part.value]));
  return {
    date: `${values.year}-${values.month}-${values.day}`,
    time: `${values.hour}:${values.minute}`
  };
}

function localDateString(){
  return jakartaDateTimeParts().date;
}

function bookingStartHasPassed(date, time){
  const now = jakartaDateTimeParts();
  return date < now.date || (date === now.date && time <= now.time);
}

function updateBookingTimeOptions(){
  const dateInput = document.getElementById('date');
  const timeSelect = document.getElementById('time');
  if (!dateInput || !timeSelect) return;

  const now = jakartaDateTimeParts();
  let firstAvailable = '';
  [...timeSelect.options].forEach(option => {
    const isPast = dateInput.value === now.date && option.value <= now.time;
    option.disabled = isPast;
    option.textContent = isPast ? `${option.value} (Sudah lewat)` : option.value;
    if (!isPast && !firstAvailable) firstAvailable = option.value;
  });

  if (timeSelect.selectedOptions[0]?.disabled) {
    timeSelect.value = firstAvailable;
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const profilePhotoButton = document.getElementById('manage-profile-photo');
  const profilePhotoForm = document.getElementById('profile-photo-form');
  const profilePhotoInput = document.getElementById('profile-photo-input');
  const profilePhotoDialog = document.getElementById('profile-photo-dialog');
  const changeProfilePhoto = document.getElementById('change-profile-photo');
  const cancelProfilePhoto = document.getElementById('cancel-profile-photo');
  const openProfileCamera = document.getElementById('open-profile-camera');
  const cameraPanel = document.getElementById('profile-camera-panel');
  const cameraPreview = document.getElementById('profile-camera-preview');
  const cameraError = document.getElementById('profile-camera-error');
  const captureProfilePhoto = document.getElementById('capture-profile-photo');
  const switchProfileCamera = document.getElementById('switch-profile-camera');
  const cameraFallback = document.getElementById('camera-profile-photo-fallback');
  const backToPhotoActions = document.getElementById('back-profile-photo-actions');
  const profilePhotoActions = document.getElementById('profile-photo-actions');
  const cropPanel = document.getElementById('profile-crop-panel');
  const cropStage = document.getElementById('profile-crop-stage');
  const cropImage = document.getElementById('profile-crop-image');
  const cropZoom = document.getElementById('profile-crop-zoom');
  const cropError = document.getElementById('profile-crop-error');
  const useProfileCrop = document.getElementById('use-profile-crop');
  const cancelProfileCrop = document.getElementById('cancel-profile-crop');

  if (profilePhotoButton && profilePhotoForm && profilePhotoInput) {
    let cameraStream = null;
    let cameraRequestId = 0;
    let activeCameraFacingMode = 'user';
    let pendingProfilePhoto = null;
    let cropImageUrl = '';
    let cropScale = 1;
    let cropOffsetX = 0;
    let cropOffsetY = 0;
    let cropDrag = null;

    const openPhotoPicker = () => {
      stopCamera();
      profilePhotoDialog?.close();
      profilePhotoInput.value = '';
      profilePhotoInput.click();
    };

    const stopCamera = () => {
      cameraRequestId += 1;
      cameraStream?.getTracks().forEach(track => track.stop());
      cameraStream = null;
      if (cameraPreview) cameraPreview.srcObject = null;
      if (captureProfilePhoto) captureProfilePhoto.disabled = true;
    };

    const clearCropPreview = () => {
      if (cropImageUrl) URL.revokeObjectURL(cropImageUrl);
      cropImageUrl = '';
      pendingProfilePhoto = null;
      cropDrag = null;
      if (cropImage) cropImage.removeAttribute('src');
      if (cropPanel) cropPanel.hidden = true;
      if (cropZoom) cropZoom.value = '1';
      cropScale = 1;
      cropOffsetX = 0;
      cropOffsetY = 0;
    };

    const renderCropPreview = () => {
      if (!pendingProfilePhoto || !cropStage || !cropImage?.naturalWidth) return;
      const stageSize = cropStage.clientWidth;
      if (!stageSize) return;
      const baseScale = Math.max(
        stageSize / cropImage.naturalWidth,
        stageSize / cropImage.naturalHeight
      );
      const width = cropImage.naturalWidth * baseScale * cropScale;
      const height = cropImage.naturalHeight * baseScale * cropScale;
      cropOffsetX = Math.max(-(width - stageSize) / 2, Math.min((width - stageSize) / 2, cropOffsetX));
      cropOffsetY = Math.max(-(height - stageSize) / 2, Math.min((height - stageSize) / 2, cropOffsetY));
      cropImage.style.width = `${width}px`;
      cropImage.style.height = `${height}px`;
      cropImage.style.left = `calc(50% + ${cropOffsetX}px)`;
      cropImage.style.top = `calc(50% + ${cropOffsetY}px)`;
    };

    const showCropPreview = file => {
      if (!file) return;
      stopCamera();
      pendingProfilePhoto = file;
      cropScale = 1;
      cropOffsetX = 0;
      cropOffsetY = 0;
      cropZoom.value = '1';
      cropError.hidden = true;
      cameraPanel.hidden = true;
      profilePhotoActions.hidden = true;
      cropPanel.hidden = false;
      if (!profilePhotoDialog.open) profilePhotoDialog.showModal();
      if (cropImageUrl) URL.revokeObjectURL(cropImageUrl);
      cropImageUrl = URL.createObjectURL(file);
      cropImage.onload = renderCropPreview;
      cropImage.src = cropImageUrl;
    };

    const submitCroppedPhoto = () => {
      if (!pendingProfilePhoto || !cropImage?.naturalWidth || !cropStage.clientWidth) return;
      const stageSize = cropStage.clientWidth;
      const baseScale = Math.max(
        stageSize / cropImage.naturalWidth,
        stageSize / cropImage.naturalHeight
      );
      const displayScale = baseScale * cropScale;
      const sourceSize = stageSize / displayScale;
      const sourceX = Math.max(0, Math.min(
        cropImage.naturalWidth - sourceSize,
        cropImage.naturalWidth / 2 - cropOffsetX / displayScale - sourceSize / 2
      ));
      const sourceY = Math.max(0, Math.min(
        cropImage.naturalHeight - sourceSize,
        cropImage.naturalHeight / 2 - cropOffsetY / displayScale - sourceSize / 2
      ));
      const canvas = document.createElement('canvas');
      canvas.width = 512;
      canvas.height = 512;
      const context = canvas.getContext('2d');
      if (!context) return;
      context.drawImage(cropImage, sourceX, sourceY, sourceSize, sourceSize, 0, 0, 512, 512);
      canvas.toBlob(blob => {
        if (!blob) {
          cropError.textContent = 'Foto belum bisa diproses. Silakan coba lagi.';
          cropError.hidden = false;
          return;
        }
        const file = new File([blob], 'profile-photo.jpg', { type: 'image/jpeg' });
        const transfer = new DataTransfer();
        transfer.items.add(file);
        profilePhotoInput.files = transfer.files;
        profilePhotoDialog.close();
        profilePhotoForm.requestSubmit();
      }, 'image/jpeg', 0.9);
    };

    const showPhotoActions = () => {
      stopCamera();
      if (profilePhotoActions) profilePhotoActions.hidden = false;
      if (cameraPanel) cameraPanel.hidden = true;
      if (cropPanel) cropPanel.hidden = true;
    };

    const showCamera = async (facingMode = activeCameraFacingMode) => {
      if (profilePhotoActions) profilePhotoActions.hidden = true;
      if (cameraPanel) cameraPanel.hidden = false;
      if (cropPanel) cropPanel.hidden = true;
      if (cameraError) cameraError.hidden = true;
      stopCamera();
      activeCameraFacingMode = facingMode;
      const requestId = ++cameraRequestId;

      if (!navigator.mediaDevices?.getUserMedia) {
        if (cameraError) {
          cameraError.textContent = 'Kamera tidak tersedia di browser ini. Kamu tetap bisa memilih foto dari perangkat.';
          cameraError.hidden = false;
        }
        return;
      }

      try {
        const stream = await navigator.mediaDevices.getUserMedia({
          video: { facingMode: { ideal: facingMode } },
          audio: false
        });
        if (requestId !== cameraRequestId || !profilePhotoDialog.open) {
          stream.getTracks().forEach(track => track.stop());
          return;
        }
        cameraStream = stream;
        const actualFacingMode = stream.getVideoTracks()[0]?.getSettings().facingMode;
        activeCameraFacingMode = actualFacingMode === 'environment' ? 'environment' : facingMode;
        cameraPreview.classList.toggle('is-mirrored', activeCameraFacingMode === 'user');
        if (switchProfileCamera) {
          switchProfileCamera.textContent = activeCameraFacingMode === 'user'
            ? 'Pakai kamera belakang'
            : 'Pakai kamera depan';
        }
        cameraPreview.srcObject = cameraStream;
        await cameraPreview.play();
        captureProfilePhoto.disabled = false;
      } catch {
        stopCamera();
        if (cameraError) {
          cameraError.textContent = 'Kamera tidak bisa diakses. Izinkan akses kamera atau pilih foto dari perangkat.';
          cameraError.hidden = false;
        }
      }
    };

    const captureCameraPhoto = () => {
      if (!cameraPreview?.videoWidth || !cameraPreview?.videoHeight) return;
      const canvas = document.createElement('canvas');
      canvas.width = cameraPreview.videoWidth;
      canvas.height = cameraPreview.videoHeight;
      const context = canvas.getContext('2d');
      if (!context) return;
      if (activeCameraFacingMode === 'user') {
        context.translate(canvas.width, 0);
        context.scale(-1, 1);
      }
      context.drawImage(cameraPreview, 0, 0, canvas.width, canvas.height);
      canvas.toBlob(blob => {
        if (!blob) {
          if (cameraError) {
            cameraError.textContent = 'Foto belum bisa diambil. Silakan coba lagi.';
            cameraError.hidden = false;
          }
          return;
        }
        showCropPreview(new File([blob], 'profile-camera.jpg', { type: 'image/jpeg' }));
      }, 'image/jpeg', 0.9);
    };

    profilePhotoButton.addEventListener('click', () => {
      if (profilePhotoDialog?.showModal) {
        profilePhotoDialog.showModal();
      } else {
        openPhotoPicker();
      }
    });
    changeProfilePhoto?.addEventListener('click', openPhotoPicker);
    openProfileCamera?.addEventListener('click', () => showCamera('user'));
    switchProfileCamera?.addEventListener('click', () => {
      showCamera(activeCameraFacingMode === 'user' ? 'environment' : 'user');
    });
    captureProfilePhoto?.addEventListener('click', captureCameraPhoto);
    cameraFallback?.addEventListener('click', openPhotoPicker);
    backToPhotoActions?.addEventListener('click', showPhotoActions);
    cancelProfilePhoto?.addEventListener('click', () => profilePhotoDialog?.close());
    cropZoom?.addEventListener('input', () => {
      cropScale = Number(cropZoom.value);
      renderCropPreview();
    });
    cropImage?.addEventListener('pointerdown', event => {
      event.preventDefault();
      cropDrag = {
        pointerId: event.pointerId,
        startX: event.clientX,
        startY: event.clientY,
        offsetX: cropOffsetX,
        offsetY: cropOffsetY
      };
      cropImage.setPointerCapture(event.pointerId);
    });
    cropImage?.addEventListener('pointermove', event => {
      if (!cropDrag || event.pointerId !== cropDrag.pointerId) return;
      cropOffsetX = cropDrag.offsetX + event.clientX - cropDrag.startX;
      cropOffsetY = cropDrag.offsetY + event.clientY - cropDrag.startY;
      renderCropPreview();
    });
    cropImage?.addEventListener('pointerup', () => { cropDrag = null; });
    cropImage?.addEventListener('pointercancel', () => { cropDrag = null; });
    useProfileCrop?.addEventListener('click', submitCroppedPhoto);
    cancelProfileCrop?.addEventListener('click', () => {
      clearCropPreview();
      showPhotoActions();
    });
    profilePhotoDialog?.addEventListener('close', () => {
      clearCropPreview();
      showPhotoActions();
    });
    profilePhotoInput.addEventListener('change', () => {
      if (profilePhotoInput.files?.length) showCropPreview(profilePhotoInput.files[0]);
    });
  }

  const dateInput = document.getElementById('date');
  if (dateInput) {
    dateInput.min = localDateString();
    dateInput.addEventListener('change', () => {
      if (dateInput.value && dateInput.value < localDateString()) {
        dateInput.value = '';
        alert('Tanggal booking tidak boleh di masa lalu. Silakan pilih hari ini atau tanggal setelahnya.');
      }
      updateBookingTimeOptions();
    });
    updateBookingTimeOptions();
    setInterval(updateBookingTimeOptions, 30000);
  }

  const people = document.getElementById('people');
  const computerCount = document.getElementById('computer_count');
  if (people && computerCount) {
    const updateComputerOptions = () => {
      const max = Number(people.value);
      [...computerCount.options].forEach(option => {
        option.disabled = Number(option.value) > max;
      });
      if (Number(computerCount.value) > max) computerCount.value = String(max);
    };
    people.addEventListener('change', updateComputerOptions);
    updateComputerOptions();
  }

  document.querySelectorAll('.pc-checkbox').forEach(box => {
    box.addEventListener('change', () => {
      const form = box.closest('form');
      const max = Number(form?.querySelector('[name="computer_count"]')?.value || 1);
      const checked = form.querySelectorAll('.pc-checkbox:checked');
      if (checked.length > max) {
        box.checked = false;
        alert(`Kamu hanya bisa memilih ${max} komputer untuk booking ini.`);
      }
      const card = box.closest('.pc-card');
      if (card) card.classList.toggle('is-selected', box.checked);
    });
  });

  const modal = document.getElementById('confirm-modal');
  const open = document.getElementById('open-confirm-modal');
  const paymentProcessing = document.getElementById('payment-processing');
  const payment = document.getElementById('payment');
  const paymentGuide = document.getElementById('payment-guide');
  const paymentDetails = document.getElementById('payment-details');
  const qrisDemo = document.getElementById('qris-demo');
  const vaDemo = document.getElementById('va-demo');
  const vaBank = document.getElementById('va-bank');
  const vaNumber = document.getElementById('va-number');
  const virtualAccounts = {
    'BCA Virtual Account': {bank: 'BCA Virtual Account', number: 'DEMO-BCA-0001'},
    'BNI Virtual Account': {bank: 'BNI Virtual Account', number: 'DEMO-BNI-0002'},
    'BRI Virtual Account': {bank: 'BRI Virtual Account', number: 'DEMO-BRI-0003'},
    'Mandiri Virtual Account': {bank: 'Mandiri Virtual Account', number: 'DEMO-MANDIRI-0004'}
  };
  const updatePaymentGuide = () => {
    const selectedMethod = payment?.value || '';
    if (paymentGuide) {
      paymentGuide.textContent = selectedMethod
        ? 'Detail pembayaran contoh ditampilkan di bawah.'
        : 'Pilih metode untuk melihat detail pembayaran.';
    }
    if (paymentDetails) paymentDetails.hidden = selectedMethod === '';
    if (qrisDemo) qrisDemo.hidden = selectedMethod !== 'QRIS';
    const account = virtualAccounts[selectedMethod];
    if (vaDemo) vaDemo.hidden = !account;
    if (account && vaBank && vaNumber) {
      vaBank.textContent = account.bank;
      vaNumber.textContent = account.number;
    }
  };
  payment?.addEventListener('change', updatePaymentGuide);
  updatePaymentGuide();

  if (open && modal) {
    open.addEventListener('click', () => {
      if (!payment?.value) {
        alert('Pilih metode pembayaran terlebih dahulu.');
        payment?.focus();
        return;
      }
      const form = document.getElementById('booking-form');
      const paymentInput = form?.querySelector('[name="payment"]');
      if (!form || !paymentInput) return;
      paymentInput.value = payment.value;
      open.disabled = true;
      modal.setAttribute('aria-busy', 'true');
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      if (paymentProcessing) paymentProcessing.hidden = false;
      window.setTimeout(() => form.submit(), 500);
    });
  }

  const passwordEyeIcons = {
    visible: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7Z"/><circle cx="12" cy="12" r="3"/></svg>',
    hidden: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18M10.6 5.2A10.8 10.8 0 0 1 12 5c6 0 9.5 7 9.5 7a16 16 0 0 1-3 3.8M6.2 6.2C3.8 8 2.5 12 2.5 12s3.5 7 9.5 7a10 10 0 0 0 4-.8"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>',
  };
  document.querySelectorAll('.toggle-password').forEach(button => {
    const input = document.getElementById(button.dataset.target);
    if (!input) return;
    button.innerHTML = input.type === 'password' ? passwordEyeIcons.visible : passwordEyeIcons.hidden;
    button.setAttribute('aria-label', input.type === 'password' ? 'Tampilkan password' : 'Sembunyikan password');
    button.addEventListener('click', () => {
      input.type = input.type === 'password' ? 'text' : 'password';
      button.setAttribute('aria-label', input.type === 'password' ? 'Tampilkan password' : 'Sembunyikan password');
      button.innerHTML = input.type === 'password' ? passwordEyeIcons.visible : passwordEyeIcons.hidden;
    });
  });
  const passwordInput = document.getElementById('password');
  const checklist = document.getElementById('password-checklist');
  if (passwordInput && checklist) {
    const rules = {length: v => v.length >= 8, upper: v => /[A-Z]/.test(v), number: v => /[0-9]/.test(v), symbol: v => /[^A-Za-z0-9]/.test(v)};
    const updateRules = () => Object.entries(rules).forEach(([key, test]) => {
      const item = checklist.querySelector(`[data-rule="${key}"]`); if (!item) return;
      const ok = test(passwordInput.value); item.classList.toggle('rule-ok', ok); item.textContent = `${ok ? '✓' : '○'} ${({length:'Minimal 8 karakter',upper:'1 huruf kapital',number:'1 angka',symbol:'1 simbol'})[key]}`;
    });
    passwordInput.addEventListener('input', updateRules); updateRules();
  }
  const params = new URLSearchParams(location.search);
  if (params.get('error') === 'pastdate') alert('Tanggal booking tidak boleh di masa lalu. Silakan pilih tanggal yang benar.');
  if (params.get('error') === 'pasttime') alert('Waktu mulai sudah lewat. Silakan pilih jam yang masih tersedia.');
  if (params.get('error') === 'computers') alert('Pilihan komputer tidak sesuai. Silakan isi booking lagi.');
  if (params.get('error') === 'taken') alert('Maaf, komputer yang kamu pilih baru saja dipesan orang lain di jam yang sama. Silakan pilih lagi.');
  if (params.get('error') === 'payment') alert('Pilih metode pembayaran terlebih dahulu.');
  if (params.get('error') === 'save') alert('Booking belum bisa disimpan. Pastikan folder storage bisa ditulis oleh Laragon.');
});

function goToComputers(event){
  event.preventDefault();
  const branchLocation = document.getElementById('location')?.value || '';
  const type = document.querySelector('input[name="type"]:checked')?.value || 'regular';
  const date = document.getElementById('date')?.value || '';
  const time = document.getElementById('time')?.value || '';
  const duration = document.getElementById('duration')?.value || '1';
  const people = document.getElementById('people')?.value || '1';
  const computerCount = document.getElementById('computer_count')?.value || '1';
  const name = document.getElementById('name')?.value.trim() || '';
  if(!branchLocation){ alert('Silakan pilih lokasi NetZone terlebih dahulu.'); document.getElementById('location')?.focus(); return false; }
  if(!date){ alert('Silakan pilih tanggal booking terlebih dahulu.'); return false; }
  if(date < localDateString()){ alert('Tanggal booking tidak boleh di masa lalu.'); return false; }
  updateBookingTimeOptions();
  if(bookingStartHasPassed(date, time)){
    alert('Waktu mulai tersebut sudah lewat. Silakan pilih jam yang masih tersedia.');
    return false;
  }
  if(Number(computerCount) > Number(people)){ alert('Jumlah komputer tidak boleh lebih banyak dari jumlah orang.'); return false; }
  const q = new URLSearchParams({location: branchLocation,type,date,time,duration,people,computer_count:computerCount,name});
  window.location.href = './pilih-komputer.php?' + q.toString();
  return false;
}

function validatePcSelection(event, requiredCount){
  const checked = event.currentTarget.querySelectorAll('.pc-checkbox:checked');
  if(checked.length !== requiredCount){
    event.preventDefault();
    alert(`Pilih tepat ${requiredCount} komputer sebelum melanjutkan.`);
    return false;
  }
  return true;
}

function formatRupiah(n){ return 'Rp ' + Number(n || 0).toLocaleString('id-ID'); }
function formatDate(value){
  if(!value) return '-';
  const d = new Date(value + 'T00:00:00');
  return Number.isNaN(d.getTime()) ? value : d.toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'});
}
function escapeHtml(value){
  return String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
